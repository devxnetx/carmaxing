<?php

namespace App\Support;

class ApiDocsPhpExample
{
    public static function build(
        string $method,
        string $url,
        ?array $body = null,
        ?array $query = null,
        string $apiKeyPlaceholder = 'ac_YOUR_API_KEY',
    ): string {
        $method = strtoupper($method);
        $requestUrl = self::urlWithQuery($url, $query);
        $token = $apiKeyPlaceholder;

        $lines = [
            '<?php',
            '',
            'use Illuminate\\Support\\Facades\\Http;',
            '',
        ];

        $chain = "Http::withToken('{$token}')\n    ->acceptJson()";

        if ($method === 'GET') {
            $lines[] = '$response = '.$chain;
            $lines[] = "    ->get('{$requestUrl}');";
        } elseif ($method === 'POST') {
            $payload = self::exportPhpArray($body ?? []);
            $lines[] = '$payload = '.$payload.';';
            $lines[] = '';
            $lines[] = '$response = '.$chain;
            $lines[] = "    ->post('{$requestUrl}', \$payload);";
        } elseif ($method === 'PUT') {
            $payload = self::exportPhpArray($body ?? []);
            $lines[] = '$payload = '.$payload.';';
            $lines[] = '';
            $lines[] = '$response = '.$chain;
            $lines[] = "    ->put('{$requestUrl}', \$payload);";
        } else {
            $lines[] = '$response = '.$chain;
            $lines[] = "    ->send('{$method}', '{$requestUrl}');";
        }

        $lines[] = '';
        $lines[] = '$response->throw();';
        $lines[] = '';
        $lines[] = '$data = $response->json();';

        return implode("\n", $lines);
    }

    private static function urlWithQuery(string $url, ?array $query): string
    {
        if ($query === null || $query === []) {
            return $url;
        }

        return $url.'?'.http_build_query($query);
    }

    private static function exportPhpArray(array $data, int $depth = 0): string
    {
        if ($data === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth + 1);
        $closing = str_repeat('    ', $depth);
        $items = [];

        foreach ($data as $key => $value) {
            $exportedKey = is_int($key) ? $key : "'".addslashes((string) $key)."'";

            if (is_array($value)) {
                $items[] = $indent.$exportedKey.' => '.self::exportPhpArray($value, $depth + 1);
            } elseif (is_bool($value)) {
                $items[] = $indent.$exportedKey.' => '.($value ? 'true' : 'false');
            } elseif (is_int($value) || is_float($value)) {
                $items[] = $indent.$exportedKey.' => '.$value;
            } elseif ($value === null) {
                $items[] = $indent.$exportedKey.' => null';
            } else {
                $items[] = $indent.$exportedKey." => '".addslashes((string) $value)."'";
            }
        }

        return "[\n".implode(",\n", $items).",\n".$closing.']';
    }
}