<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Transfer;
use App\Models\Personnel;
use App\Models\Appointment;
use App\Models\PersonnelAppointment;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        Log::info('TRANSFER REQUEST RECEIVED', [

            'payload' => $request->all()

        ]);

        $request->validate([

            'from_unit_id'  => 'required|exists:units,id',

            'to_unit_id'    => 'required|exists:units,id',

            'transfer_date' => 'required|date',

            'reason'        => 'required|string',

            'personnel'     => 'required|array|min:1'

        ]);

        if ($request->from_unit_id == $request->to_unit_id) {

            return response()->json([

                'success' => false,

                'message' => 'Source unit and destination unit cannot be the same.'

            ], 422);

        }

        try {

            DB::transaction(function () use ($request) {

                foreach ($request->personnel as $item) {

                    $personnelId = $item['personnel_id'];

                    if (empty($item['appointment_id'])) {

                        throw new \Exception(

                            'Every personnel must have a destination appointment.'

                        );

                    }

                    $appointmentId = $item['appointment_id'];

                    /*
                    |---------------------------------------------------------
                    | Lock Personnel
                    |---------------------------------------------------------
                    */

                    $personnel = Personnel::where(

                        'id',

                        $personnelId

                    )

                    ->lockForUpdate()

                    ->first();

                    if (!$personnel) {

                        throw new \Exception(

                            'Personnel not found.'

                        );

                    }

                    /*
                    |---------------------------------------------------------
                    | Verify Source Unit
                    |---------------------------------------------------------
                    */

                    if (

                        $personnel->unit_id != $request->from_unit_id

                    ) {

                        throw new \Exception(

                            $personnel->full_name .

                            ' is no longer in the selected source unit.'

                        );

                    }

                    /*
                    |---------------------------------------------------------
                    | Verify Appointment
                    |---------------------------------------------------------
                    */

                    $appointment = Appointment::find(

                        $appointmentId

                    );

                    if (!$appointment) {

                        throw new \Exception(

                            'Appointment not found.'

                        );

                    }

                    if (

                        $appointment->unit_id != $request->to_unit_id

                    ) {

                        throw new \Exception(

                            'Selected appointment does not belong to destination unit.'

                        );

                    }

                    /*
                    |---------------------------------------------------------
                    | Prevent Duplicate Transfer
                    |---------------------------------------------------------
                    */

                    $exists = Transfer::where(

                        'personnel_id',

                        $personnelId

                    )

                    ->whereDate(

                        'transfer_date',

                        $request->transfer_date

                    )

                    ->where(

                        'to_unit_id',

                        $request->to_unit_id

                    )

                    ->exists();

                    if ($exists) {

                        continue;

                    }

                    /*
                    |---------------------------------------------------------
                    | Save Transfer
                    |---------------------------------------------------------
                    */

                    Transfer::create([

                        'personnel_id'  => $personnelId,

                        'from_unit_id'  => $request->from_unit_id,

                        'to_unit_id'    => $request->to_unit_id,

                        'transfer_date' => $request->transfer_date,

                        'reason'        => $request->reason

                    ]);

                    /*
                    |---------------------------------------------------------
                    | Update Personnel Unit
                    |---------------------------------------------------------
                    */

                    $personnel->unit_id = $request->to_unit_id;

                    $personnel->save();

                    /*
                    |---------------------------------------------------------
                    | Close Current Appointment
                    |---------------------------------------------------------
                    */

                    PersonnelAppointment::where(

                        'personnel_id',

                        $personnelId

                    )

                    ->where(

                        'is_current',

                        true

                    )

                    ->update([

                        'is_current' => false,

                        'end_date'   => $request->transfer_date

                    ]);

                    /*
                    |---------------------------------------------------------
                    | Create New Appointment
                    |---------------------------------------------------------
                    */

                    PersonnelAppointment::create([

                        'personnel_id'   => $personnelId,

                        'appointment_id' => $appointmentId,

                        'start_date'     => $request->transfer_date,

                        'is_current'     => true

                    ]);

                    Log::info('PERSONNEL TRANSFERRED', [

                        'personnel_id' => $personnelId,

                        'from_unit'    => $request->from_unit_id,

                        'to_unit'      => $request->to_unit_id

                    ]);

                }

            });

            return response()->json([

                'success' => true,

                'message' => 'Transfer completed successfully.',

                'total_personnel' => count($request->personnel),

                'from_unit_id' => $request->from_unit_id,

                'to_unit_id' => $request->to_unit_id

            ]);

        } catch (\Exception $e) {

            Log::error('TRANSFER FAILED', [

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
}