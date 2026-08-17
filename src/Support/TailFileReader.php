<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Support;

use Luiscamp\LaravelLogMonitor\Contracts\LogReaderInterface;
use Luiscamp\LaravelLogMonitor\DTO\LogFile;

/**
 * Reads the tail of a file without ever loading it entirely into memory,
 * keeping the package stable on multi-hundred-MB log files.
 */
final class TailFileReader implements LogReaderInterface
{
    public function tail(LogFile $file, int $maxBytes): string
    {
        $size = filesize($file->path);

        if ($size === false || $size === 0) {
            return '';
        }

        $handle = fopen($file->path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            $readBytes = min($maxBytes, $size);
            $offset = $size - $readBytes;

            if (fseek($handle, $offset) !== 0) {
                return '';
            }

            $contents = fread($handle, $readBytes);

            if ($contents === false) {
                return '';
            }

            // If we didn't start at byte 0 we likely cut a line in half;
            // drop the partial first line so parsing stays aligned.
            if ($offset > 0) {
                $firstNewline = strpos($contents, "\n");

                if ($firstNewline !== false) {
                    $contents = substr($contents, $firstNewline + 1);
                }
            }

            return $contents;
        } finally {
            fclose($handle);
        }
    }
}
