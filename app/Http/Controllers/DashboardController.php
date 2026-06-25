<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Unit;
use App\Models\Transfer;
use App\Models\Personnel;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



class DashboardController extends Controller
{
  public function index()
{
    return response()->json([

        'totalPersonnel' =>
            Personnel::count(),

        'activePersonnel' =>
            Personnel::where(
                'status',
                'ACTIVE'
            )->count(),

        'totalUnits' =>
            Unit::count(),

        'activeOperations' =>
            Operation::where(
                'status',
                'ONGOING'
            )->count(),

        'totalUsers' =>
            User::count(),

        'administrators' =>
            User::where(
                'role',
                'ADMINISTRATOR'
            )->count(),

        'operators' =>
            User::where(
                'role',
                'OPERATOR'
            )->count(),

        'transfers' =>
            Transfer::count()

    ]);
}


public function personnelStatus()
{
    return Personnel::select(
        'status',
        DB::raw('COUNT(*) as total')
    )
    ->groupBy('status')
    ->get();
}

public function operationsStatus()
{
    return Operation::select(
        'status',
        DB::raw('COUNT(*) as total')
    )
    ->groupBy('status')
    ->get();
}

public function personnelByUnit()
{
    return Unit::join(
            'personnel',
            'units.id',
            '=',
            'personnel.unit_id'
        )
        ->select(
            'units.name',
            DB::raw('COUNT(personnel.id) as total')
        )
        ->groupBy(
            'units.id',
            'units.name'
        )
        ->orderByDesc('total')
        ->limit(10)
        ->get();
}

public function recentActivities()
{
    $transfers = Transfer::latest()
        ->take(5)
        ->get()
        ->map(function ($item) {

            return [

                'type' => 'TRANSFER',

                'title' => 'Personnel Transfer',

                'description' =>
                    'Transfer executed',

                'date' =>
                    $item->created_at
            ];
        });

    return $transfers
        ->sortByDesc('date')
        ->values();
}
}
