<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonnelExtraController extends Controller
{

    // =========================
    // 🔹 GET DATA
    // =========================

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

    public function education($id)
    {
        return DB::table('personnel_education')
            ->where('personnel_id', $id)
            ->get();
    }

    public function operations($id)
    {
        return DB::table('personnel_operations')
            ->where('personnel_id', $id)
            ->get();
    }

  public function trainings($id)
    {
        return DB::table('personnel_trainings') 
            ->where('personnel_id', $id)
            ->get();
    }


    // =========================
    // 🔹 STORE
    // =========================

    public function storeTrade(Request $request)
    {
        $data = $request->validate([
            'personnel_id' => 'required|integer',
            'trade_name'   => 'required|string',
        ]);

        return DB::table('personnel_trades')->insertGetId([
            'personnel_id' => $data['personnel_id'],
            'trade_name'   => $data['trade_name'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

public function storeEducation(Request $request)
{
    \Log::info('EDUCATION REQUEST', [ 'data' => $request->all() ]);

    try {
        $data = $request->validate([
            'personnel_id'    => 'required|integer',
            'name'            => 'required|string',
            'institution'     => 'nullable|string',
            'year_completion' => 'nullable'
        ]);

        $id = DB::table('personnel_education')
            ->insertGetId([

                'personnel_id'    => $data['personnel_id'],
                'name'            => $data['name'],
                'institution'     =>
                    $data['institution'] ?? null,
                'year_completion' =>
                    $data['year_completion'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now()
            ]);

        \Log::info('EDUCATION SAVED', [
            'id' => $id
        ]);

        return response()->json([
            'message' => 'Education saved successfully',
            'id'      => $id
        ]);

    } catch (\Exception $e) {
        \Log::error('EDUCATION STORE ERROR', [
            'message' => $e->getMessage(),
            'data' => $request->all()
        ]);

        return response()->json([
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function storeOperation(Request $request)
    {
        $data = $request->validate([
            'personnel_id'   => 'required|integer',
            'operation_name' => 'required|string',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
        ]);

        return DB::table('personnel_operations')->insertGetId([
            'personnel_id'   => $data['personnel_id'],
            'operation_name' => $data['operation_name'],
            'start_date'     => $data['start_date'] ?? null,
            'end_date'       => $data['end_date'] ?? null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

public function storeTraining(Request $request)
{
    $data = $request->validate([

        'personnel_id'    => 'required|integer',

        'name'            => 'required|string',

        'type'            => 'nullable|string',

        'military_school' => 'nullable|string',

        'end_date'        => 'nullable|string',
    ]);

    // =========================
    // TRADE TRAINING
    // =========================

    if ($request->type === 'trade') {

        DB::table('personnel_trades')
            ->where('personnel_id', $request->personnel_id)
            ->where('is_current', 1)
            ->update([

                'is_current' => 0,
                'updated_at' => now()
            ]);

        $trade = DB::table('trades')
            ->where('name', $request->name)
            ->first();

        if ($trade) {

            DB::table('personnel_trades')
                ->insert([

                    'personnel_id' => $request->personnel_id,

                    'trade_id'     => $trade->id,

                    'start_date'   => now(),

                    'is_current'   => 1,

                    'created_at'   => now(),

                    'updated_at'   => now()
                ]);
        }
    }

    // =========================
    // TRAINING TABLE
    // =========================

    $training = DB::table('trainings')
        ->where('name', $request->name)
        ->first();

    if (!$training) {

        $trainingId = DB::table('trainings')
            ->insertGetId([

                'name'       => $request->name,

                'category'   => $request->type,

                'created_at' => now(),

                'updated_at' => now()
            ]);

    } else {

        $trainingId = $training->id;
    }

    // =========================
    // PERSONNEL TRAINING
    // =========================

    DB::table('personnel_trainings')
        ->insert([

            'personnel_id'    => $request->personnel_id,

            'training_id'     => $trainingId,

            'military_school' => $request->military_school,

            'end_date'        => $request->end_date
        ]);

    return response()->json([

        'message' => 'Training added successfully'
    ]);
}


    // =========================
    // 🔹 UPDATE
    // =========================


public function updateEducation($id, Request $request)
{
    \Log::info('EDUCATION UPDATE REQUEST', [
        'id'   => $id,
        'data' => $request->all()

    ]);

    try {

        DB::table('personnel_education')
            ->where('id', $id)
            ->update([

                'name'            => $request->name,
                'institution'     => $request->institution,
                'year_completion' => $request->year_completion,
                'updated_at'      => now()
            ]);

        \Log::info('EDUCATION UPDATED', [
            'id' => $id
        ]);

        return response()->json([
            'message' => 'Education updated successfully'
        ]);

    } catch (\Exception $e) {

        \Log::error('EDUCATION UPDATE ERROR', [
            'message' => $e->getMessage(),
            'id' => $id,
            'data' => $request->all()
        ]);

        return response()->json([
            'message' => $e->getMessage()
        ], 500);
    }
}
    public function updateOperation($id, Request $request)
    {
        DB::table('personnel_operations')
            ->where('id', $id)
            ->update([
                'operation_name' => $request->operation_name,
                'start_date'     => $request->start_date,
                'end_date'       => $request->end_date,
                'updated_at'     => now()
            ]);

        return response()->json(['message' => 'Operation updated']);


}

public function updateTraining($id, Request $request)
{
    DB::beginTransaction();

    try {

        $request->validate([

            'personnel_id'    => 'required|integer',

            'name'            => 'required|string',

            'type'            => 'required|string',

            'military_school' => 'nullable|string',

            'end_date'        => 'nullable|string',
        ]);

        $personTraining = DB::table('personnel_trainings')
            ->where('id', $id)
            ->first();

        if (!$personTraining) {

            return response()->json([
                'message' => 'Training not found'
            ], 404);
        }

        // ===================================
        // CASE 1: TRADE TRAINING
        // ===================================

        if ($request->type === 'trade') {

            $trade = DB::table('trades')
                ->where('name', $request->name)
                ->first();

            if ($trade) {

                DB::table('personnel_trades')
                    ->where('personnel_id', $request->personnel_id)
                    ->where('is_current', 1)
                    ->update([

                        'trade_id'   => $trade->id,

                        'updated_at' => now()
                    ]);
            }
        }

        // ===================================
        // TRAININGS TABLE
        // ===================================

        $training = DB::table('trainings')
            ->where('name', $request->name)
            ->first();

        if (!$training) {

            $trainingId = DB::table('trainings')
                ->insertGetId([

                    'name'       => $request->name,

                    'category'   => $request->type,

                    'created_at' => now(),

                    'updated_at' => now()
                ]);

        } else {

            $trainingId = $training->id;
        }

        // ===================================
        // PERSONNEL TRAININGS
        // ===================================

        DB::table('personnel_trainings')
            ->where('id', $id)
            ->update([

                'training_id'     => $trainingId,

                'military_school' => $request->military_school,

                'end_date'        => $request->end_date
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