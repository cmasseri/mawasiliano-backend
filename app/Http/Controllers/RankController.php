<?php

namespace App\Http\Controllers;

use App\Models\Rank;
use Illuminate\Http\Request;

class RankController extends Controller
{
    // =============================
    // 🔹 GET ALL RANKS
    // =============================
    public function index(Request $request)
    {
        $query = Rank::query();

        // 🔥 filter kwa category (optional)
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // 🔥 sort kwa level
        $query->orderBy('category')
              ->orderBy('level');

        return response()->json($query->get());
    }
}