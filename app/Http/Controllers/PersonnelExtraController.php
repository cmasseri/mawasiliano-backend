<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonnelExtraController extends Controller
{

    // ==========================================
    // TRADES
    // ==========================================

    public function trades($id)
    {
        return DB::table('personnel_trades')

            ->join(
                'trades',
                'personnel_trades.trade_id',
                '=',
                'trades.id'
            )

            ->select(

                'personnel_trades.id',

                'personnel_trades.personnel_id',

                'personnel_trades.trade_id',

                'personnel_trades.start_date',

                'personnel_trades.end_date',

                'personnel_trades.is_current',

                'trades.name as trade_name'

            )

            ->where(
                'personnel_trades.personnel_id',
                $id
            )

            ->orderByDesc(
                'personnel_trades.is_current'
            )

            ->orderByDesc(
                'personnel_trades.start_date'
            )

            ->get();
    }

    // ==========================================
    // EDUCATION
    // ==========================================

    public function education($id)
    {
        return DB::table('personnel_education')

            ->where(
                'personnel_id',
                $id
            )

            ->orderByDesc('year_completion')

            ->get();
    }

    // ==========================================
    // OPERATIONS
    // ==========================================

    public function operations($id)
{
    return DB::table('personnel_operations')

        ->join(
            'operations',
            'personnel_operations.operation_id',
            '=',
            'operations.id'
        )

        ->leftJoin(
            'units',
            'personnel_operations.unit_id',
            '=',
            'units.id'
        )

        ->select(

            'personnel_operations.*',

            'operations.name as operation_name',

            'operations.type as operation_type',

            'operations.country',

            'operations.location',

            'operations.status as operation_status',

            'units.name as unit_name'

        )

        ->where(
            'personnel_operations.personnel_id',
            $id
        )

        ->orderByDesc(
            'personnel_operations.start_date'
        )

        ->get();
}
    // ==========================================
    // TRAININGS
    // ==========================================

    public function trainings($id)
    {
        return DB::table('personnel_trainings')

            ->join(
                'trainings',
                'personnel_trainings.training_id',
                '=',
                'trainings.id'
            )

            ->select(

                'personnel_trainings.*',

                'trainings.name',

                'trainings.category'

            )

            ->where(
                'personnel_trainings.personnel_id',
                $id
            )

            ->orderByDesc(
                'personnel_trainings.end_date'
            )

            ->get();
    }

        // ==========================================
    // STORE TRADE
    // ==========================================

    public function storeTrade(Request $request)
    {
        $data = $request->validate([

            'personnel_id' => 'required|integer',

            'trade_id'     => 'required|exists:trades,id',

            'start_date'   => 'nullable|date',

            'end_date'     => 'nullable|date'

        ]);

        DB::table('personnel_trades')

            ->where('personnel_id', $data['personnel_id'])

            ->where('is_current', 1)

            ->update([

                'is_current' => 0,

                'updated_at' => now()

            ]);

        $id = DB::table('personnel_trades')

            ->insertGetId([

                'personnel_id' => $data['personnel_id'],

                'trade_id'     => $data['trade_id'],

                'start_date'   => $data['start_date'] ?? now(),

                'end_date'     => $data['end_date'] ?? null,

                'is_current'   => 1,

                'created_at'   => now(),

                'updated_at'   => now()

            ]);

        return response()->json([

            'message' => 'Trade added successfully',

            'id' => $id

        ]);
    }

    // ==========================================
    // STORE EDUCATION
    // ==========================================

    public function storeEducation(Request $request)
    {
        try {

            $data = $request->validate([

                'personnel_id'    => 'required|integer',

                'qualification'   => 'required|string',

                'field_of_study'  => 'nullable|string',

                'institution'     => 'nullable|string',

                'year_completion' => 'nullable|integer|min:1900|max:2100'

            ]);

            $id = DB::table('personnel_education')

                ->insertGetId([

                    'personnel_id'    => $data['personnel_id'],

                    'qualification'   => $data['qualification'],

                    'field_of_study'  => $data['field_of_study'] ?? null,

                    'institution'     => $data['institution'] ?? null,

                    'year_completion' => $data['year_completion'] ?? null,

                    'created_at'      => now(),

                    'updated_at'      => now()

                ]);

            return response()->json([

                'message' => 'Education saved successfully',

                'id' => $id

            ]);

        } catch (\Exception $e) {

            return response()->json([

                'message' => $e->getMessage()

            ], 500);

        }
    }

        // ==========================================
    // STORE OPERATION
    // ==========================================

    public function storeOperation(Request $request)
    {
        $data = $request->validate([

            'personnel_id'   => 'required|integer',

            'operation_name' => 'required|string',

            'start_date'     => 'nullable|date',

            'end_date'       => 'nullable|date',

        ]);

        $id = DB::table('personnel_operations')

            ->insertGetId([

                'personnel_id'   => $data['personnel_id'],

                'operation_name' => $data['operation_name'],

                'start_date'     => $data['start_date'] ?? null,

                'end_date'       => $data['end_date'] ?? null,

                'created_at'     => now(),

                'updated_at'     => now()

            ]);

        return response()->json([

            'message' => 'Operation added successfully',

            'id' => $id

        ]);
    }

    // ==========================================
    // STORE TRAINING
    // ==========================================

    public function storeTraining(Request $request)
    {
        DB::beginTransaction();

        try {

            $data = $request->validate([

                'personnel_id'    => 'required|integer',

                'name'            => 'required|string',

                'type'            => 'required|string',

                'military_school' => 'nullable|string',

                'end_date'        => 'nullable|string'

            ]);

            // =====================================
            // TRADE TRAINING
            // =====================================

            if ($data['type'] === 'trade') {

                DB::table('personnel_trades')

                    ->where('personnel_id', $data['personnel_id'])

                    ->where('is_current', 1)

                    ->update([

                        'is_current' => 0,

                        'updated_at' => now()

                    ]);

                $trade = DB::table('trades')

                    ->where('name', $data['name'])

                    ->first();

                if ($trade) {

                    DB::table('personnel_trades')

                        ->insert([

                            'personnel_id' => $data['personnel_id'],

                            'trade_id'     => $trade->id,

                            'start_date'   => now(),

                            'is_current'   => 1,

                            'created_at'   => now(),

                            'updated_at'   => now()

                        ]);

                }

            }

            // =====================================
            // TRAINING MASTER
            // =====================================

            $training = DB::table('trainings')

                ->where('name', $data['name'])

                ->first();

            if (!$training) {

                $trainingId = DB::table('trainings')

                    ->insertGetId([

                        'name'       => $data['name'],

                        'category'   => $data['type'],

                        'created_at' => now(),

                        'updated_at' => now()

                    ]);

            } else {

                $trainingId = $training->id;

            }

            // =====================================
            // PERSONNEL TRAINING
            // =====================================

            DB::table('personnel_trainings')

                ->insert([

                    'personnel_id'    => $data['personnel_id'],

                    'training_id'     => $trainingId,

                    'military_school' => $data['military_school'] ?? null,

                    'end_date'        => $data['end_date'] ?? null,

                    'created_at'      => now(),

                    'updated_at'      => now()

                ]);

            DB::commit();

            return response()->json([

                'message' => 'Training added successfully'

            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'message' => $e->getMessage()

            ], 500);

        }

    }
        // ==========================================
    // UPDATE EDUCATION
    // ==========================================

    public function updateEducation($id, Request $request)
    {
        try {

            $data = $request->validate([

                'qualification'   => 'required|string',

                'field_of_study'  => 'nullable|string',

                'institution'     => 'nullable|string',

               'year_completion' => 'nullable|integer|min:1900|max:2100'

            ]);

            DB::table('personnel_education')

                ->where('id', $id)

                ->update([

                    'qualification'   => $data['qualification'],

                    'field_of_study'  => $data['field_of_study'] ?? null,

                    'institution'     => $data['institution'] ?? null,

                    'year_completion' => $data['year_completion'] ?? null,

                    'updated_at'      => now()

                ]);

            return response()->json([

                'message' => 'Education updated successfully'

            ]);

        } catch (\Exception $e) {

            return response()->json([

                'message' => $e->getMessage()

            ], 500);

        }
    }

    // ==========================================
    // UPDATE OPERATION
    // ==========================================

    public function updateOperation($id, Request $request)
    {
        $data = $request->validate([

            'operation_name' => 'required|string',

            'start_date'     => 'nullable|date',

            'end_date'       => 'nullable|date'

        ]);

        DB::table('personnel_operations')

            ->where('id', $id)

            ->update([

                'operation_name' => $data['operation_name'],

                'start_date'     => $data['start_date'] ?? null,

                'end_date'       => $data['end_date'] ?? null,

                'updated_at'     => now()

            ]);

        return response()->json([

            'message' => 'Operation updated successfully'

        ]);
    }

    // ==========================================
    // UPDATE TRAINING
    // ==========================================

    public function updateTraining($id, Request $request)
    {
        DB::beginTransaction();

        try {

            $data = $request->validate([

                'personnel_id'    => 'required|integer',

                'name'            => 'required|string',

                'type'            => 'required|string',

                'military_school' => 'nullable|string',

                'end_date'        => 'nullable|string'

            ]);

            $personTraining = DB::table('personnel_trainings')

                ->where('id', $id)

                ->first();

            if (!$personTraining) {

                return response()->json([

                    'message' => 'Training not found'

                ], 404);

            }

            // =====================================
            // UPDATE TRADE IF TRADE TRAINING
            // =====================================

            if ($data['type'] === 'trade') {

                $trade = DB::table('trades')

                    ->where('name', $data['name'])

                    ->first();

                if ($trade) {

                    DB::table('personnel_trades')

                        ->where('personnel_id', $data['personnel_id'])

                        ->where('is_current', 1)

                        ->update([

                            'trade_id'   => $trade->id,

                            'updated_at' => now()

                        ]);

                }

            }

            // =====================================
            // TRAINING MASTER
            // =====================================

            $training = DB::table('trainings')

                ->where('name', $data['name'])

                ->first();

            if (!$training) {

                $trainingId = DB::table('trainings')

                    ->insertGetId([

                        'name'       => $data['name'],

                        'category'   => $data['type'],

                        'created_at' => now(),

                        'updated_at' => now()

                    ]);

            } else {

                $trainingId = $training->id;

            }

            // =====================================
            // UPDATE PERSONNEL TRAINING
            // =====================================

            DB::table('personnel_trainings')

                ->where('id', $id)

                ->update([

                    'training_id'     => $trainingId,

                    'military_school' => $data['military_school'] ?? null,

                    'end_date'        => $data['end_date'] ?? null,

                    'updated_at'      => now()

                ]);

            DB::commit();

            return response()->json([

                'message' => 'Training updated successfully'

            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([

                'message' => $e->getMessage()

            ], 500);

        }
    }

}