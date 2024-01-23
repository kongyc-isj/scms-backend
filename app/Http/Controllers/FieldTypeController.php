<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use App\Models\FieldType;

class FieldTypeController extends Controller
{
    public function index()
    {
        try {
            $fieldTypes = FieldType::all(['field_type_tag', 'field_type_name', 'field_type_description']);
            
            $formattedFieldTypes = [];
            
            foreach ($fieldTypes as $fieldType) {
                $tag = $fieldType['field_type_tag'];
                $name = $fieldType['field_type_name'];
                $description = $fieldType['field_type_description'];
                
                // Check if the tag is already in the array, if not, initialize it
                if (!isset($formattedFieldTypes[$tag])) {
                    $formattedFieldTypes[$tag] = [];
                }
    
                // Add the entry directly to the array
                $formattedFieldTypes[$tag][] = [
                    'name' => $name,
                    'description' => $description,
                ];
            }
            
            return response()->json(['field_types' => $formattedFieldTypes], 200);
            
        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
