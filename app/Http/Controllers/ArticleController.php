<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\NewsAggregatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(private NewsAggregatorService $newsAggregatorService) {}

    public function index(Request $request)
    {
        $query = Article::query();

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }
        if ($request->has('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        // **Filter by Date Range**
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('published_at', [$startDate, $endDate]);
        } elseif ($request->has('start_date')) { // Filter by a single date
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->whereDate('published_at', $startDate);
        }

        return response()->json($query->latest()->paginate(10));
    }
}
