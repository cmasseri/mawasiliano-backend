<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    // =============================
    // 🔹 GET ALL TRADES
    // =============================
    public function index(Request $request)
    {
        $query = Trade::query();

        // 🔥 filter kwa category
        if ($request->has('category')) {
            $category = $request->category;

            // BOTH pia ijumlishwe
            $query->where(function ($q) use ($category) {
                $q->where('category', $category)
                  ->orWhere('category', 'BOTH');
            });
        }

        return response()->json($query->get());
    }
}