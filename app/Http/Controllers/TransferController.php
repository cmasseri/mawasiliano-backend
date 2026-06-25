<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Transfer;
use App\Models\Personnel;
use App\Models\PersonnelAppointment;
class TransferController extends Controller
{
 public function store(Request $request)
{
    Log::info('TRANSFER REQUEST RECEIVED', [
        'payload' => $request->all()
    ]);

    try {

        $request->validate([

            'from_unit_id'  => 'required',

            'to_unit_id'    => 'required',

            'transfer_date' => 'required',

            'personnel'     => 'required|array|min:1'

        ]);

        DB::beginTransaction();

        foreach ($request->personnel as $item) {

            Log::info('PROCESSING PERSONNEL', [
                'item' => $item
            ]);

            $personnelId   = $item['personnel_id'];
            $appointmentId = $item['appointment_id'] ?? null;

            Transfer::create([

                'personnel_id'  => $personnelId,

                'from_unit_id'  => $request->from_unit_id,

                'to_unit_id'    => $request->to_unit_id,

                'transfer_date' => $request->transfer_date,

                'reason'        => $request->reason

            ]);

            Log::info('TRANSFER SAVED', [
                'personnel_id' => $personnelId
            ]);

            Personnel::where('id', $personnelId)

                ->update([

                    'unit_id' => $request->to_unit_id

                ]);

            Log::info('PERSONNEL UNIT UPDATED', [
                'personnel_id' => $personnelId,
                'new_unit' => $request->to_unit_id
            ]);

            if ($appointmentId) {

                PersonnelAppointment::where(
                    'personnel_id',
                    $personnelId
                )
                ->where('is_current', 1)
                ->update([

                    'is_current' => 0,

                    'end_date' => $request->transfer_date

                ]);

                Log::info('OLD APPOINTMENT CLOSED', [
                    'personnel_id' => $personnelId
                ]);

                PersonnelAppointment::create([

                    'personnel_id' => $personnelId,

                    'appointment_id' => $appointmentId,

                    'start_date' => $request->transfer_date,

                    'is_current' => 1

                ]);

                Log::info('NEW APPOINTMENT CREATED', [
                    'personnel_id' => $personnelId,
                    'appointment_id' => $appointmentId
                ]);
            }
        }

        DB::commit();

        Log::info('TRANSFER COMPLETED SUCCESSFULLY');

        return response()->json([
            'success' => true,
            'message' => 'Transfer completed successfully'
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

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
