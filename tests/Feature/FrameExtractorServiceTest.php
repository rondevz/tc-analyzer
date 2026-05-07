<?php

use App\Services\FrameExtractorService;

$tmpBase = sys_get_temp_dir() . '/tc-analyzer-frame-tests';

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

it('returns the path to the extracted frame on success', function () use ($tmpBase) {
    $service = new class($tmpBase) extends FrameExtractorService {
        private int $call = 0;
        private array $responses = [
            [0, "10.5\n", ''],
            [0, '', ''],
        ];
        protected function exec(array $command): array { return $this->responses[$this->call++]; }
    };

    $path = $service->extract('/tmp/video.mp4', '@creator', 'vid123');

    expect($path)->toBe("{$tmpBase}/frames/@creator/vid123.jpg");
});

it('throws when ffprobe exits with non-zero', function () use ($tmpBase) {
    $service = new class($tmpBase) extends FrameExtractorService {
        protected function exec(array $command): array { return [1, '', 'No such file']; }
    };

    expect(fn() => $service->extract('/tmp/missing.mp4', '@creator', 'vid123'))
        ->toThrow(RuntimeException::class, 'ffprobe failed');
});

it('throws when ffmpeg exits with non-zero', function () use ($tmpBase) {
    $service = new class($tmpBase) extends FrameExtractorService {
        private int $call = 0;
        private array $responses = [
            [0, "10.5\n", ''],
            [1, '', 'Invalid data found'],
        ];
        protected function exec(array $command): array { return $this->responses[$this->call++]; }
    };

    expect(fn() => $service->extract('/tmp/video.mp4', '@creator', 'vid123'))
        ->toThrow(RuntimeException::class, 'ffmpeg failed');
});

it('calls ffmpeg with seek time at 30% of video duration', function () use ($tmpBase) {
    $service = new class($tmpBase) extends FrameExtractorService {
        public array $ffmpegCommand = [];
        private int $call = 0;
        private array $responses = [
            [0, "20.0\n", ''],
            [0, '', ''],
        ];
        protected function exec(array $command): array
        {
            if ($this->call === 1) {
                $this->ffmpegCommand = $command;
            }
            return $this->responses[$this->call++];
        }
    };

    $service->extract('/tmp/video.mp4', '@creator', 'vid123');

    $ssIndex = array_search('-ss', $service->ffmpegCommand);
    expect($service->ffmpegCommand[$ssIndex + 1])->toBe('6');
});

it('creates the output directory automatically', function () use ($tmpBase) {
    $service = new class($tmpBase) extends FrameExtractorService {
        private int $call = 0;
        private array $responses = [
            [0, "10.0\n", ''],
            [0, '', ''],
        ];
        protected function exec(array $command): array { return $this->responses[$this->call++]; }
    };

    $service->extract('/tmp/video.mp4', '@newcreator', 'vid456');

    expect(is_dir("{$tmpBase}/frames/@newcreator"))->toBeTrue();
});
