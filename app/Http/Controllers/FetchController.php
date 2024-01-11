<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Component;
use App\Models\FieldData;
use App\Models\Language;

class FetchController extends Controller
{
    //

    public function get(Request $request)
    {
        $queryParams = $request->query(); // Get all query parameters as an associative array
        logger()->info($request->board->_id);

        $component_name = $request->input('component_name');
        $language_code  = $request->input('language_code');

        $field_data_list = [];

        //first retrieve the field data list (check component_name is given)
        if(empty($component_name))
        {
            $component = Component::where('board_id', $request->board->_id)
            ->where('deleted_at', null)
            ->get(); 

            if (!$component) {
                return response()->json(['message' => 'Component not found'], 422);
            }

            foreach ($component as $component_id => $component_id_value) {

                $field_data = FieldData::where('component_id', $component_id_value['_id'])
                ->where('deleted_at', null)
                ->first(['field_key_value']); 

                if(empty($field_data))
                    continue;
                
                unset($field_data['_id']);
                $field_data = $field_data['field_key_value'];

                $component_field_data = [$component_id_value['component_name'] => $field_data];

                $field_data_list[]    = $component_field_data;
            }
        }
        else
        {
            $component = Component::where('component_name', $component_name)
            ->where('board_id', $request->board->_id)
            ->where('deleted_at', null)
            ->first(); 
            
            if (!$component) {
                return response()->json(['message' => 'Component not found'], 422);
            }
            
            $field_data = FieldData::where('component_id', $component['_id'])
            ->where('deleted_at', null)
            ->first(['field_key_value']); 

            if (!$field_data) {
                return response()->json(['message' => 'Field not found'], 422);
            }

            unset($field_data['_id']);
            $field_data = $field_data['field_key_value'];

            $component_field_data = [$component['component_name'] => $field_data];
            $field_data_list[]    = $component_field_data;
        }

        if (empty($field_data_list)){
            return response()->json(['data' => [], 'message' => 'No field data not found'], 422);
        }

        $field_data_language = [];


        foreach ($field_data_list as $each_field_data) {
            $output_each_field_data = [];
        
            foreach ($each_field_data as $each_field_data_name => $each_field_data_value) {
                if (empty($language_code)) {
                    $output_each_field_data[$each_field_data_name] = $each_field_data_value;
                } else {
                    $output_each_field_data[$each_field_data_name] = [
                        $language_code => isset($each_field_data_value[$language_code]) ? $each_field_data_value[$language_code] : []
                    ];
                }
            }
            $field_data_language[] = $output_each_field_data;
        }

        if($queryParams){
            if ($request->has('filter')) {

                $filters = $queryParams['filter'];                

                $filtered_field_data_list = array_map(function ($field_data) use ($filters) {
                    $filtered_component = [];
                
                    foreach ($field_data as $field_data_key => $field_data_value) {
                        $filtered_languages = [];
                    
                        foreach ($field_data_value as $language => $language_data) {
                            $filtered_keys = [];
                    
                            foreach ($filters as $key => $value) {
                                if ($value === null) {
                                    //$filtered_keys[$key] = isset($language_data[$key]) ? $language_data[$key] : null;
                    
                                    if (isset($language_data[$key])){
                                        $filtered_keys[$key] = $language_data[$key];
                                    }
                                    else{
                                        unset($filtered_keys[$key]);
                                    }
                    
                                } elseif (isset($language_data[$key]) && $language_data[$key] === $value) {
                                    $filtered_keys[$key] = $value;
                                }
                            }
                            $filtered_languages[$language] = $filtered_keys;
                        }
                        // Include the component only if it has languages
                        if (!empty($filtered_languages)) {
                            $filtered_component[$field_data_key] = $filtered_languages;
                        }
                    }
                
                    return $filtered_component;
                }, $field_data_language);        

                $field_data_language = $filtered_field_data_list;
            }
        }

        return response()->json(['data' => $field_data_language, 'message' => 'Data show successfully'], 200);
    }
}