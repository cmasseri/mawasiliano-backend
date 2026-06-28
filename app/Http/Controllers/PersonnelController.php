<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Models\Promotion;
use App\Models\Trade;
use App\Models\PersonnelTrade;
use App\Models\PersonnelEducation;
use App\Models\PersonnelAppointment;
use App\Models\Training;
use App\Models\PersonnelTraining;
use App\Models\RetirementExtension;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\RetirementRule;
class PersonnelController extends Controller
{
    // ==================================================
    // LIST PERSONNEL
    // ==================================================

    public function index(Request $request)
    {
        $query = Personnel::with([

            'education',

            'currentPromotion.rank',

            'currentTrade.trade',

            'currentAppointment.appointment',

            'trainings',

            'trainings.training'

        ])
        ->where('status', 'ACTIVE');

        if ($request->filled('unit_id')) {

            $query->where(
                'unit_id',
                $request->unit_id
            );

        }

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'full_name',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'service_number',
                    'like',
                    "%{$search}%"
                );

            });

        }

        return response()->json(

            $query
                ->latest()
                ->paginate(20)

        );
    }

    // ==================================================
    // PERSONNEL BY UNIT
    // ==================================================

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
                    true
                );

            })

            ->join(
                'ranks',
                'promotions.rank_id',
                '=',
                'ranks.id'
            )

            ->where(
                'personnel.unit_id',
                $unitId
            )
            ->where('personnel.status', 'ACTIVE')

            // Officers first
            ->orderByRaw("
                CASE
                    WHEN ranks.category='OFFICER'
                    THEN 1
                    ELSE 2
                END
            ")

            // Highest rank first
            ->orderBy(
                'ranks.level',
                'desc'
            )

            ->select(
                'personnel.*'
            )

            ->get();
    }
    // ==================================================
