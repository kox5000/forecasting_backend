<?php

namespace App\Jobs;

use App\Models\Forecast;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateForecastJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected int $forecastId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $forecastId)
    {
        $this->forecastId = $forecastId;
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        $forecast = Forecast::find($this->forecastId);

        if (!$forecast) {
            Log::error('Forecast not found', ['forecast_id' => $this->forecastId]);
            return;
        }

        $forecast->update([
            'status' => 'processing',
            'started_at' => now(),
            'job_id' => $jobId = $this->job?->getJobId(),
        ]);

        try {
            $product = $forecast->product;
            $salesData = $product->sales->map(function ($sale) {
                return [
                    'date' => $sale->date->toDateString(),
                    'quantity' => $sale->quantity,
                    'revenue' => $sale->revenue,
                    'region' => $sale->region,
                ];
            })->toArray();

            $inputData = [
                'product_id' => $product->id,
                'region' => $forecast->region,
                'sales_data' => $salesData,
            ];

            $result = $aiService->requestForecastSync($product->id, $forecast->region);

            $forecast->update([
                'status' => 'completed',
                'input_data' => $inputData,
                'result' => $result,
                'model_version' => $result['model_version'] ?? 'v1.0',
                'completed_at' => now(),
            ]);

            Log::info('Forecast generated successfully', ['forecast_id' => $this->forecastId]);

        } catch (\Exception $e) {
            $forecast->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            Log::error('Forecast generation failed', [
                'forecast_id' => $this->forecastId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $forecast = Forecast::find($this->forecastId);
        if ($forecast) {
            $forecast->update(['status' => 'failed']);
        }

        Log::error('GenerateForecastJob failed', [
            'forecast_id' => $this->forecastId,
            'error' => $exception->getMessage()
        ]);
    }
}
