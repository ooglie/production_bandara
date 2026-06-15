<?php

namespace App\Services;

use App\Models\CustomerAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DeliveryDistanceService
{
    public function routeForAddress(?CustomerAddress $address): array
    {
        if (! $address) {
            return $this->failure('No delivery address selected.');
        }

        if (! (bool) config('delivery.distance_enabled', false)) {
            return $this->failure('Distance-based delivery is disabled.', 'disabled');
        }

        if (config('delivery.distance_provider', 'google') !== 'google') {
            return $this->failure('Unsupported delivery distance provider.', 'unsupported_provider');
        }

        $apiKey = (string) config('delivery.google_maps_api_key', '');
        if ($apiKey === '') {
            return $this->failure('Google Maps API key is not configured.', 'missing_api_key');
        }

        $origin = $this->origin();
        if ($origin === '') {
            return $this->failure('Store delivery origin is not configured.', 'missing_origin');
        }

        $destination = $this->destination($address);
        if ($destination === '') {
            return $this->failure('Customer address is incomplete.', 'missing_destination');
        }

        try {
            $response = Http::timeout((int) config('delivery.distance_timeout_seconds', 6))
                ->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                    'origins' => $origin,
                    'destinations' => $destination,
                    'mode' => 'driving',
                    'units' => 'metric',
                    'key' => $apiKey,
                ]);

            if (! $response->successful()) {
                return $this->failure('Distance provider did not respond successfully.', 'provider_http_error');
            }

            $payload = $response->json();
            $status = (string) ($payload['status'] ?? '');
            if ($status !== 'OK') {
                return $this->failure('Distance provider returned status: ' . ($status ?: 'unknown'), 'provider_status_error');
            }

            $element = $payload['rows'][0]['elements'][0] ?? null;
            $elementStatus = (string) ($element['status'] ?? '');
            if (! $element || $elementStatus !== 'OK') {
                return $this->failure('Route distance is not available for this address.', 'route_unavailable');
            }

            $meters = (int) ($element['distance']['value'] ?? 0);
            $seconds = (int) ($element['duration']['value'] ?? 0);
            if ($meters <= 0) {
                return $this->failure('Route distance was returned as zero.', 'zero_distance');
            }

            $this->geocodeIfNeeded($address);

            return [
                'success' => true,
                'status' => 'ok',
                'provider' => 'google',
                'distance_km' => round($meters / 1000, 2),
                'duration_minutes' => $seconds > 0 ? (int) ceil($seconds / 60) : null,
                'calculated_at' => now(),
                'message' => null,
            ];
        } catch (Throwable $e) {
            report($e);
            return $this->failure('Distance calculation failed. Falling back to delivery zone if available.', 'exception');
        }
    }

    private function origin(): string
    {
        $lat = config('delivery.store_origin_lat');
        $lng = config('delivery.store_origin_lng');

        if ($lat !== null && $lat !== '' && $lng !== null && $lng !== '') {
            return trim((string) $lat) . ',' . trim((string) $lng);
        }

        return trim((string) config('delivery.store_origin_address', ''));
    }

    private function destination(CustomerAddress $address): string
    {
        if ($address->latitude !== null && $address->longitude !== null) {
            return $address->latitude . ',' . $address->longitude;
        }

        return collect([
            $address->address_line1,
            $address->address_line2,
            $address->city ?: 'Pune',
            $address->state ?: 'Maharashtra',
            $address->pincode,
            $address->country ?: 'India',
        ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');
    }

    private function geocodeIfNeeded(CustomerAddress $address): void
    {
        if ($address->latitude !== null && $address->longitude !== null) {
            return;
        }

        if (! Schema::hasColumn('customer_addresses', 'latitude') || ! Schema::hasColumn('customer_addresses', 'longitude')) {
            return;
        }

        $apiKey = (string) config('delivery.google_geocoding_api_key', '');
        if ($apiKey === '') {
            return;
        }

        $destination = collect([
            $address->address_line1,
            $address->address_line2,
            $address->city ?: 'Pune',
            $address->state ?: 'Maharashtra',
            $address->pincode,
            $address->country ?: 'India',
        ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');

        if ($destination === '') {
            return;
        }

        try {
            $response = Http::timeout((int) config('delivery.distance_timeout_seconds', 6))
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $destination,
                    'key' => $apiKey,
                ]);

            if (! $response->successful()) {
                return;
            }

            $payload = $response->json();
            if (($payload['status'] ?? null) !== 'OK') {
                return;
            }

            $result = $payload['results'][0] ?? null;
            $location = $result['geometry']['location'] ?? null;
            if (! isset($location['lat'], $location['lng'])) {
                return;
            }

            $address->forceFill([
                'latitude' => round((float) $location['lat'], 7),
                'longitude' => round((float) $location['lng'], 7),
                'geocoded_at' => now(),
                'geocoding_provider' => 'google',
                'geocoding_quality' => $result['geometry']['location_type'] ?? null,
            ])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function failure(string $message, string $status = 'failed'): array
    {
        return [
            'success' => false,
            'status' => $status,
            'provider' => config('delivery.distance_provider', 'google'),
            'distance_km' => null,
            'duration_minutes' => null,
            'calculated_at' => null,
            'message' => $message,
        ];
    }
}
