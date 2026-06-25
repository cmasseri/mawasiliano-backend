<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Structure;
use Illuminate\Support\Facades\Log;
use App\Models\Personnel;


class StructureController extends Controller
{
    // 🔹 LIST
    public function index(Request $request)
    {
        $query = Structure::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json(
            $query->select('id','name','type','parent_id','child_type')->get(),
            200
        );
    }

    // 🔹 STORE
public function store(Request $request)
{
    try {

        Log::info('REQUEST', $request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'type' => 'required|string|in:COMMAND,BRIGADE,UNIT,RESERVE_REGION,RESERVE_DISTRICT',
            'parent_id' => 'nullable|integer|exists:units,id'
        ]);

        // 🔥 pata parent
        $parent = null;
        if ($validated['parent_id']) {
            $parent = Structure::find($validated['parent_id']);
        }

        // 🔥 determine child_type
        $childType = $this->determineChildType(
            $validated['type'],
            $validated['name'],
            $parent
        );

        $structure = Structure::create([
            'name' => $validated['name'],
            'nickname' => $validated['nickname'] ?? null,
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'child_type' => $childType ? json_encode($childType) : null
        ]);

        return response()->json([
            'message' => 'Saved successfully',
            'data' => $structure
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'message' => 'Please fill all required fields correctly'
        ], 422);

    } catch (\Exception $e) {

        Log::error('STORE ERROR: ' . $e->getMessage());

        return response()->json([
            'message' => 'Something went wrong. Please try again.'
        ], 500);
    }
}
    // 🔹 NEXT LEVEL (DATABASE-DRIVEN)
public function getNextLevel($id)
{
    $node = Structure::findOrFail($id);

    $childTypes = json_decode($node->child_type, true);

    if (!$childTypes) {
        $childTypes = [$node->child_type];
    }

    if (!is_array($childTypes)) {
        $childTypes = [$childTypes];
    }

    $data = Structure::where('parent_id', $id)
        ->whereIn('type', $childTypes)
        ->select(
            'id',
            'name',
            'type',
            'parent_id',
            'child_type'
        )
        ->paginate(20);

    $data->getCollection()->transform(function ($item) {

        // UNIT & RESERVE_DISTRICT
        if (
            in_array(
                strtoupper($item->type),
                ['UNIT', 'RESERVE_DISTRICT']
            )
        ) {

            $item->personnel_count =
                Personnel::where(
                    'unit_id',
                    $item->id
                )->count();
        }

        // COMMAND, BRIGADE, REGION etc
        else {

            $item->units_count =
                Structure::where(
                    'parent_id',
                    $item->id
                )->count();
        }

        return $item;
    });

    return response()->json($data);
}


private function determineChildType($type, $name, $parent)
{
    $type = strtoupper($type);
    $name = strtolower($name);

    // ======================
    // COMMAND LOGIC
    // ======================
    if ($type === 'COMMAND') {

        if (str_contains($name, 'land')) {
            return ['BRIGADE', 'UNIT'];
        }

        if (
            str_contains($name, 'air') ||
            str_contains($name, 'navy') ||
            str_contains($name, 'jkt')
        ) {
            return ['UNIT'];
        }

        if (str_contains($name, 'reserve')) {
            return ['RESERVE_REGION'];
        }
    }

    // ======================
    // RESERVE REGION
    // ======================
    if ($type === 'RESERVE_REGION') {
        return ['RESERVE_DISTRICT'];
    }

    // ======================
    // BRIGADE
    // ======================
    if ($type === 'BRIGADE') {
        return ['UNIT'];
    }

    return null;
}
}