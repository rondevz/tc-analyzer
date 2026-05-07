<?php

use App\Services\VideoDownloaderService;

$tmpBase = sys_get_temp_dir() . '/tc-analyzer-tests';

afterEach(function () use ($tmpBase) {
    if (is_dir($tmpBase)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpBase, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($tmpBase);
    }
});

it('returns the correct output path on success', function () use ($tmpBase) {
    $service = new class($tmpBase) extends VideoDownloaderService {
        protected function exec(array $command): array { return [0, '']; }
    };

    $path = $service->download('https://www.tiktok.com/@test/video/abc123', '@test', 'abc123');

    expect($path)->toBe("{$tmpBase}/videos/@test/abc123.mp4");
});

it('creates the output directory automatically', function () use ($tmpBase) {
    $service = new class($tmpBase) extends VideoDownloaderService {
        protected function exec(array $command): array { return [0, '']; }
    };

    $service->download('https://www.tiktok.com/@test/video/abc123', '@test', 'abc123');

    expect(is_dir("{$tmpBase}/videos/@test"))->toBeTrue();
});

it('throws with stderr content on yt-dlp failure', function () use ($tmpBase) {
    $service = new class($tmpBase) extends VideoDownloaderService {
        protected function exec(array $command): array { return [1, 'ERROR: Video unavailable']; }
    };

    expect(fn() => $service->download('https://www.tiktok.com/@test/video/abc123', '@test', 'abc123'))
        ->toThrow(RuntimeException::class, 'ERROR: Video unavailable');
});

it('passes the correct arguments to yt-dlp', function () use ($tmpBase) {
    $service = new class($tmpBase) extends VideoDownloaderService {
        public array $lastCommand = [];

        protected function exec(array $command): array
        {
            $this->lastCommand = $command;
            return [0, ''];
        }
    };

    $service->download('https://www.tiktok.com/@test/video/abc123', '@test', 'abc123');

    expect($service->lastCommand[0])->toBe('yt-dlp')
        ->and($service->lastCommand)->toContain('https://www.tiktok.com/@test/video/abc123')
        ->and($service->lastCommand)->toContain('--no-playlist')
        ->and($service->lastCommand)->toContain('-q')
        ->and($service->lastCommand)->toContain("{$tmpBase}/videos/@test/abc123.mp4");
});
