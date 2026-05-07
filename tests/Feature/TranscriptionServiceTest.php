<?php

use App\Services\TranscriptionService;

$base = sys_get_temp_dir() . '/tc-transcription-tests';

afterEach(function () use ($base): void {
    if (is_dir($base)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }

        rmdir($base);
    }
});

function makeTranscriptionService(array $responses): TranscriptionService
{
    return new class($responses) extends TranscriptionService {
        private int $call = 0;

        public function __construct(private array $responses)
        {
            parent::__construct();
        }

        protected function exec(array $command): array
        {
            return $this->responses[$this->call++] ?? [0, ''];
        }
    };
}

it('returns the transcript string on success', function () use ($base): void {
    mkdir($base, 0755, true);
    $videoPath = $base . '/video.mp4';
    touch($base . '/video.wav');
    file_put_contents($base . '/video.wav.txt', "Hello world\n");

    $service = makeTranscriptionService([[0, ''], [0, '']]);

    expect($service->transcribe($videoPath))->toBe('Hello world');
});

it('returns empty string when whisper produces no output', function () use ($base): void {
    mkdir($base, 0755, true);
    $videoPath = $base . '/video.mp4';
    touch($base . '/video.wav');

    $service = makeTranscriptionService([[0, ''], [0, '']]);

    expect($service->transcribe($videoPath))->toBe('');
});

it('always deletes the wav file even when whisper fails', function () use ($base): void {
    mkdir($base, 0755, true);
    $wavPath   = $base . '/video.wav';
    $videoPath = $base . '/video.mp4';
    touch($wavPath);

    $service = makeTranscriptionService([[0, ''], [1, 'whisper error']]);

    expect(fn(): string => $service->transcribe($videoPath))
        ->toThrow(RuntimeException::class);

    expect(file_exists($wavPath))->toBeFalse();
});

it('throws a descriptive exception on ffmpeg failure', function () use ($base): void {
    mkdir($base, 0755, true);
    $service = makeTranscriptionService([[1, 'No such file']]);

    expect(fn(): string => $service->transcribe($base . '/video.mp4'))
        ->toThrow(RuntimeException::class, 'ffmpeg failed [1]: No such file');
});

it('throws a descriptive exception on whisper failure', function () use ($base): void {
    mkdir($base, 0755, true);
    touch($base . '/video.wav');
    $service = makeTranscriptionService([[0, ''], [1, 'CUDA error']]);

    expect(fn(): string => $service->transcribe($base . '/video.mp4'))
        ->toThrow(RuntimeException::class, 'whisper failed [1]: CUDA error');
});
