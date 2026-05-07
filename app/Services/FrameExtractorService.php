<?php

namespace App\Services;

use RuntimeException;

class FrameExtractorService
{
    public function __construct(private readonly ?string $storageBasePath = null) {}

    public function extract(string $videoPath, string $handle, string $tiktokId): string
    {
        [$exitCode, $stdout] = $this->exec([
            'ffprobe', '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'csv=p=0',
            $videoPath,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('ffprobe failed: ' . $stdout);
        }

        $duration = (float) trim($stdout);
        $seekTime = $duration * 0.3;

        $base = $this->storageBasePath ?? storage_path();
        $dir = sprintf('%s/frames/%s', $base, $handle);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $framePath = sprintf('%s/%s.jpg', $dir, $tiktokId);

        [$exitCode, , $stderr] = $this->exec([
            'ffmpeg', '-ss', (string) $seekTime,
            '-i', $videoPath,
            '-frames:v', '1',
            $framePath, '-y',
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('ffmpeg failed: ' . $stderr);
        }

        return $framePath;
    }

    protected function exec(array $command): array
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if ($process === false) {
            return [-1, '', 'proc_open failed'];
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), (string) $stdout, trim((string) $stderr)];
    }
}
