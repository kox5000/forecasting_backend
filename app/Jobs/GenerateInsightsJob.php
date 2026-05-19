<?php

namespace App\Jobs;

use App\Models\Insight;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInsightsJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected int $insightId;
    public  $salesData;

    /**
     * Create a new job instance.
     */
    public function __construct(int $insightId ,  $salesData)
    {
        $this->insightId = $insightId;
        $this->salesData = $salesData;
    }

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        $insight = Insight::find($this->insightId);

        if (!$insight) {
            Log::error('Insight not found', ['insight_id' => $this->insightId]);
            return;
        }

        $insight->update([
            'status' => 'processing',
            'started_at' => now(),
            'job_id' => $this->job->getJobId(),
        ]);

        

        try {
            $SalesDataVariabel = $this->salesData->toArray();
            // $SalesDataVariabel = ['test'];
            $userId = $insight->user_id;
            $result = $aiService->getInsightsSync($userId, $SalesDataVariabel);

            $insight->update([
                'status' => 'completed',
                'input_data' => ['user_id' => $userId],
                'result' => $result,
                'model_version' => 'v1.0',
                'completed_at' => now(),
            ]);

            Log::info('Insights generated successfully', ['insight_id' => $this->insightId]);

        } catch (\Exception $e) {
            $insight->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            Log::error('Insights generation failed', [
                'insight_id' => $this->insightId,
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
        $insight = Insight::find($this->insightId);
        if ($insight) {
            $insight->update(['status' => 'failed']);
        }

        Log::error('GenerateInsightsJob failed', [
            'insight_id' => $this->insightId,
            'error' => $exception->getMessage()
        ]);
    }
}
