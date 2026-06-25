<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log; 
use Illuminate\Http\Request;
use App\Models\Personnel;
use App\Models\Unit;

class ReportController extends Controller
{
    public function rankDistribution(Request $request)
    {
        $unitId = $request->unit_id;

        $query = Personnel::query();

        // Filter selected structure + all descendants
        if ($unitId) {

            $unitIds = $this->getAllChildren($unitId);

            $unitIds[] = (int) $unitId;

            $query->whereIn(
                'unit_id',
                $unitIds
            );
        }

        $ranks = $query
            ->join('promotions', function ($join) {

                $join->on(
                    'personnel.id',
                    '=',
                    'promotions.personnel_id'
                )
                ->where(
                    'promotions.is_current',
                    true
                );

            })
            ->join(
                'ranks',
                'promotions.rank_id',
                '=',
                'ranks.id'
            )
            ->selectRaw(
                '
                ranks.id,
                ranks.name,
                COUNT(*) as total
                '
            )
            ->groupBy(
                'ranks.id',
                'ranks.name'
            )
            ->orderByDesc('ranks.id')
            ->get();

        return response()->json($ranks);
    }

    /**
     * Get all child structures recursively
     */
    private function getAllChildren($parentId): array
    {
        $ids = [];

        $children = Unit::where(
            'parent_id',
            $parentId
        )->get();

        foreach ($children as $child) {

            $ids[] = $child->id;

            $ids = array_merge(
                $ids,
                $this->getAllChildren(
                    $child->id
                )
            );
        }

        return $ids;
    }


public function dashboard()
{
    $totalPersonnel = Personnel::count();

    $activePersonnel = Personnel::where('status', 'ACTIVE')->count();

    $totalUnits = Unit::count();


    return response()->json([
        'summary' => [
            'totalPersonnel'   => $totalPersonnel,
            'activePersonnel'  => $activePersonnel,
            'totalUnits'       => $totalUnits
        ]
    ]);
}


public function tradeDistribution(Request $request)
{
    $parentId = $request->unit_id;
    $tradeId  = $request->trade_id;

    $report = [];

    // ==========================
    // ENTIRE FORCE
    // ==========================
    if (!$parentId) {

        $units = Unit::where('parent_id', 1)
            ->orderBy('name')
            ->get();

    } else {

        $children = Unit::where('parent_id', $parentId)
            ->orderBy('name')
            ->get();

        // Kama hakuna watoto tumrudishe structure yenyewe
        if ($children->count() == 0) {

            $units = Unit::where('id', $parentId)->get();

        } else {

            $units = $children;

        }
    }

    foreach ($units as $unit) {

        $unitIds = $this->getAllChildren($unit->id);

        $unitIds[] = $unit->id;

        $query = Personnel::query()
            ->whereIn(
                'personnel.unit_id',
                $unitIds
            );

        // Trade filter
        if ($tradeId) {

            $query->join(
                'personnel_trades',
                function ($join) {

                    $join->on(
                        'personnel.id',
                        '=',
                        'personnel_trades.personnel_id'
                    )
                    ->where(
                        'personnel_trades.is_current',
                        true
                    );

                }
            );

            $query->where(
                'personnel_trades.trade_id',
                $tradeId
            );
        }

        $report[] = [

            'id'    => $unit->id,
            'name'  => $unit->name,
            'total' => $query->count()

        ];
    }

    return response()->json($report);
}


public function appointmentDistribution(Request $request)
{
    $parentId = $request->unit_id;

    // Entire Force
    if (!$parentId) {

        $unitIds = Unit::pluck('id')->toArray();

    } else {

        $unitIds = $this->getAllChildren($parentId);

        $unitIds[] = (int) $parentId;

    }

    $report = Personnel::query()

        ->join(
            'personnel_appointments',
            'personnel.id',
            '=',
            'personnel_appointments.personnel_id'
        )

        ->join(
            'appointments',
            'appointments.id',
            '=',
            'personnel_appointments.appointment_id'
        )

        ->where(
            'personnel_appointments.is_current',
            true
        )

        ->whereIn(
            'personnel.unit_id',
            $unitIds
        )

        ->selectRaw("
            appointments.id,
            appointments.name,
            COUNT(*) as total
        ")

        ->groupBy(
            'appointments.id',
            'appointments.name'
        )

        ->orderBy(
            'appointments.name'
        )

        ->get();

    return response()->json($report);
}


public function genderDistribution(Request $request)
{
    $unitId = $request->unit_id;

    $query = Personnel::query();

    // Filter selected structure + descendants
    if ($unitId) {

        $unitIds = $this->getAllChildren($unitId);

        $unitIds[] = (int) $unitId;

        $query->whereIn(
            'unit_id',
            $unitIds
        );
    }

    $report = $query

        ->selectRaw("
            gender,
            COUNT(*) as total
        ")

        ->groupBy(
            'gender'
        )

        ->orderBy(
            'gender'
        )

        ->get()

        ->map(function ($row) {

            return [

                'name' => ucfirst(strtolower($row->gender)),

                'total' => $row->total

            ];

        });

    return response()->json($report);
}



}