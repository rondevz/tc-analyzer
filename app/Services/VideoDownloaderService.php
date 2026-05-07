<?php

namespace App\Services;

use RuntimeException;

class VideoDownloaderService
{
    public function __construct(private readonly ?string $storageBasePath = null) {}

    public function download(string $url, string $handle, string $tiktokId): string
    {
        $base = $this->storageBasePath ?? storage_path();
        $dir = "{$base}/videos/{$handle}";
        $outputPath = "{$dir}/{$tiktokId}.mp4";

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        [$exitCode, $stderr] = $this->exec(
            ['yt-dlp', $url, '-o', $outputPath, '--no-playlist', '-q']
        );

        if ($exitCode !== 0) {
            throw new RuntimeException("yt-dlp failed [{$exitCode}] for {$url}: {$stderr}");
        }

        return $outputPath;
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
