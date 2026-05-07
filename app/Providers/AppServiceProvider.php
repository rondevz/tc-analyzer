<?php

namespace App\Providers;

use App\Services\ScrapeCreatorsService;
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
    }
}
