<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Reads the application log for the back-office journal.
 *
 * There is no external error tracker: this journal and the alert emails are
 * what the administrators have. The file is read from its tail so a log that
 * has grown for months cannot slow the screen down, and split into entries on
 * the header lines Monolog writes (`[date] environment.LEVEL: message`).
 */
class ErrorLogService
{
    /**
     * How far back into the file the journal looks. Two megabytes hold days
     * of ordinary errors; anything older belongs to an archive, not a screen.
     */
    private const MAX_BYTES = 2 * 1024 * 1024;

    private const MAX_MESSAGE_LENGTH = 600;

    private const MAX_DETAIL_LENGTH = 5000;

    /**
     * The latest entries, most recent first.
     *
     * @return list<array{timestamp: string, level: string, message: string, detail: string|null}>
     */
    public function latest(int $limit = 50): array
    {
        $path = config('logging.channels.single.path');

        if (! is_string($path) || ! is_file($path)) {
            return [];
        }

        $tail = $this->tail($path);

        preg_match_all(
            '/^\[(?<timestamp>\d{4}-\d{2}-\d{2}[T ][^\]]*)\] \w+\.(?<level>[A-Z]+): (?<message>.*)$/m',
            $tail,
            $headers,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER,
        );

        $entries = [];

        foreach ($headers as $index => $header) {
            /*
             * Tout ce qui suit la ligne d'en-tête jusqu'à l'en-tête suivant
             * est le corps de l'entrée — la pile d'appels, le plus souvent.
             */
            $bodyStart = $header[0][1] + strlen($header[0][0]);
            $bodyEnd = isset($headers[$index + 1]) ? $headers[$index + 1][0][1] : strlen($tail);
            $detail = trim(substr($tail, $bodyStart, $bodyEnd - $bodyStart));

            $entries[] = [
                'timestamp' => $header['timestamp'][0],
                'level' => $header['level'][0],
                'message' => Str::limit(trim($header['message'][0]), self::MAX_MESSAGE_LENGTH),
                'detail' => $detail === '' ? null : Str::limit($detail, self::MAX_DETAIL_LENGTH),
            ];
        }

        return array_reverse(array_slice($entries, -$limit));
    }

    /**
     * The last MAX_BYTES of the file. A cut that lands mid-entry is harmless:
     * the parser only recognises entries from their header line onwards.
     */
    private function tail(string $path): string
    {
        $size = (int) filesize($path);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            if ($size > self::MAX_BYTES) {
                fseek($handle, $size - self::MAX_BYTES);
            }

            return (string) stream_get_contents($handle);
        } finally {
            fclose($handle);
        }
    }
}
