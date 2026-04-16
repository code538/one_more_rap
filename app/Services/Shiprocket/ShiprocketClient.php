<?php

namespace App\Services\Shiprocket;

use App\Models\Website\ShiprocketSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ShiprocketClient
{
    private function activeSetting(): ?ShiprocketSetting
    {
        return ShiprocketSetting::where('status', 1)->latest()->first();
    }

    private function baseUrl(): string
    {
        $setting = $this->activeSetting();
        $url = $setting?->base_url ?: (string) config('shiprocket.base_url');
        return rtrim($url, '/');
    }

    /**
     * Returns a bearer token, cached for configured minutes.
     */
    public function token(): string
    {
        $setting = $this->activeSetting();
        $cacheKey = 'shiprocket:token:' . ($setting?->id ?: 'env');
        $ttlMinutes = (int) ($setting?->token_cache_minutes ?: config('shiprocket.token_cache_minutes', 720));

        return Cache::remember($cacheKey, now()->addMinutes($ttlMinutes), function () {
            $setting = $this->activeSetting();

            $mode = $setting?->mode ?: 'test';
            $email = $mode === 'live'
                ? (string) ($setting?->live_email ?: config('shiprocket.email'))
                : (string) ($setting?->test_email ?: config('shiprocket.email'));
            $password = $mode === 'live'
                ? (string) ($setting?->live_password ?: config('shiprocket.password'))
                : (string) ($setting?->test_password ?: config('shiprocket.password'));

            if ($email === '' || $password === '') {
                throw new \RuntimeException('Shiprocket credentials are not configured. Please set an active Shiprocket setting in DB.');
            }

            $res = Http::baseUrl($this->baseUrl())
                ->acceptJson()
                ->asJson()
                ->post('/auth/login', [
                    'email' => $email,
                    'password' => $password,
                ]);

            $res->throw();

            $json = $res->json();
            $token = $json['token'] ?? null;
            if (!is_string($token) || $token === '') {
                throw new \RuntimeException('Shiprocket token missing in response.');
            }

            return $token;
        });
    }

    /**
     * Generic Shiprocket request helper.
     *
     * @throws RequestException
     */
    public function request(string $method, string $path, array $payload = []): array
    {
        $method = strtoupper($method);

        $http = Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->withToken($this->token());

        $res = match ($method) {
            'GET' => $http->get($path, $payload),
            'POST' => $http->post($path, $payload),
            'PUT' => $http->put($path, $payload),
            'PATCH' => $http->patch($path, $payload),
            'DELETE' => $http->delete($path, $payload),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        $res->throw();

        $json = $res->json();
        return is_array($json) ? $json : ['raw' => $json];
    }

    /**
     * Create a Shiprocket order/shipment.
     * Docs: POST /orders/create/adhoc
     */
    public function createAdhocOrder(array $payload): array
    {
        return $this->request('POST', '/orders/create/adhoc', $payload);
    }

    /**
     * Generate AWB for a shipment.
     * Docs: POST /courier/assign/awb
     */
    public function generateAwb(int $shipmentId, int $courierCompanyId): array
    {
        return $this->request('POST', '/courier/assign/awb', [
            'shipment_id' => $shipmentId,
            'courier_company_id' => $courierCompanyId,
        ]);
    }

    /**
     * Track by AWB.
     * Docs: GET /courier/track/awb/{awb}
     */
    public function trackByAwb(string $awb): array
    {
        $awb = trim($awb);
        return $this->request('GET', '/courier/track/awb/' . urlencode($awb));
    }

    /**
     * List pickup locations (helpful for setup validation).
     * Docs: GET /settings/company/pickup
     */
    public function pickupLocations(): array
    {
        return $this->request('GET', '/settings/company/pickup');
    }
}

