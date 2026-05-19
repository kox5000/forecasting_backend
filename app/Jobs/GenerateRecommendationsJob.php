<?php

namespace App\Jobs;

use App\Models\Recommendation;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationsJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected int $recommendationId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $recommendationId)
    {
        $this->recommendationId = $recommendationId;
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        $recommendation = Recommendation::find($this->recommendationId);

        if (!$recommendation) {
            Log::error('Recommendation not found', ['recommendation_id' => $this->recommendationId]);
            return;
        }

        $recommendation->update([
            'status' => 'processing',
            'started_at' => now(),
            'job_id' => $this->job->getJobId(),
        ]);

        try {
            $userId = $recommendation->user_id;
            $result = $aiService->getRecommendationsSync($userId);

            $recommendation->update([
                'status' => 'completed',
                'input_data' => ['user_id' => $userId],
                'result' => $result,
                'model_version' => 'v1.0',
                'completed_at' => now(),
            ]);

            Log::info('Recommendations generated successfully', ['recommendation_id' => $this->recommendationId]);

        } catch (\Exception $e) {
            $recommendation->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            Log::error('Recommendations generation failed', [
                'recommendation_id' => $this->recommendationId,
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
        $recommendation = Recommendation::find($this->recommendationId);
        if ($recommendation) {
            $recommendation->update(['status' => 'failed']);
        }

        Log::error('GenerateRecommendationsJob failed', [
            'recommendation_id' => $this->recommendationId,
            'error' => $exception->getMessage()
        ]);
    }
}
