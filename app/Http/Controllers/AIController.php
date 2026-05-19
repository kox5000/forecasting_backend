<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateForecastJob;
use App\Jobs\GenerateInsightsJob;
use App\Jobs\GenerateRecommendationsJob;
use App\Models\Forecast;
use App\Models\Insight;
use App\Models\Product;
use App\Models\User;
use App\Models\Recommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    public function forecast(Request $request)
    {
  
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'region' => 'nullable|string',
        ]);
        

        $productId = $request->product_id;
        $region = $request->region;
       

        $product = Product::find($productId);

        if (!$product || $product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $forecast = Forecast::create([
            'product_id' => $productId,
            'region' => $region,
            'status' => 'pending',
        ]);
        
    


        GenerateForecastJob::dispatch($forecast->id);

        Log::info('Forecast job dispatched', ['forecast_id' => $forecast->id, 'user_id' => auth()->id()]);

        return response()->json([
            'message' => 'Forecast request queued for processing',
            'forecast_id' => $forecast->id,
            'status' => 'pending',
        ], 202);

    }

    public function insights()
    {
        $insight = Insight::create([
            'description' => '',
            'title' =>'test',
            'type' => 'blah',
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);
        //here my code 

        $id = auth()->id();
       
        $products = Product::with(['sales'=> function($query){
        $query->select('product_id','date','quantity', 'revenue',);
        } 
        ] )->where('user_id', $id)->get();

        $salesData = $products->flatMap(function($product){
            return $product->sales->map(function($sale){
                return [
                    'product_id' => $sale->product_id,
                    'date' => $sale->date,
                    'quantity' => $sale->quantity,
                    'revenue' => $sale->revenue,
                ];
            });
        });

   
        $payload = ['sales_data' => $salesData ];

        GenerateInsightsJob::dispatch($insight->id,$salesData);

        Log::info('Insights job dispatched', ['insight_id' => $insight->id, 'user_id' => auth()->id()]);

        return response()->json([
            'message' => 'Insights request queued for processing',
            'insight_id' => $insight->id,
            'status' => 'pending',
            'payload' => $salesData,
        ], 202);
    }

    public function recommendations()
    {
        $recommendation = Recommendation::create([
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        GenerateRecommendationsJob::dispatch($recommendation->id);

        Log::info('Recommendations job dispatched', ['recommendation_id' => $recommendation->id, 'user_id' => auth()->id()]);

        return response()->json([
            'message' => 'Recommendations request queued for processing',
            'recommendation_id' => $recommendation->id,
            'status' => 'pending',
        ], 202);
    }

    public function forecastStatus(int $id)
    {
        $forecast = Forecast::find($id);
        if (!$forecast || $forecast->product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $forecast->id,
            'status' => $forecast->status,
            'result' => $forecast->result['predictions'],
            'started_at' => $forecast->started_at,
            'completed_at' => $forecast->completed_at,
        ]);
    }

    public function insightsStatus(int $id)
    {
        $insight = Insight::find($id);
        if (!$insight || $insight->user_id !== auth()->id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $insight->id,
            'status' => $insight->status,
            'result' => $insight->result,
            'started_at' => $insight->started_at,
            'completed_at' => $insight->completed_at,
        ]);
    }

    public function recommendationsStatus(int $id)
    {
        $recommendation = Recommendation::find($id);
        if (!$recommendation || $recommendation->user_id !== auth()->id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $recommendation->id,
            'status' => $recommendation->status,
            'result' => $recommendation->result,
            'started_at' => $recommendation->started_at,
            'completed_at' => $recommendation->completed_at,
        ]);
    }
}
