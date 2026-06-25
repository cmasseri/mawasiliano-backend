<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
public function search(Request $request)
{
    $query = $request->get('q');

    $units = Unit::query()

        ->whereIn('type', [
            'UNIT',
            'RESERVE_DISTRICT'
        ])

        ->when($query, function ($q) use ($query) {

            $q->where('name', 'like', "%{$query}%");

        })

        ->orderBy('name')

        ->limit(20)

        ->get([
            'id',
            'name',
            'type'
        ]);

    return response()->json($units);
}
}