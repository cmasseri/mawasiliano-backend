<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
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


public function educationFields(Request $request)
{
    $qualification = $request->qualification;
    $keyword = $request->keyword;

    $query =DB::table('personnel_education')
        ->select('field_of_study')
        ->whereNotNull('field_of_study');

    if ($qualification) {

        $query->where(
            'qualification',
            $qualification
        );

    }

    if ($keyword) {

        $query->where(
            'field_of_study',
            'like',
            "%{$keyword}%"
        );

    }

return response()->json(

    $query
        ->distinct()
        ->orderBy('field_of_study')
        ->limit(20)
        ->pluck('field_of_study')

);
}


public function personnelByEducation(Request $request)
{
    $structureId   = $request->structure_id;
    $qualification = $request->qualification;
    $field         = $request->field_of_study;

    $query = Personnel::query()

        ->join(
            'personnel_education',
            'personnel.id',
            '=',
            'personnel_education.personnel_id'
        )

        ->leftJoin(
            'units',
            'personnel.unit_id',
            '=',
            'units.id'
        )

        ->leftJoin(
            'promotions',
            function($join){

                $join->on(
                    'personnel.id',
                    '=',
                    'promotions.personnel_id'
                )
                ->where(
                    'promotions.is_current',
                    true
                );

            }
        )

        ->leftJoin(
            'ranks',
            'promotions.rank_id',
            '=',
            'ranks.id'
        );

    // ==========================
    // STRUCTURE FILTER
    // ==========================

    if($structureId){

        $ids = $this->getAllChildren($structureId);

        $ids[] = (int)$structureId;

        $query->whereIn(
            'personnel.unit_id',
            $ids
        );

    }

    // ==========================
    // QUALIFICATION
    // ==========================

    if($qualification){

 $query->where(
    'personnel_education.qualification',
    $qualification
);

    }

    // ==========================
    // FIELD
    // ==========================

    if($field){

        $query->where(
            'personnel_education.field_of_study',
            'like',
            "%{$field}%"
        );

    }

    return response()->json(

        $query

        ->select(

            'personnel.id as personnel_id',

            'personnel.service_number',

            'personnel.full_name',

            'ranks.name as rank_name',

            'personnel_education.qualification',

            'personnel_education.field_of_study',

            'personnel_education.institution',

            'personnel_education.year_completion',

            'units.name as unit_name'

        )

->orderBy('personnel.full_name')
->paginate(
    $request->per_page ?? 20
)
);
}

}