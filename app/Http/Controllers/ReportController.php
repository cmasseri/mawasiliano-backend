<?php

namespace App\Http\Controllers;

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
}