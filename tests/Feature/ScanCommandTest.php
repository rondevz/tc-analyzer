<?php

use App\Models\Creator;
use App\Models\Video;
use App\Services\AudioClassifierService;
use App\Services\FrameExtractorService;
use App\Services\HairColorDetectorService;
use App\Services\LanguageDetectorService;
use App\Services\OllamaService;
use App\Services\ScrapeCreatorsService;
use App\Services\TranscriptionService;
use App\Services\VideoDownloaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Creates a temp CSV file with one handle per line
function scanCsv(string ...$handles): string
{
    $file = tempnam(sys_get_temp_dir(), 'scan-csv-');
    file_put_contents($file, implode("\n", $handles) . "\n");
    return $file;
}

// Binds default no-op mock services; individual tests can override
function bindScanMocks(array $overrides = []): void
{
    $defaults = [
        ScrapeCreatorsService::class => new class('') extends ScrapeCreatorsService {
            public function fetchRecentVideos(string $handle): array
            {
                return [['tiktok_id' => 'vid1', 'tiktok_url' => sprintf('https://tiktok.com/%s/video/vid1', $handle), 'duration' => 30]];
            }
        },
        VideoDownloaderService::class => new class extends VideoDownloaderService {
            public function download(string $url, string $handle, string $tiktokId): string { return '/tmp/test-video.mp4'; }
        },
        TranscriptionService::class => new class extends TranscriptionService {
            public function transcribe(string $videoPath): string { return 'Hello world'; }
        },
        AudioClassifierService::class => new class(new OllamaService) extends AudioClassifierService {
            public function classify(string $transcript): string { return 'speech'; }
        },
        FrameExtractorService::class => new class extends FrameExtractorService {
            public function extract(string $videoPath, string $handle, string $tiktokId): string { return '/tmp/test-frame.jpg'; }
        },
        LanguageDetectorService::class => new class(new OllamaService) extends LanguageDetectorService {
            public function detect(array $transcripts): array { return ['en']; }
        },
        HairColorDetectorService::class => new class(new OllamaService) extends HairColorDetectorService {
            public function detect(?string $framePath): string { return 'brown hair'; }
        },
    ];

    foreach (array_merge($defaults, $overrides) as $abstract => $instance) {
        app()->instance($abstract, $instance);
    }
}

it('processes a handle end-to-end and saves results to the database', function (): void {
    bindScanMocks();
    $csv = scanCsv('@creator');

    $this->artisan('scan', ['csv' => $csv])->assertSuccessful();

    unlink($csv);

    $creator = Creator::find('@creator');
    expect($creator)->not->toBeNull()
        ->and($creator->status)->toBe('done')
        ->and($creator->spoken_languages)->toBe(['en'])
        ->and($creator->hair_color)->toBe('brown hair')
        ->and($creator->processed_at)->not->toBeNull();

    $video = Video::where('tiktok_id', 'vid1')->first();
    expect($video)->not->toBeNull()
        ->and($video->status)->toBe('done')
        ->and($video->transcript)->toBe('Hello world')
        ->and($video->audio_class)->toBe('speech')
        ->and($video->frame_path)->toBe('/tmp/test-frame.jpg');
});

it('processes only the first N handles when --limit is set', function (): void {
    bindScanMocks();
    $csv = scanCsv('@first', '@second');

    $this->artisan('scan', ['csv' => $csv, '--limit' => 1])->assertSuccessful();

    unlink($csv);

    expect(Creator::find('@first'))->not->toBeNull()
        ->and(Creator::find('@second'))->toBeNull();
});

it('skips a creator that is already done without calling any services', function (): void {
    Creator::create(['handle' => '@done', 'status' => 'done', 'spoken_languages' => ['fr'], 'hair_color' => 'blonde']);

    $scraperCalled = false;
    bindScanMocks([
        ScrapeCreatorsService::class => new class('') extends ScrapeCreatorsService {
            public bool $called = false;

            public function fetchRecentVideos(string $handle): array
            {
                $this->called = true;
                return [];
            }
        },
    ]);

    $csv = scanCsv('@done');
    $this->artisan('scan', ['csv' => $csv])->assertSuccessful();
    unlink($csv);

    $creator = Creator::find('@done');
    expect($creator->status)->toBe('done')
        ->and($creator->spoken_languages)->toBe(['fr'])
        ->and($creator->hair_color)->toBe('blonde');

    expect(Video::where('creator_handle', '@done')->count())->toBe(0);
});

it('marks a video failed and continues when video processing throws', function (): void {
    bindScanMocks([
        VideoDownloaderService::class => new class extends VideoDownloaderService {
            public function download(string $url, string $handle, string $tiktokId): string
            {
                throw new \RuntimeException('Network timeout');
            }
        },
    ]);

    $csv = scanCsv('@creator');
    $this->artisan('scan', ['csv' => $csv])->assertSuccessful();
    unlink($csv);

    $creator = Creator::find('@creator');
    expect($creator->status)->toBe('done');

    $video = Video::where('tiktok_id', 'vid1')->first();
    expect($video->status)->toBe('failed')
        ->and($video->error_message)->toBe('Network timeout');
});

it('marks a creator failed and continues to the next creator when the creator-level pipeline throws', function (): void {
    bindScanMocks([
        ScrapeCreatorsService::class => new class('') extends ScrapeCreatorsService {
            public function fetchRecentVideos(string $handle): array
            {
                if ($handle === '@broken') {
                    throw new \RuntimeException('API rate limit');
                }

                return [['tiktok_id' => 'vid1', 'tiktok_url' => sprintf('https://tiktok.com/%s/video/vid1', $handle), 'duration' => 30]];
            }
        },
    ]);

    $csv = scanCsv('@broken', '@good');
    $this->artisan('scan', ['csv' => $csv])->assertSuccessful();
    unlink($csv);

    expect(Creator::find('@broken')->status)->toBe('failed')
        ->and(Creator::find('@good')->status)->toBe('done');
});
