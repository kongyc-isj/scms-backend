<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FieldDataController extends Controller
{
    public function index()
    {
        $fieldData = FieldData::all();
        return response()->json($fieldData);
    }

    public function show($id)
    {
        $fieldData = FieldData::find($id);

        if (!$fieldData) {
            return response()->json(['error' => 'FieldData not found'], 404);
        }

        return response()->json($fieldData);
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'component_id' => 'required|string',
            'language_code' => 'required|array',
            'field_key_value' => 'required|array',
        ]);

        // Create a new FieldData instance
        $fieldData = new FieldData();
        $fieldData->fill($request->all());
        $fieldData->save();

        return response()->json($fieldData, 201);
    }

    public function update(Request $request, $id)
    {
        $fieldData = FieldData::find($id);

        if (!$fieldData) {
            return response()->json(['error' => 'FieldData not found'], 404);
        }

        // Validate the request data
        $request->validate([
            'component_id' => 'string',
            'language_code' => 'array',
            'field_key_value' => 'array',
        ]);

        // Update the FieldData instance
        $fieldData->update($request->all());

        return response()->json($fieldData);
    }

    public function destroy($id)
    {
        $fieldData = FieldData::find($id);

        if (!$fieldData) {
            return response()->json(['error' => 'FieldData not found'], 404);
        }

        $fieldData->delete();

        return response()->json(['message' => 'FieldData deleted successfully']);
    }
}
