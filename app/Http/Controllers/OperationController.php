<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OperationController extends Controller
{
    public function index()
    {
        return response()->json(
            Operation::latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'type' => 'nullable|string|max:191',
            'location' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = $this->calculateStatus(
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $operation = Operation::create($validated);

        Log::info('OPERATION CREATED', [
            'operation_id' => $operation->id,
            'name' => $operation->name,
            'type' => $operation->type,
            'status' => $operation->status
        ]);

        return response()->json($operation, 201);
    }

    public function show($id)
    {
        return response()->json(
            Operation::findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $operation = Operation::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'type' => 'nullable|string|max:191',
            'location' => 'nullable|string|max:191',
            'country' => 'nullable|string|max:191',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = $this->calculateStatus(
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $operation->update($validated);

        Log::info('OPERATION UPDATED', [
            'operation_id' => $operation->id,
            'name' => $operation->name,
            'type' => $operation->type,
            'status' => $operation->status
        ]);

        return response()->json($operation);
    }

public function destroy($id)
{
    $operation = Operation::findOrFail($id);

    $hasPersonnel = DB::table('personnel_operations')
        ->where('operation_id', $id)
        ->exists();

    if ($hasPersonnel) {

        return response()->json([

            'message' =>
                'Cannot delete operation because personnel are assigned.'

        ], 422);

    }

    Log::warning('OPERATION DELETED', [

        'operation_id' => $operation->id,

        'name' => $operation->name

    ]);

    $operation->delete();

    return response()->json([

        'message' => 'Operation deleted successfully'

    ]);
}

    private function calculateStatus($startDate, $endDate): string
    {
        $today = now()->toDateString();

        if (!$startDate || !$endDate) {
            return 'PLANNED';
        }

        if ($today < $startDate) {
            return 'PLANNED';
        }

        if ($today >= $startDate && $today <= $endDate) {
            return 'ONGOING';
        }

        return 'COMPLETED';
    }

public function personnelOps($id)
{
    $personnel = DB::table('personnel')

        // Current Trade
        ->leftJoin('personnel_trades', function ($join) {
            $join->on('personnel.id', '=', 'personnel_trades.personnel_id')
                 ->where('personnel_trades.is_current', 1);
        })
        ->leftJoin('trades', 'personnel_trades.trade_id', '=', 'trades.id')

        // Current Rank
        ->leftJoin('promotions', function ($join) {
            $join->on('personnel.id', '=', 'promotions.personnel_id')
                 ->where('promotions.is_current', 1);
        })
        ->leftJoin('ranks', 'promotions.rank_id', '=', 'ranks.id')

        ->where('personnel.unit_id', $id)

        ->where('personnel.status', 'ACTIVE')
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('personnel_operations')
                ->join(
                    'operations',
                    'personnel_operations.operation_id',
                    '=',
                    'operations.id'
                )
                ->whereColumn(
                    'personnel_operations.personnel_id',
                    'personnel.id'
                )
                ->where('operations.status', 'ONGOING');
        })

        ->select(
            'personnel.id',
            'personnel.service_number',
            'personnel.full_name',
            'ranks.name as rank_name',
            'trades.name as trade_name'
        )

        ->orderBy('personnel.full_name')
        ->get();

    return response()->json($personnel);
}

public function assignPersonnel(Request $request)
{
    Log::info('ASSIGN PERSONNEL START', [
        'request' => $request->all()
    ]);

    $request->validate([

        'operation_id' => 'required|exists:operations,id',

        'personnel' => 'required|array|min:1',

        'personnel.*.personnel_id' =>
            'required|exists:personnel,id',

        'personnel.*.role' =>
            'required|string'

    ]);

    try {

        $operation = DB::table('operations')
            ->where('id', $request->operation_id)
            ->first();


if ($operation->status === 'COMPLETED') {

    return response()->json([

        'success' => false,

        'message' => 'Cannot assign personnel to a completed operation.'

    ], 422);

}

DB::transaction(function () use ($request, $operation) {


        foreach ($request->personnel as $item) {

            $personnelId = $item['personnel_id'];

            $role = $item['role'];

            $person = DB::table('personnel')
                ->where('id', $personnelId)
                ->first();

            Log::info('PROCESSING PERSONNEL', [

                'personnel_id' => $personnelId,

                'role' => $role,

                'unit_id' => $person?->unit_id,

                'operation_id' => $request->operation_id

            ]);

            DB::table('personnel_operations')
                ->updateOrInsert(

                    [
                        'personnel_id' => $personnelId,
                        'operation_id' => $request->operation_id
                    ],

                    [
                        'role'       => $role,

                        'unit_id'    => $person?->unit_id,

                        'start_date' => $operation->start_date,

                        'end_date'   => $operation->end_date,

                        'status'     => 'ASSIGNED',

                        'created_at' => now(),

                        'updated_at' => now()
                    ]
                );

            Log::info('PERSONNEL SAVED', [

                'personnel_id' => $personnelId,

                'role' => $role

            ]);


        }
});


        Log::info('ASSIGNMENT COMPLETED');

        return response()->json([

            'success' => true,

            'message' => 'Personnel assigned successfully'

        ]);

   



    } catch (\Exception $e) {

        Log::error('ASSIGNMENT FAILED', [

            'message' => $e->getMessage(),

            'line' => $e->getLine(),

            'file' => $e->getFile()

        ]);

        return response()->json([

            'success' => false,

            'message' => $e->getMessage()

        ], 500);
    }
}



public function operationPersonnel($id)
{
    $personnel = DB::table('personnel_operations')

        ->join(
            'personnel',
            'personnel_operations.personnel_id',
            '=',
            'personnel.id'
        )

        ->leftJoin(
            'units',
            'personnel_operations.unit_id',
            '=',
            'units.id'
        )

        ->leftJoin('promotions', function ($join) {

            $join->on(
                'personnel.id',
                '=',
                'promotions.personnel_id'
            )
            ->where('promotions.is_current', 1);

        })

        ->leftJoin(
            'ranks',
            'promotions.rank_id',
            '=',
            'ranks.id'
        )
        ->where('personnel.status', 'ACTIVE')

        ->where(
            'personnel_operations.operation_id',
            $id
        )

        ->select(
            'personnel.id',
            'personnel.service_number',
            'personnel.full_name',
            'ranks.name as rank',
            'ranks.level',
            'ranks.category',
            'units.name as unit',
            'personnel_operations.role'
        )

        ->orderByRaw("
            CASE
                WHEN ranks.category = 'OFFICER' THEN 1
                ELSE 2
            END
        ")

        ->orderByDesc('ranks.level')

        ->orderBy('personnel.full_name')

        ->get();

    return response()->json($personnel);
}


}