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
        $handles = array_filter(array_map('trim', file($csvPath)), fn($h) => str_starts_with($h, '@'));

        if ($limit = $this->option('limit')) {
            $handles = array_slice($handles, 0, (int) $limit);
        }

        foreach ($handles as $handle) {
            try {
                $this->processCreator($handle);
            } catch (\Throwable $e) {
                Creator::where('handle', $handle)->update(['status' => 'failed']);
                Log::error("Creator {$handle} failed: {$e->getMessage()}");
                $this->error("Creator {$handle} failed: {$e->getMessage()}");
            }
        }
    }

    private function processCreator(string $handle): void
    {
        $creator = Creator::firstOrCreate(
            ['handle' => $handle],
            ['status' => 'pending']
        );

        if ($creator->status === 'done') {
            $this->info("Skipping {$handle} (already done)");
            return;
        }

        $creator->update(['status' => 'processing']);

        foreach ($this->scraper->fetchRecentVideos($handle) as $v) {
            Video::firstOrCreate(
                ['creator_handle' => $handle, 'tiktok_id' => $v['tiktok_id']],
                ['tiktok_url' => $v['tiktok_url'], 'status' => 'pending']
            );
        }

        foreach ($creator->videos()->where('status', '!=', 'done')->get() as $video) {
            $this->processVideo($creator, $video);
        }

        $speechTranscripts = $creator->videos()
            ->where('audio_class', 'speech')
            ->where('status', 'done')
            ->pluck('transcript')
            ->filter()
            ->values()
            ->all();

        $framePath = $creator->videos()->whereNotNull('frame_path')->value('frame_path');

        $creator->update([
            'spoken_languages' => $this->languageDetector->detect($speechTranscripts),
            'hair_color'       => $this->hairColorDetector->detect($framePath),
            'status'           => 'done',
            'processed_at'     => now(),
        ]);
    }

    private function processVideo(Creator $creator, Video $video): void
    {
        try {
            $videoPath = $this->downloader->download($video->tiktok_url, $creator->handle, $video->tiktok_id);
            $video->update(['status' => 'downloaded']);

            $transcript = $this->transcriber->transcribe($videoPath);
            $video->update(['transcript' => $transcript, 'status' => 'transcribed']);

            $audioClass = $this->classifier->classify($transcript);
            $video->update(['audio_class' => $audioClass, 'status' => 'classified']);

            if ($audioClass === 'speech' && ! $creator->videos()->whereNotNull('frame_path')->exists()) {
                $framePath = $this->frameExtractor->extract($videoPath, $creator->handle, $video->tiktok_id);
                $video->update(['frame_path' => $framePath]);
            }

            if (! $this->option('keep-videos') && file_exists($videoPath)) {
                unlink($videoPath);
            }

            $video->update(['status' => 'done']);

        } catch (\Throwable $e) {
            $video->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::warning("Video {$video->tiktok_id} failed: {$e->getMessage()}");
            $this->warn("Video {$video->tiktok_id} failed: {$e->getMessage()}");
        }
    }
}
