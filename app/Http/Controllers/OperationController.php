<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List Operations
    |--------------------------------------------------------------------------
    */
public function index()
{
    $operations = Operation::query()

        ->orderByRaw("
            CASE
                WHEN CURDATE() BETWEEN start_date AND end_date THEN 1
                WHEN start_date > CURDATE() THEN 2
                ELSE 3
            END
        ")

        ->orderBy('start_date', 'asc')

        ->orderBy('end_date', 'asc')

        ->get();

    return response()->json($operations);
}

    /*
    |--------------------------------------------------------------------------
    | Store Operation
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'name'        => 'required|string|max:191',

            'type'        => 'nullable|string|max:191',

            'location'    => 'nullable|string|max:191',

            'country'     => 'nullable|string|max:191',

            'start_date'  => 'required|date',

            'end_date' => [
    'required',
    'date',
    'after_or_equal:start_date'
],

            'description' => 'nullable|string',

        ]);

        $operation = Operation::create($validated);

        Log::info('OPERATION CREATED', [

            'operation_id' => $operation->id,

            'name' => $operation->name,

            'type' => $operation->type,

            'start_date' => $operation->start_date,

            'end_date' => $operation->end_date,

        ]);

        return response()->json($operation, 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Operation
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        return response()->json(

            Operation::findOrFail($id)

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Operation
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $operation = Operation::findOrFail($id);

        $validated = $request->validate([

            'name'        => 'required|string|max:191',

            'type'        => 'nullable|string|max:191',

            'location'    => 'nullable|string|max:191',

            'country'     => 'nullable|string|max:191',

            'start_date'  => 'required|date',

           'end_date' => [
    'required',
    'date',
    'after_or_equal:start_date'
],

            'description' => 'nullable|string',

        ]);

        $operation->update($validated);

        Log::info('OPERATION UPDATED', [

            'operation_id' => $operation->id,

            'name' => $operation->name,

            'type' => $operation->type,

        ]);

        return response()->json($operation);
    }

        /*
    |--------------------------------------------------------------------------
    | Delete Operation
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Available Personnel For Assignment
    |--------------------------------------------------------------------------
    */

    public function personnelOps($unitId)
    {
        $personnel = DB::table('personnel')

            // Current Trade
            ->leftJoin('personnel_trades', function ($join) {

                $join->on(
                    'personnel.id',
                    '=',
                    'personnel_trades.personnel_id'
                )
                ->where('personnel_trades.is_current', 1);

            })

            ->leftJoin(
                'trades',
                'personnel_trades.trade_id',
                '=',
                'trades.id'
            )

            // Current Rank
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

            ->where('personnel.unit_id', $unitId)

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

->where(function ($query) {

    $query->whereRaw("
        CURDATE()
        BETWEEN
        operations.start_date
        AND
        operations.end_date
    ")

    ->orWhereRaw("
        operations.start_date > CURDATE()
    ");

});

            })

            ->select(

                'personnel.id',

                'personnel.service_number',

                'personnel.full_name',

                'ranks.name as rank_name',

                'trades.name as trade_name'

            )

        ->orderBy('ranks.level', 'desc')
->orderBy('personnel.full_name')

            ->get();

        return response()->json($personnel);
    }


        /*
    |--------------------------------------------------------------------------
    | Assign Personnel
    |--------------------------------------------------------------------------
    */

    public function assignPersonnel(Request $request)
    {
        Log::info('ASSIGN PERSONNEL START', [
            'request' => $request->all()
        ]);

        $request->validate([

            'operation_id' => 'required|exists:operations,id',

            'personnel' => 'required|array|min:1',

            'personnel.*.personnel_id'
                => 'required|exists:personnel,id',

            'personnel.*.role'
                => 'required|string'

        ]);

        try {

            $operation = Operation::findOrFail(
                $request->operation_id
            );


            if ($operation->status === 'COMPLETED') {

                return response()->json([

                    'success' => false,

                    'message' =>
                        'Cannot assign personnel to a completed operation.'

                ], 422);

            }

            DB::transaction(function () use ($request, $operation) {

                foreach ($request->personnel as $item) {
$person = DB::table('personnel')
    ->select('id', 'unit_id')
    ->where('id', $item['personnel_id'])
    ->first();

                    Log::info('PROCESSING PERSONNEL', [

                        'personnel_id'
                            => $item['personnel_id'],

                        'role'
                            => $item['role'],

                        'unit_id'
                            => $person?->unit_id,

                        'operation_id'
                            => $request->operation_id

                    ]);

                    DB::table('personnel_operations')
                        ->updateOrInsert(

                            [

                                'personnel_id'
                                    => $item['personnel_id'],

                                'operation_id'
                                    => $request->operation_id

                            ],

                            [

                                'role'
                                    => $item['role'],

                                'unit_id'
                                    => $person?->unit_id,

                                'start_date'
                                    => $operation->start_date,

                                'end_date'
                                    => $operation->end_date,

                                'status'
                                    => 'ASSIGNED',

                               'remarks' => null,


                                'created_at'
                                    => now(),

                                'updated_at'
                                    => now()

                            ]

                        );

                    Log::info('PERSONNEL ASSIGNED', [

                        'personnel_id'
                            => $item['personnel_id'],

                        'operation_id'
                            => $request->operation_id

                    ]);

                }

            });

            Log::info('ASSIGNMENT COMPLETED');

            return response()->json([

                'success' => true,

                'message'
                    => 'Personnel assigned successfully'

            ]);

        } catch (\Exception $e) {

            Log::error('ASSIGNMENT FAILED', [

                'message'
                    => $e->getMessage(),

                'line'
                    => $e->getLine(),

                'file'
                    => $e->getFile()

            ]);

            return response()->json([

                'success' => false,

                'message'
                    => $e->getMessage()

            ], 500);

        }
    }

        /*
    |--------------------------------------------------------------------------
    | Operation Personnel
    |--------------------------------------------------------------------------
    */

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

            ->leftJoin('personnel_trades', function ($join) {

    $join->on(
        'personnel.id',
        '=',
        'personnel_trades.personnel_id'
    )
    ->where('personnel_trades.is_current',1);

})

->leftJoin(
    'trades',
    'personnel_trades.trade_id',
    '=',
    'trades.id'
)


            ->where(
                'personnel_operations.operation_id',
                $id
            )

            ->where(
    'personnel_operations.status',
    'ASSIGNED'
)

            ->select(

                'personnel.id',

                'personnel.service_number',

                'personnel.full_name',

                'personnel.status as current_status',

                'ranks.name as rank',
                'trades.name as trade',

                'ranks.level',

                'ranks.category',

                'units.name as unit',

                'personnel_operations.role',

                'personnel_operations.start_date',

                'personnel_operations.end_date'

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


public function removePersonnel(
    Request $request,
    $operationId,
    $personnelId
)
{
    $request->validate([
        'reason' => 'required|string|min:5|max:500'
    ]);

    $operation = Operation::findOrFail($operationId);

    if ($operation->status === 'COMPLETED') {

        return response()->json([
            'success' => false,
            'message' => 'Personnel cannot be removed from a completed operation.'
        ], 422);

    }

    $assignment = DB::table('personnel_operations')
        ->where('operation_id', $operationId)
        ->where('personnel_id', $personnelId)
        ->first();

    if (!$assignment) {

        return response()->json([
            'success' => false,
            'message' => 'Assignment not found.'
        ], 404);

    }

    DB::table('personnel_operations')
        ->where('operation_id', $operationId)
        ->where('personnel_id', $personnelId)
        ->update([
            'status' => 'REMOVED',
            'remarks' => $request->reason,
            'updated_at' => now()
        ]);

    Log::warning('PERSONNEL REMOVED FROM OPERATION', [
        'operation_id' => $operationId,
        'personnel_id' => $personnelId,
        'reason' => $request->reason
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Personnel removed successfully.'
    ]);
}

}