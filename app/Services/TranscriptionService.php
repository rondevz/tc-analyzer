<?php

namespace App\Services;

use RuntimeException;

class TranscriptionService
{
    public function __construct(
        private string $whisperBinary = 'whisper-cli',
        private string $whisperModelPath = '',
    ) {}

    public function transcribe(string $videoPath): string
    {
        $wavPath = (string) preg_replace('/\.mp4$/i', '.wav', $videoPath);
        $txtPath = $wavPath . '.txt';

        try {
            [$exitCode, $stderr] = $this->exec([
                'ffmpeg', '-i', $videoPath, '-vn', '-acodec', 'pcm_s16le',
                '-ar', '16000', '-ac', '1', $wavPath, '-y',
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException(sprintf('ffmpeg failed [%s]: %s', $exitCode, $stderr));
            }

            [$exitCode, $stderr] = $this->exec([
                $this->whisperBinary,
                '-m', $this->whisperModelPath,
                '-f', $wavPath,
                '-l', 'auto',
                '-otxt',
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException(sprintf('whisper failed [%s]: %s', $exitCode, $stderr));
            }

            return file_exists($txtPath) ? trim((string) file_get_contents($txtPath)) : '';
        } finally {
            if (file_exists($wavPath)) {
                unlink($wavPath);
            }

            if (file_exists($txtPath)) {
                unlink($txtPath);
            }
        }
    }

    /**
     * @param  string[]  $command
     * @return array{int, string} [exitCode, stderr]
     */
    protected function exec(array $command): array
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if ($process === false) {
            return [-1, 'proc_open failed'];
        }

        stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), trim((string) $stderr)];
    }
}
