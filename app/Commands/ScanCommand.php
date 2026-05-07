<?php

namespace App\Commands;

use App\Models\Creator;
use App\Models\Video;
use App\Services\AudioClassifierService;
use App\Services\FrameExtractorService;
use App\Services\HairColorDetectorService;
use App\Services\LanguageDetectorService;
use App\Services\ScrapeCreatorsService;
use App\Services\TranscriptionService;
use App\Services\VideoDownloaderService;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;

class ScanCommand extends Command
{
    protected $signature = 'scan
        {csv : Path to CSV file of TikTok handles}
        {--keep-videos : Retain .mp4 files after processing}
        {--limit= : Process only the first N handles}';

    protected $description = 'Run the full TikTok creator analysis pipeline';

    private ScrapeCreatorsService $scraper;
    private VideoDownloaderService $downloader;
    private TranscriptionService $transcriber;
    private AudioClassifierService $classifier;
    private FrameExtractorService $frameExtractor;
    private LanguageDetectorService $languageDetector;
    private HairColorDetectorService $hairColorDetector;

    public function handle(
        ScrapeCreatorsService $scraper,
        VideoDownloaderService $downloader,
        TranscriptionService $transcriber,
        AudioClassifierService $classifier,
        FrameExtractorService $frameExtractor,
        LanguageDetectorService $languageDetector,
        HairColorDetectorService $hairColorDetector,
    ): void {
        $this->scraper = $scraper;
        $this->downloader = $downloader;
        $this->transcriber = $transcriber;
        $this->classifier = $classifier;
        $this->frameExtractor = $frameExtractor;
        $this->languageDetector = $languageDetector;
        $this->hairColorDetector = $hairColorDetector;

        $csvPath = $this->argument('csv');
        $handles = array_values(array_filter(
            array_map('trim', file($csvPath)),
            fn($h) => str_starts_with($h, '@')
        ));

        if ($limit = $this->option('limit')) {
            $handles = array_slice($handles, 0, (int) $limit);
        }

        $total = count($handles);

        foreach ($handles as $index => $handle) {
            try {
                $this->processCreator($handle, $index + 1, $total);
            } catch (\Throwable $e) {
                Creator::where('handle', $handle)->update(['status' => 'failed']);
                Log::error("Creator {$handle} failed: {$e->getMessage()}");
            }
        }
    }

    private function processCreator(string $handle, int $num, int $total): void
    {
        $this->newLine();
        $this->line("<options=bold>{$handle}</> <fg=gray>[{$num}/{$total}]</>");

        $creator = Creator::firstOrCreate(
            ['handle' => $handle],
            ['status' => 'pending']
        );

        if ($creator->status === 'done') {
            $this->line('  <fg=yellow>↷ Already done, skipping</>');
            return;
        }

        $creator->update(['status' => 'processing']);

        $videos = $this->step(
            '  Fetching videos',
            fn() => $this->scraper->fetchRecentVideos($handle),
            fn($vs) => count($vs) . ' video(s) found'
        );

        foreach ($videos as $v) {
            Video::firstOrCreate(
                ['creator_handle' => $handle, 'tiktok_id' => $v['tiktok_id']],
                ['tiktok_url' => $v['tiktok_url'], 'status' => 'pending']
            );
        }

        $pending = $creator->videos()->where('status', '!=', 'done')->get();
        $pendingCount = $pending->count();

        foreach ($pending as $vIndex => $video) {
            $this->newLine();
            $this->line('  <fg=cyan>Video ' . ($vIndex + 1) . "/{$pendingCount}</> <fg=gray>{$video->tiktok_id}</>");
            $this->processVideo($creator, $video);
        }

        $this->newLine();

        $speechTranscripts = $creator->videos()
            ->where('audio_class', 'speech')
            ->where('status', 'done')
            ->pluck('transcript')
            ->filter()
            ->values()
            ->all();

        $framePath = $creator->videos()->whereNotNull('frame_path')->value('frame_path');

        $languages = $this->step(
            '  Detecting languages',
            fn() => $this->languageDetector->detect($speechTranscripts),
            fn($codes) => empty($codes) ? 'none detected' : implode(', ', $codes)
        );

        $hairColor = $this->step(
            '  Detecting hair color',
            fn() => $this->hairColorDetector->detect($framePath),
            fn($color) => $color
        );

        $creator->update([
            'spoken_languages' => $languages,
            'hair_color'       => $hairColor,
            'status'           => 'done',
            'processed_at'     => now(),
        ]);

        $done   = $creator->videos()->where('status', 'done')->count();
        $failed = $creator->videos()->where('status', 'failed')->count();
        $langStr = empty($languages) ? 'none' : implode(', ', $languages);
        $videoSummary = $done . ' done' . ($failed > 0 ? ", {$failed} failed" : '');

        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->line("<options=bold>{$handle}</> · {$videoSummary} · languages: {$langStr} · hair: {$hairColor}");
    }

    private function processVideo(Creator $creator, Video $video): void
    {
        try {
            $videoPath = $this->step(
                '    Downloading',
                fn() => $this->downloader->download($video->tiktok_url, $creator->handle, $video->tiktok_id)
            );
            $video->update(['status' => 'downloaded']);

            $transcript = $this->step(
                '    Transcribing',
                fn() => $this->transcriber->transcribe($videoPath),
                function ($t) {
                    if (empty($t)) {
                        return 'empty';
                    }
                    $preview = mb_substr($t, 0, 80);
                    return '"' . $preview . (mb_strlen($t) > 80 ? '…' : '') . '"';
                }
            );
            $video->update(['transcript' => $transcript, 'status' => 'transcribed']);

            $audioClass = $this->step(
                '    Classifying',
                fn() => $this->classifier->classify($transcript),
                fn($c) => $c
            );
            $video->update(['audio_class' => $audioClass, 'status' => 'classified']);

            if ($audioClass === 'speech') {
                if ($creator->videos()->whereNotNull('frame_path')->exists()) {
                    $this->line('    <fg=gray>Frame already extracted, skipping</>');
                } else {
                    $framePath = $this->step(
                        '    Extracting frame',
                        fn() => $this->frameExtractor->extract($videoPath, $creator->handle, $video->tiktok_id)
                    );
                    $video->update(['frame_path' => $framePath]);
                }
            }

            if (! $this->option('keep-videos') && file_exists($videoPath)) {
                unlink($videoPath);
            }

            $video->update(['status' => 'done']);

        } catch (\Throwable $e) {
            $video->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::warning("Video {$video->tiktok_id} failed: {$e->getMessage()}");
        }
    }

    private function step(string $description, callable $fn, ?callable $format = null): mixed
    {
        $plainLen = mb_strlen(preg_replace('/<[^>]+>/', '', $description));
        $dots = max(52 - $plainLen, 3);

        $this->output->write($description . ' ' . str_repeat('<fg=gray>.</>', $dots) . ' ');

        try {
            $result = $fn();
            $suffix = $format ? (' <fg=gray>' . $format($result) . '</>') : '';
            $this->output->writeln('<fg=green>✓</>' . $suffix);
            return $result;
        } catch (\Throwable $e) {
            $this->output->writeln('<fg=red>✗</> <fg=red>' . $e->getMessage() . '</>');
            throw $e;
        }
    }
}
