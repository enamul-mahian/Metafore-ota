<?php

namespace App\Services\Admin;

use SplFileInfo;

final class RedactedSystemLogReader
{
    /** @var list<string> */
    public const LEVELS = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    private const MAX_FILES = 10;

    private const MAX_BYTES_PER_FILE = 524_288;

    private const MAX_ENTRIES = 100;

    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? storage_path('logs');
    }

    /**
     * @return array{
     *     entries: list<array{timestamp: string, level: string, summary: string}>,
     *     levelCounts: array<string, int>,
     *     filesInspected: int,
     *     truncated: bool
     * }
     */
    public function read(?string $level = null): array
    {
        $entries = [];
        $files = $this->logFiles();
        $sequence = 0;

        foreach ($files as $file) {
            foreach ($this->tailLines($file) as $line) {
                $metadata = $this->parseMetadata($line);

                if ($metadata === null) {
                    continue;
                }

                $entries[] = $metadata + ['sequence' => $sequence++];
            }
        }

        usort(
            $entries,
            fn (array $left, array $right): int => [
                $right['timestamp'],
                $right['sequence'],
            ] <=> [
                $left['timestamp'],
                $left['sequence'],
            ],
        );

        $levelCounts = array_fill_keys(self::LEVELS, 0);

        foreach ($entries as $entry) {
            $levelCounts[$entry['level']]++;
        }

        $filteredEntries = array_values(array_filter(
            $entries,
            fn (array $entry): bool => $level === null
                || $entry['level'] === $level,
        ));
        $truncated = count($filteredEntries) > self::MAX_ENTRIES;
        $filteredEntries = array_slice($filteredEntries, 0, self::MAX_ENTRIES);

        return [
            'entries' => array_map(
                fn (array $entry): array => [
                    'timestamp' => $entry['timestamp'],
                    'level' => $entry['level'],
                    'summary' => $entry['summary'],
                ],
                $filteredEntries,
            ),
            'levelCounts' => $levelCounts,
            'filesInspected' => count($files),
            'truncated' => $truncated,
        ];
    }

    /** @return list<SplFileInfo> */
    private function logFiles(): array
    {
        $directory = realpath($this->directory);

        if ($directory === false || ! is_dir($directory)) {
            return [];
        }

        $paths = glob($directory.DIRECTORY_SEPARATOR.'laravel*.log');

        if ($paths === false) {
            return [];
        }

        $files = [];

        foreach ($paths as $path) {
            $realPath = realpath($path);

            if (
                $realPath === false
                || is_link($path)
                || ! is_file($realPath)
                || ! str_starts_with(
                    $realPath,
                    $directory.DIRECTORY_SEPARATOR,
                )
            ) {
                continue;
            }

            $files[] = new SplFileInfo($realPath);
        }

        usort(
            $files,
            fn (SplFileInfo $left, SplFileInfo $right): int => [
                $right->getMTime(),
                $right->getFilename(),
            ] <=> [
                $left->getMTime(),
                $left->getFilename(),
            ],
        );

        return array_slice($files, 0, self::MAX_FILES);
    }

    /** @return iterable<string> */
    private function tailLines(SplFileInfo $file): iterable
    {
        $handle = @fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return;
        }

        try {
            $size = $file->getSize();
            $offset = max(0, $size - self::MAX_BYTES_PER_FILE);

            if ($offset > 0) {
                fseek($handle, $offset);
                fgets($handle, 16_384);
            }

            while (($line = fgets($handle, 16_384)) !== false) {
                yield $line;
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return array{timestamp: string, level: string, summary: string}|null */
    private function parseMetadata(string $line): ?array
    {
        $matched = preg_match(
            '/^\[(?<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+[^.\s]+\.(?<level>EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):/i',
            substr($line, 0, 160),
            $matches,
        );

        if ($matched !== 1) {
            return null;
        }

        $level = strtolower($matches['level']);

        return [
            'timestamp' => $matches['timestamp'],
            'level' => $level,
            'summary' => match ($level) {
                'emergency' => 'Emergency application event recorded',
                'alert' => 'Alert application event recorded',
                'critical' => 'Critical application event recorded',
                'error' => 'Application error recorded',
                'warning' => 'Application warning recorded',
                'notice' => 'Application notice recorded',
                'info' => 'Informational application event recorded',
                'debug' => 'Debug application event recorded',
            },
        ];
    }
}
