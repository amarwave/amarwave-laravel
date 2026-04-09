<?php

declare(strict_types=1);

namespace AmarWave\Laravel;

/**
 * AmarWave HTTP client — standalone, no external PHP SDK dependency.
 *
 * Registered as a singleton by AmarWaveServiceProvider and resolved
 * by the AmarWave facade and AmarWaveBroadcaster.
 */
class AmarWaveClient
{
    private readonly string $resolvedBaseUrl;

    /** @var array<string, string> */
    private static array $clusterApis = [
        'default' => 'https://amarwave.com',
        'local'   => 'https://amarwave.com',
        'eu'      => 'https://amarwave.com',
        'us'      => 'https://amarwave.com',
        'ap1'     => 'https://amarwave.com',
        'ap2'     => 'https://amarwave.com',
    ];

    public function __construct(
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly string $cluster = 'default',
        private readonly int $timeout = 10,
    ) {
        $this->resolvedBaseUrl = self::$clusterApis[$cluster] ?? 'https://amarwave.com';
    }

    // -------------------------------------------------------------------------
    // Event Triggering
    // -------------------------------------------------------------------------

    /**
     * Trigger a single event on a channel.
     *
     * @param  mixed $data
     * @return array
     *
     * @throws AmarWaveException
     */
    public function trigger(string $channel, string $event, mixed $data = null): array
    {
        $body = json_encode([
            'channel' => $channel,
            'event'   => $event,
            'data'    => is_array($data) ? json_encode($data) : $data,
        ], JSON_THROW_ON_ERROR);

        return $this->post('/api/events', $body);
    }

    /**
     * Trigger multiple events in a single request.
     *
     * @param  array<int, array{channel: string, event: string, data: mixed}> $events
     * @return array
     *
     * @throws AmarWaveException
     */
    public function triggerBatch(array $events): array
    {
        $batch = array_map(static function (array $e): array {
            return [
                'channel' => $e['channel'],
                'event'   => $e['event'],
                'data'    => isset($e['data']) && is_array($e['data'])
                    ? json_encode($e['data'])
                    : ($e['data'] ?? null),
            ];
        }, $events);

        $body = json_encode(['batch' => $batch], JSON_THROW_ON_ERROR);

        return $this->post('/api/batch_events', $body);
    }

    // -------------------------------------------------------------------------
    // Channel Authentication
    // -------------------------------------------------------------------------

    /**
     * Generate an auth token for a private channel.
     *
     * @return string  "{appKey}:{HMAC-SHA256 signature}"
     */
    public function authenticate(string $socketId, string $channel): string
    {
        $signature = hash_hmac('sha256', "{$socketId}:{$channel}", $this->appSecret);

        return "{$this->appKey}:{$signature}";
    }

    /**
     * Generate an auth token for a presence channel.
     *
     * @param  array<string, mixed> $channelData
     * @return array{auth: string, channel_data: string}
     *
     * @throws AmarWaveException
     */
    public function authenticatePresence(string $socketId, string $channel, array $channelData): array
    {
        $channelDataJson = json_encode($channelData, JSON_THROW_ON_ERROR);
        $signature       = hash_hmac('sha256', "{$socketId}:{$channel}:{$channelDataJson}", $this->appSecret);

        return [
            'auth'         => "{$this->appKey}:{$signature}",
            'channel_data' => $channelDataJson,
        ];
    }

    // -------------------------------------------------------------------------
    // HTTP
    // -------------------------------------------------------------------------

    /**
     * @throws AmarWaveException
     */
    private function post(string $path, string $body): array
    {
        $url  = $this->resolvedBaseUrl . $path;
        $opts = [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($body),
                'Authorization: ' . $this->buildAuthHeader('POST', $path, $body),
            ],
        ];

        if (str_starts_with($url, 'https')) {
            $opts[CURLOPT_SSL_VERIFYPEER] = true;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $opts);

        $response   = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new AmarWaveException("Connection error: {$curlError}");
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AmarWaveException(
                "AmarWave API returned HTTP {$statusCode}",
                $statusCode,
                (string) $response,
            );
        }

        $decoded = json_decode((string) $response, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Build an HMAC-signed Authorization header value.
     */
    private function buildAuthHeader(string $method, string $path, string $body): string
    {
        $timestamp    = (string) time();
        $bodyMd5      = md5($body);
        $stringToSign = implode("\n", [$method, $path, $timestamp, $bodyMd5]);
        $signature    = hash_hmac('sha256', $stringToSign, $this->appSecret);

        return "AmarWave key={$this->appKey}, ts={$timestamp}, md5={$bodyMd5}, sig={$signature}";
    }
}
