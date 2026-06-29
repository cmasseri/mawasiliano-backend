<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\Operation;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
public function index()
{
    $totalPersonnel = Personnel::where('status', 'ACTIVE')->count();

    $communicationOperators = Personnel::where('status', 'ACTIVE')
        ->whereHas('currentTrade.trade', function ($q) {

            $q->where('name', 'like', 'Comm opp%');

        })
        ->count();

    $communicationTechnicians = Personnel::where('status', 'ACTIVE')
        ->whereHas('currentTrade.trade', function ($q) {

            $q->where('name', 'like', 'Comm tech%');

        })
        ->count();

    $commissionedOfficers = Personnel::where('status', 'ACTIVE')
        ->whereHas('currentPromotion.rank', function ($q) {

            $q->where('category', 'OFFICER');

        })
        ->count();

    return response()->json([

        'totalPersonnel'           => $totalPersonnel,

        'communicationOperators'   => $communicationOperators,

        'communicationTechnicians' => $communicationTechnicians,

        'commissionedOfficers'     => $commissionedOfficers

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
            ->take(2)
            ->get()
            ->map(function ($item) {

                return [

                    'type' => 'TRANSFER',

                    'title' => 'Personnel Transfer',

                    'description' => 'Transfer executed',

                    'date' => $item->created_at

                ];
            });

        return $transfers
            ->sortByDesc('date')
            ->values();
    }
}