<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $aiServiceUrl;
    protected int $maxRetries = 3;
    protected int $timeout = 30;

    public function __construct()
    {
        $this->aiServiceUrl = config('services.ai_service.url', 'https://forecasting-production-cf4e.up.railway.app');
    }


    public function requestForecastSync(int $productId, ?string $region = null): array
    {
        $product = \App\Models\Product::with('sales')->findOrFail($productId);
        $salesData = $product->sales->map(function ($sale) {
            return [
                'date' => $sale->date->toDateString(),
                'quantity' => $sale->quantity,
                'revenue' => $sale->revenue,
                'region' => $sale->region,
            ];
        })->toArray();

        $payload = [
            'product_id' => $productId,
            'region' => $region,
            'sales_data' => $salesData,
        ];

        return $this->makeRequestWithRetry('POST', '/forecast', $payload);
    }

 
    public function getInsightsSync(int $userId, $payload = []): array
    {

        return $this->makeRequestWithRetry('POST', '/insights', $payload );
    }

    public function getRecommendationsSync(int $userId): array
    {
        $payload = ['user_id' => $userId];
        return $this->makeRequestWithRetry('GET', '/api/recommendations', $payload);
    }


    private function makeRequestWithRetry(string $method, string $endpoint, array $payload = []): array
    {
        $attempts = 0;

        while ($attempts < $this->maxRetries) {
            try {
                $attempts++;

                Log::info("AI Service Request Attempt {$attempts}", [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'payload' => $payload
                ]);

                $httpClient = Http::timeout($this->timeout);

                if ($method === 'GET') {
                    $response = $httpClient->get("{$this->aiServiceUrl}{$endpoint}", $payload);
                } else {
                    $response = $httpClient->post("{$this->aiServiceUrl}{$endpoint}", $payload);
                }

                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('AI Service Response Success', ['data' => $data]);
                    return $data;
                } else {
                    Log::warning('AI Service Response Error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    if ($response->status() >= 500 && $attempts < $this->maxRetries) {
              
                        sleep(pow(2, $attempts)); 
                        continue;
                    }

                    return $this->getFallbackResponse($endpoint);
                }

            } catch (\Exception $e) {
                Log::error('AI Service Request Exception', [
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                    'endpoint' => $endpoint
                ]);

                if ($attempts < $this->maxRetries) {
                    sleep(pow(2, $attempts));
                    continue;
                }

                return $this->getFallbackResponse($endpoint);
            }
        }

        return $this->getFallbackResponse($endpoint);
    }

    /**
     * Get fallback response when AI service is unavailable
     */
    private function getFallbackResponse(string $endpoint): array
    {
        Log::warning('Using fallback response for AI service', ['endpoint' => $endpoint]);

        if (str_contains($endpoint, 'forecast')) {
            return [
                'forecasts' => [],
                'model_version' => 'fallback',
                'message' => 'AI service temporarily unavailable'
            ];
        }

        if (str_contains($endpoint, 'insights')) {
            return [
                'insights' => [],
                'message' => 'AI service temporarily unavailable'
            ];
        }

        if (str_contains($endpoint, 'recommendations')) {
            return [
                'recommendations' => [],
                'message' => 'AI service temporarily unavailable'
            ];
        }

        return ['error' => 'Unknown endpoint'];
    }

    /**
     * Legacy methods for backward compatibility (deprecated)
     */
    public function requestForecast(int $productId, ?string $region = null): array
    {
        return $this->requestForecastSync($productId, $region);
    }

    public function getInsights(int $userId): array
    {
        return $this->getInsightsSync($userId);
    }

    public function getRecommendations(int $userId): array
    {
        return $this->getRecommendationsSync($userId);
    }
}