// STORE PERSONNEL
// ==================================================

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

            'qualification'      => 'required|string',

            'field_of_study'     => 'nullable|string',

            'institution'        => 'nullable|string',

            'year_completion'    => 'nullable|string',

            'rank_id'            => 'required|exists:ranks,id',

            'trade_id'           => 'required|exists:trades,id',

            'appointment_id'     => 'required|exists:appointments,id',

            'date_of_promotion'  => 'required|date',

            'unit_id'            => 'required|exists:units,id'

        ]);

        Log::info('STORE PERSONNEL', [

            'data' => $request->all()

        ]);

        // =====================================
        // PERSONNEL
        // =====================================

        $person = Personnel::create([

            'full_name'          => $validated['full_name'],

            'service_number'     => $validated['service_number'],

            'gender'             => $validated['gender'],

            'date_of_birth'      => $validated['date_of_birth'],

            'date_of_enlistment' => $validated['date_of_enlistment'],

            'unit_id'            => $validated['unit_id'],

            'status'             => 'ACTIVE'

        ]);

        // =====================================
        // PROMOTION
        // =====================================

        Promotion::create([

            'personnel_id'  => $person->id,

            'rank_id'       => $validated['rank_id'],

            'date_promoted' => $validated['date_of_promotion'],

            'is_current'    => true

        ]);

        // =====================================
        // TRADE
        // =====================================

        PersonnelTrade::create([

            'personnel_id' => $person->id,

            'trade_id'     => $validated['trade_id'],

            'start_date'   => now(),

            'end_date'     => $request->military_year_completion,

            'is_current'   => true

        ]);

        $selectedTrade = Trade::find(

            $validated['trade_id']

        );

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

                'personnel_id'    => $person->id,

                'training_id'     => $training->id,

                'military_school' => $request->military_school,

                'end_date'        => $request->military_year_completion

            ]);

        }

        // =====================================
        // APPOINTMENT
        // =====================================

        PersonnelAppointment::create([

            'personnel_id'   => $person->id,

            'appointment_id' => $validated['appointment_id'],

            'start_date'     => now(),

            'end_date'       => null,

            'is_current'     => true

        ]);

        // =====================================
        // EDUCATION
        // =====================================

        PersonnelEducation::create([

            'personnel_id'    => $person->id,

            'qualification'   => $validated['qualification'],

            'field_of_study'  => $request->field_of_study,

            'institution'     => $request->institution,

            'year_completion' => $request->year_completion

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

// ==================================================
// SHOW PERSONNEL
// ==================================================

public function show($id)
{
    $person = Personnel::with([

        'education',
        'currentPromotion.rank',
        'currentTrade.trade',
        'currentAppointment.appointment',
        'currentAppointment.appointment.unit',
        'trainings',
        'trainings.training',
        'unit'

    ])->findOrFail($id);

    $person->latest_education = DB::table('personnel_education')
        ->where('personnel_id', $person->id)
        ->latest('id')
        ->first();

    return response()->json($person);
}
// ==================================================
// UPDATE PERSONNEL
// ==================================================
public function update(Request $request, $id)
{
    $person = Personnel::findOrFail($id);

    DB::beginTransaction();

    try {

$validated = $request->validate([

    'full_name'          => 'required|string',

    'service_number'     => [
        'required',
        Rule::unique('personnel', 'service_number')
            ->ignore($person->id)
    ],

    'gender'             => 'required|in:MALE,FEMALE',

    'date_of_birth'      => 'required|date',

    'date_of_enlistment' => 'required|date',

    'qualification'      => 'required|string',

    'field_of_study'     => 'nullable|string',

    'institution'        => 'nullable|string',

    'year_completion'    => 'nullable|string',

    'rank_id'            => 'required|exists:ranks,id',

    'trade_id'           => 'required|exists:trades,id',

    'appointment_id'     => 'required|exists:appointments,id',

    'date_of_promotion'  => 'required|date',

    'unit_id'            => 'required|exists:units,id'

]);

        $person->update([

            'full_name'          => $validated['full_name'],

            'service_number'     => $validated['service_number'],

            'gender'             => $validated['gender'],

            'date_of_birth'      => $validated['date_of_birth'],

            'date_of_enlistment' => $validated['date_of_enlistment'],

            'unit_id'            => $validated['unit_id'],

          'status' => $person->status

        ]);

        // =====================================
        // CURRENT PROMOTION
        // =====================================

        $promotion = Promotion::where(
            'personnel_id',
            $person->id
        )
        ->where('is_current', true)
        ->first();

        if ($promotion) {

            $promotion->update([

                'rank_id'       => $validated['rank_id'],

                'date_promoted' => $validated['date_of_promotion']

            ]);

        } else {

            Promotion::create([

                'personnel_id'  => $person->id,

                'rank_id'       => $validated['rank_id'],

                'date_promoted' => $validated['date_of_promotion'],

                'is_current'    => true

            ]);

        }

        // =====================================
        // CURRENT TRADE
        // =====================================

        $trade = PersonnelTrade::where(
            'personnel_id',
            $person->id
        )
        ->where('is_current', true)
        ->first();

        if ($trade) {

            $trade->update([

                'trade_id' => $validated['trade_id']

            ]);

        } else {

            PersonnelTrade::create([

                'personnel_id' => $person->id,

                'trade_id'     => $validated['trade_id'],

                'start_date'   => now(),

                'is_current'   => true

            ]);

        }

                // =====================================
        // CURRENT APPOINTMENT
        // =====================================

        $appointment = PersonnelAppointment::where(
            'personnel_id',
            $person->id
        )
        ->where('is_current', true)
        ->first();

        if ($appointment) {

            $appointment->update([

                'appointment_id' => $validated['appointment_id']

            ]);

        } else {

            PersonnelAppointment::create([

                'personnel_id'   => $person->id,

                'appointment_id' => $validated['appointment_id'],

                'start_date'     => now(),

                'is_current'     => true

            ]);

        }

        // =====================================
        // EDUCATION
        // =====================================

   $education = PersonnelEducation::where(
    'personnel_id',
    $person->id
)
->orderByDesc('id')
->first();

        if ($education) {

            $education->update([

                'qualification'   => $validated['qualification'],

                'field_of_study'  => $request->field_of_study,

                'institution'     => $request->institution,

                'year_completion' => $request->year_completion

            ]);

        } else {

            PersonnelEducation::create([

                'personnel_id'    => $person->id,

                'qualification'   => $validated['qualification'],

                'field_of_study'  => $request->field_of_study,

                'institution'     => $request->institution,

                'year_completion' => $request->year_completion

            ]);

        }

        // =====================================
        // TRAINING
        // =====================================

        $selectedTrade = Trade::find(
            $validated['trade_id']
        );

        if ($selectedTrade) {

            $training = Training::whereRaw(
                'LOWER(name)=?',
                [strtolower($selectedTrade->name)]
            )->first();

            if (!$training) {

                $training = Training::create([

                    'name' => $selectedTrade->name

                ]);

            }

            $personTraining = PersonnelTraining::where(
                'personnel_id',
                $person->id
            )->first();

            if ($personTraining) {

                $personTraining->update([

                    'training_id'     => $training->id,

                    'military_school' => $request->military_school,

                    'end_date'        => $request->military_year_completion

                ]);

            } else {

                PersonnelTraining::create([

                    'personnel_id'    => $person->id,

                    'training_id'     => $training->id,

                    'military_school' => $request->military_school,

                    'end_date'        => $request->military_year_completion

                ]);

            }

        }

        DB::commit();

        return response()->json([

            'message' => 'Personnel updated successfully'

        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('UPDATE ERROR', [

            'error' => $e->getMessage(),

            'personnel_id' => $person->id

        ]);

        return response()->json([

            'message' => 'Failed to update personnel',

            'error'   => $e->getMessage()

        ], 500);

    }

}

// ==================================================
// DELETE PERSONNEL
// ==================================================

public function destroy(Personnel $person)
{
    DB::beginTransaction();

    try {

        Promotion::where(
            'personnel_id',
            $person->id
        )->delete();

        PersonnelTrade::where(
            'personnel_id',
            $person->id
        )->delete();

        PersonnelAppointment::where(
            'personnel_id',
            $person->id
        )->delete();

        PersonnelEducation::where(
            'personnel_id',
            $person->id
        )->delete();

        PersonnelTraining::where(
            'personnel_id',
            $person->id
        )->delete();

        $person->delete();

        DB::commit();

        return response()->json([

            'message' => 'Personnel deleted successfully'

        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('DELETE ERROR', [

            'error' => $e->getMessage(),

            'personnel_id' => $person->id

        ]);

        return response()->json([

            'message' => 'Failed to delete personnel',

            'error' => $e->getMessage()

        ], 500);

    }
}

//
// ==================================================
// SEARCH PERSONNEL
// ==================================================

public function searchPersonnel(Request $request)
{
    $term = $request->q;

    if (!$term) {
        return response()->json([]);
    }

    $personnel = Personnel::select(
        'id',
        'full_name',
        'service_number'
    )
    ->where('status', 'ACTIVE')
    ->where(function ($query) use ($term) {

        $query->where(
            'full_name',
            'like',
            "%{$term}%"
        )
        ->orWhere(
            'service_number',
            'like',
            "%{$term}%"
        );

    })
    ->orderBy('full_name')
    ->limit(20)
    ->get();

    return response()->json($personnel);
}

public function retirementInformation($id)
{
$person = Personnel::with([
    'currentPromotion.rank',
    'activeRetirementExtension'
])->findOrFail($id);

    if (!$person->currentPromotion) {

        return response()->json([
            'message' => 'Current rank not found.'
        ],404);

    }

    $rule = RetirementRule::where(
        'rank_id',
        $person->currentPromotion->rank_id
    )->first();

    if (!$rule) {

        return response()->json([
            'message' => 'Retirement rule not found.'
        ],404);

    }

    $dob = Carbon::parse(
        $person->date_of_birth
    );

    $currentAge = $dob->age;

 $extensionYears =
    $person->activeRetirementExtension?->years_extended ?? 0;

$retirementAge =
    $rule->retirement_age +
    $extensionYears;

    $retirementDate = $dob
        ->copy()
        ->addYears($retirementAge);

    $today = Carbon::today();

    $status = $person->status;



    $remaining = $today->diff($retirementDate);

    return response()->json([

        'current_age' => $currentAge,

        'rank' => $person->currentPromotion->rank->name,

        'retirement_age' => $retirementAge,

       
         'extension_years' => $extensionYears,

        'retirement_date' =>
            $retirementDate->format('d M Y'),

        'years_left' =>
            $today->greaterThanOrEqualTo($retirementDate)
                ? 0
                : $remaining->y,

        'months_left' =>
            $today->greaterThanOrEqualTo($retirementDate)
                ? 0
                : $remaining->m,

        'days_left' =>
            $today->greaterThanOrEqualTo($retirementDate)
                ? 0
                : $remaining->d,

        'status' => $status

    ]);
}

public function updateStatus(Request $request, $id)
{
    $request->validate([

        'status' =>
        'required|in:RELEASED,TRANSFERRED_OUT,DECEASED'

    ]);

    $personnel = Personnel::findOrFail($id);

    $personnel->status = $request->status;

    $personnel->save();

    return response()->json([

        'message' =>
        'Personnel status updated successfully.'

    ]);
}


public function grantRetirementExtension(Request $request)
{
    $request->validate([

        'personnel_id'     => 'required|exists:personnel,id',

        'years_extended'   => 'required|integer|min:1|max:5',

        'approval_date'    => 'required|date',

        'approved_by'      => 'required|string|max:255',

        'reference_number' => 'nullable|string|max:255',

        'reason'           => 'nullable|string',

        'remarks'          => 'nullable|string'

    ]);

    DB::transaction(function () use ($request) {

        RetirementExtension::where(
            'personnel_id',
            $request->personnel_id
        )->update([
            'is_active' => false
        ]);

        RetirementExtension::create([

            'personnel_id'     => $request->personnel_id,

            'years_extended'   => $request->years_extended,

            'approval_date'    => $request->approval_date,

            'approved_by'      => $request->approved_by,

            'reference_number' => $request->reference_number,

            'reason'           => $request->reason,

            'remarks'          => $request->remarks,

            'is_active'        => true

        ]);

    });

    return response()->json([

        'success' => true,

        'message' => 'Retirement extension granted successfully.'

    ]);
}
}

