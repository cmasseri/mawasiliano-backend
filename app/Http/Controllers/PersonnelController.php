<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Promotion;
use App\Models\Trade;
use App\Models\PersonnelTrade;
use App\Models\PersonnelEducation;
use App\Models\PersonnelAppointment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Training;
use App\Models\PersonnelTraining;
class PersonnelController extends Controller
{
    // =============================
    // LIST
    // =============================
public function index(Request $request)
{
    $query = Personnel::with([

        'education',
        'currentPromotion.rank',
        'currentTrade.trade',
        'currentAppointment.appointment',
        'trainings',
        'trainings.training'

    ]);

    if ($request->filled('unit_id')) {

        $query->where(
            'unit_id',
            $request->unit_id
        );
    }

    if ($request->filled('search')) {

        $s = $request->search;

        $query->where(function ($q) use ($s) {

            $q->where(
                'full_name',
                'like',
                "%{$s}%"
            )

            ->orWhere(
                'service_number',
                'like',
                "%{$s}%"
            );
        });
    }

    return response()->json(

        $query->latest()->paginate(20)

    );
}

    // =============================
    // BY UNIT
    // =============================


public function byUnit($unitId)
{
    return Personnel::with([

            'education',

            'currentPromotion.rank',

            'currentTrade.trade',

            'currentAppointment.appointment',

            'currentAppointment.appointment.unit',

            'trainings',

            'trainings.training'

        ])

        ->join('promotions', function ($join) {

            $join->on(
                'personnel.id',
                '=',
                'promotions.personnel_id'
            )

            ->where(
                'promotions.is_current',
                1
            );
        })

        ->join(
            'ranks',
            'promotions.rank_id',
            '=',
            'ranks.id'
        )

        ->where('personnel.unit_id', $unitId)

        // OFFICERS FIRST
        ->orderByRaw("
            CASE
                WHEN ranks.category = 'OFFICER'
                THEN 1
                ELSE 2
            END
        ")

        // HIGHEST LEVEL FIRST
        ->orderBy('ranks.level', 'desc')

        ->select('personnel.*')

        ->get();
}



    // =============================
    // STORE
    // =============================
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $validated = $request->validate([
                'full_name'          => 'required|string',
                'service_number'     => 'required|unique:personnel,service_number',
                'gender'             => 'required|in:MALE,FEMALE',
                'date_of_birth'      => 'required|date',
                'date_of_enlistment' => 'required|date',
                'education_level'    => 'required|string',

                'rank_id'            => 'required|exists:ranks,id',
                'trade_id'           => 'required|exists:trades,id',
                'appointment_id'     => 'required|exists:appointments,id',
                'date_of_promotion'  => 'required|date',

                'unit_id'            => 'required|exists:units,id'
            ]);
Log::info('TRADE DATA', [
    'military_year_completion' => $request->military_year_completion,
    'military_school' => $request->military_school,
    'all' => $request->all()
]);
            // =============================
            // 1. PERSONNEL
            // =============================
            $person = Personnel::create([
                'full_name'          => $validated['full_name'],
                'service_number'     => $validated['service_number'],
                'gender'             => $validated['gender'],
                'date_of_birth'      => $validated['date_of_birth'],
                'date_of_enlistment' => $validated['date_of_enlistment'],
                'education_level'    => $validated['education_level'],
                'unit_id'            => $validated['unit_id'],
                'status'             => 'ACTIVE'
            ]);

            // =============================
            // 2. PROMOTION
            // =============================
            Promotion::create([
                'personnel_id'  => $person->id,
                'rank_id'       => $validated['rank_id'],
                'date_promoted' => $validated['date_of_promotion'],
                'is_current'    => true
            ]);

            // =============================
            // 3. TRADE
            // =============================
            PersonnelTrade::create([
                'personnel_id' => $person->id,
                'trade_id'     => $validated['trade_id'],
                'start_date'   => now(),
                'end_date'     => $request->military_year_completion,
                'is_current'   => true
            ]);


            $selectedTrade = DB::table('trades')
            ->where('id', $validated['trade_id'])
            ->first();

            if ($selectedTrade) {

          
            $training = Training::whereRaw(
                'LOWER(name) = ?',
                [strtolower($selectedTrade->name)]
            )->first();


            if (!$training) {

                $training = Training::create([
                    'name' => $selectedTrade->name
                ]);
            }

          
            PersonnelTraining::create([

                'personnel_id' => $person->id,
                'training_id' => $training->id,
                'military_school' => $request->military_school,
                'end_date' => $request->military_year_completion
            ]);
        }

            // =============================
            // 4. APPOINTMENT
            // =============================
            PersonnelAppointment::create([
                'personnel_id'  => $person->id,
                'appointment_id'=> $validated['appointment_id'],
                'start_date'    => now(),
                'end_date'      => null,
                'is_current'    => true
            ]);

                        // =============================
            // 5. EDUCATION
            // =============================
             PersonnelEducation::create([
                'personnel_id' => $person->id,
                'name' => $validated['education_level'],
                'institution'     => $request->institution,
                'year_completion' => $request->year_completion,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Personnel created successfully'
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('STORE ERROR', [
                'error' => $e->getMessage(),
                'data'  => $request->all()
            ]);

            return response()->json([
                'message' => 'Failed to save',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // =============================
    // SHOW
    // =============================
public function show($id)
{
    $person = Personnel::findOrFail($id);

   
$person->load([
    'education',
    'currentPromotion',
    'currentPromotion.rank',
    'currentTrade',
    'currentTrade.trade',
    'currentAppointment',
    'currentAppointment.appointment',
    'currentAppointment.appointment.unit',
    'trainings',
      'unit',
    'trainings.training'
]);

    return $person;
}

    // =============================
    // UPDATE
    // =============================
    public function update(Request $request, $id)
{
    DB::beginTransaction();

    try {

        $person = Personnel::findOrFail($id);

        $validated = $request->validate([
            'full_name'          => 'required|string',
            'service_number'     => [
                'required',
                Rule::unique('personnel', 'service_number')->ignore($person->id)
            ],
            'gender'             => 'required|in:MALE,FEMALE',
            'date_of_birth'      => 'required|date',
            'date_of_enlistment' => 'required|date',
            'education_level'    => 'required|string',
            'year_completion'    => 'nullable|string',
            'rank_id'            => 'required|exists:ranks,id',
            'trade_id'           => 'required|exists:trades,id',
            'appointment_id'     => 'required|exists:appointments,id',
            'unit_id'            => 'required|exists:units,id',
        ]);
Log::info('TRADE DATA', [
    'military_year_completion' => $request->military_year_completion,
    'military_school' => $request->military_school,
    'all' => $request->all()
]);
        // =============================
        // 1. UPDATE BASIC INFO
        // =============================
        $person->update([
            'full_name'          => $validated['full_name'],
            'service_number'     => $validated['service_number'],
            'gender'             => $validated['gender'],
            'date_of_birth'      => $validated['date_of_birth'],
            'date_of_enlistment' => $validated['date_of_enlistment'],
            'education_level'    => $validated['education_level'],
            'unit_id'            => $validated['unit_id'],
        ]);

        // =============================
        // 2. UPDATE RANK (PROMOTION)
        // =============================
        $currentRank = Promotion::where('personnel_id', $person->id)
            ->where('is_current', true)
            ->first();

        if (!$currentRank || $currentRank->rank_id != $validated['rank_id']) {

            // close old
            if ($currentRank) {
                $currentRank->update([
                    'is_current' => false
                ]);
            }

            // new
            Promotion::create([
                'personnel_id'  => $person->id,
                'rank_id'       => $validated['rank_id'],
                'date_promoted' => now(),
                'is_current'    => true
            ]);
        }

// =============================
// 3. UPDATE TRADE
// =============================

$currentTrade = PersonnelTrade::where(
'personnel_id',
$person->id
)
->where('is_current', 1)
->first();

if ($currentTrade) {


// Trade ya zamani iliyokuwa imeandikwa
$oldTradeId = $currentTrade->trade_id;
    // =============================
    // UPDATE ACTIVE TRADE
    // =============================

 $currentTrade->update([
    'trade_id' => $validated['trade_id'],
    'end_date' => $request->military_year_completion
]);

// Fanya update kama trade imebadilika
if ($oldTradeId != $validated['trade_id']) {


    // =============================
    // TRADE MPYA
    // =============================

    $newTrade = Trade::find(
        $validated['trade_id']
    );

    if ($newTrade) {

        // =============================
        // ANGALIA TRAINING MPYA
        // =============================

        $newTraining = Training::whereRaw(
            'LOWER(name) = ?',
            [strtolower(trim($newTrade->name))]
        )->first();

        // kama haipo, create
        if (!$newTraining) {

            $newTraining = Training::create([
                'name' => $newTrade->name
            ]);
        }

        // =============================
        // TRADE YA ZAMANI
        // =============================

        $oldTrade = Trade::find(
            $oldTradeId
        );

        if ($oldTrade) {

            // =============================
            // TRAINING YA ZAMANI
            // =============================

            $oldTraining = Training::whereRaw(
                'LOWER(name) = ?',
                [strtolower(trim($oldTrade->name))]
            )->first();

            if ($oldTraining) {

                // =============================
                // TAFUTA RECORD YA AWALI
                // =============================

                $personTraining = PersonnelTraining::where(
                        'personnel_id',
                        $person->id
                    )
                    ->where(
                        'training_id',
                        $oldTraining->id
                    )
                    ->first();

                // =============================
                // EDIT RECORD YA AWALI
                // =============================

                if ($personTraining) {

                    $personTraining->update([

                        'training_id' =>
                            $newTraining->id,

                        'military_school' =>
                            $request->military_school,

                        'end_date' =>
                            $request->military_year_completion
                    ]);
                }
            }
        }
    }
}


}

// =============================
        // 4. UPDATE APPOINTMENT
        // =============================
        $currentAppointment = PersonnelAppointment::where('personnel_id', $person->id)
            ->where('is_current', true)
            ->first();

        if (!$currentAppointment || $currentAppointment->appointment_id != $validated['appointment_id']) {

            if ($currentAppointment) {
                $currentAppointment->update([
                    'is_current' => false,
                    'end_date'   => now()
                ]);
            }

            PersonnelAppointment::create([
                'personnel_id'  => $person->id,
                'appointment_id'=> $validated['appointment_id'],
                'start_date'    => now(),
                'end_date'      => null,
                'is_current'    => true
            ]);
        }
// =============================
// 5. UPDATE EDUCATION
// =============================

$education = PersonnelEducation::where(
    'personnel_id',
    $person->id
)->first();

if ($education) {

    $education->update([

        'name' => $validated['education_level'],

        'institution' =>
            $request->institution,

        'year_completion' =>
            $request->year_completion
    ]);

} else {

    PersonnelEducation::create([

        'personnel_id' =>
            $person->id,

        'name' =>
            $validated['education_level'],

        'institution' =>
            $request->institution,

        'year_completion' =>
            $request->year_completion,

        'created_at' => now(),

        'updated_at' => now()
    ]);
}
        DB::commit();

        return response()->json([
            'message' => 'Personnel updated successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('UPDATE ERROR', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'message' => 'Update failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}
    // =============================
    // DELETE
    // =============================
    public function destroy($id)
    {
        $person = Personnel::findOrFail($id);
        $person->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }


public function search(Request $request)
{
    $q = $request->q;

    return Personnel::select(
            'id',
            'service_number',
            'full_name'
        )
        ->where('full_name', 'like', "%{$q}%")
        ->orWhere('service_number', 'like', "%{$q}%")
        ->limit(20)
        ->get();
}
}