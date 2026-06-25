<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    // =============================
    // 📄 GET ALL (OPTIONAL)
    // =============================
    public function index()
    {
        return Appointment::latest()->get();
    }

    // =============================
    // 📄 GET BY UNIT
    // =============================
    public function byUnit($unit_id)
    {
        return Appointment::where('unit_id', $unit_id)
            ->orderBy('name', 'asc')
              ->paginate(15); 
    }

    // =============================
    // 🔍 VIEW SINGLE
    // =============================
    public function show($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found'
            ], 404);
        }

        return $appointment;
    }

    // =============================
    // ➕ CREATE
    // =============================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'unit_id' => 'required|exists:units,id'
        ]);

        $appointment = Appointment::create([
            'name' => $request->name,
            'unit_id' => $request->unit_id
        ]);

        return response()->json([
            'message' => 'Appointment created successfully',
            'data' => $appointment
        ], 201);
    }

    // =============================
    // ✏️ UPDATE
    // =============================
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:191'
        ]);

        $appointment->update([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'data' => $appointment
        ]);
    }

    // =============================
    // 🗑️ DELETE (OPTIONAL)
    // =============================
    public function destroy($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found'
            ], 404);
        }

        $appointment->delete();

        return response()->json([
            'message' => 'Appointment deleted successfully'
        ]);
    }
}