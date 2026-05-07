<?php

namespace App\Providers;

use App\Services\AudioClassifierService;
use App\Services\FrameExtractorService;
use App\Services\HairColorDetectorService;
use App\Services\LanguageDetectorService;
use App\Services\OllamaService;
use App\Services\ScrapeCreatorsService;
use App\Services\TranscriptionService;
use App\Services\VideoDownloaderService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ScrapeCreatorsService::class, fn() => new ScrapeCreatorsService(
            (string) env('SCRAPECREATORS_API_KEY', '')
        ));

        $this->app->singleton(VideoDownloaderService::class, fn() => new VideoDownloaderService());

        $this->app->singleton(TranscriptionService::class, fn() => new TranscriptionService(
            (string) env('WHISPER_BINARY', 'whisper-cli'),
            (string) env('WHISPER_MODEL_PATH', ''),
        ));

        $this->app->singleton(OllamaService::class);
        $this->app->singleton(FrameExtractorService::class);
        $this->app->singleton(AudioClassifierService::class);
        $this->app->singleton(LanguageDetectorService::class);
        $this->app->singleton(HairColorDetectorService::class);
    }
}
