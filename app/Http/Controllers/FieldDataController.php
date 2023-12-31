<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\FieldData;
use App\Models\FieldKey;
use App\Models\Board;
use App\Models\Component;
use App\Models\Language;

class FieldDataController extends Controller
{
    public function field_data_permission($component_id, $email, $request, $method)
    {
        $component = Component::where('_id', $component_id)
            ->where('deleted_at', null)
            ->first();   

        if (!$component) {
            return response()->json(['message' => 'Component not found'], 422);
        }

        $board = Board::where('_id', $component->board_id)
            ->where('deleted_at', null)
            ->first();  

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 422);
        }

        $field_data = FieldData::where('component_id', $component->id)
        ->where('deleted_at', null)
        ->first(); 

        if (!$field_data) {
            return response()->json(['error' => 'Field Data not found'], 422);
        }

        $field_key = FieldKey::where('component_id', $component->_id)
        ->where('deleted_at', null)
        ->get();

        if (!$field_key) {
            return response()->json(['error' => 'Field Key not found'], 422);
        }

        $owner_board           = Board::where('board_owner_user.board_owner_email', $email)->first();
        $shared_board          = Board::where('board_shared_user', 'elemMatch', ['board_shared_user_email' => $email])->first();
        $field_data_list       = $field_data->toArray();
        $field_key_value_list  = $field_data_list['field_key_value'];
        $language_code         = empty($request['language_code']) ? $board->board_default_language_code : $request['language_code'];
        
        if ($owner_board) 
        {
            if($method == "show")
            {
                //if valid language given but it is no recorded then return error
                if (!array_key_exists($language_code, $field_key_value_list)) {
                    return response()->json(['error' => "Language code '$language_code' not found in field data."], 422);
                }

                $data = $field_key_value_list[$language_code];

                //prepare api return format
                $mapped_data = [];
                foreach ($data as $field_key_id => $field_value) {
                    foreach ($field_key as $each_field_key) {
                        if ($each_field_key['_id'] === $field_key_id) {
                            $each_map_data = [
                                "field_key_id" => $field_key_id,
                                "value" => $field_value,
                                "field_key_name" => $each_field_key['field_key_name'],
                                "field_type_name" => $each_field_key['field_type_name'],
                            ];
                            $mapped_data[] = $each_map_data;
                            break;
                        }
                    }
                }
                return response()->json(['field_data' => $mapped_data, 'message' => 'Field data show successfully'], 200);
            }
            elseif($method == "update")
            {
                $validate_field_key = $this->validate_field_key_id($field_key->toArray(), $request['field_key_value']);

                if(!empty($validate_field_key))
                    return $validate_field_key;
    
                //validate field type
                $validate_field_type = $this->validate_field_type($field_key->toArray(), $request['field_key_value']);
    
                if(!empty($validate_field_type))
                    return $validate_field_type;
                
                $language_list = Language::where('deleted_at', null)
                ->get(['language_code']);  

                $language_list = $language_list->toArray();            
                $language_exist = in_array($language_code, array_column($language_list, 'language_code'));

                if (!$language_exist) {
                    return response()->json(['error' => "Language code '$language_code' does not exist in the language data."], 422);
                } 

                $field_key_value_format = [
                    $language_code => $request['field_key_value']
                ];
    
                $data["field_key_value"]  = [];
                $field_key_value_list = $field_data->field_key_value;
    
                // Check if the request language field data stored. If havent store then store it, else update it
                if (array_key_exists($language_code, $field_key_value_list)) {
    
                    $field_key_value_list[$language_code] = array_merge(
                        $field_key_value_list[$language_code],
                        $request['field_key_value']
                    );
                    $data['field_key_value'] = $field_key_value_list;
    
                } else {
                    // Insert the whole object with the new language code
                    $merge = array_merge_recursive($field_key_value_list, $field_key_value_format);
                    $data['field_key_value'] = $merge;    
                }
    
                // Update the data
                $field_data->update($data);
    
                return response()->json(['message' => 'Field data updated successfully'], 200);
            }               
            else
            {
                return response()->json(['message' => 'method not found'], 422);
            }
        } 
        elseif ($shared_board) 
        {
            // Convert the array to a collection
            $sharedUserCollection = collect($shared_board['board_shared_user']);
        
            // Find the specific shared user that matches the provided email
            $sharedUser = $sharedUserCollection->firstWhere('board_shared_user_email', $email);

            if($method == "show")
            {
                if ($sharedUser['board_shared_user_read_access'] == 1) {
                    //if valid language given but it is no recorded then return error
                    if (!array_key_exists($language_code, $field_key_value_list)) {
                        return response()->json(['error' => "Language code '$language_code' not found in field data."], 422);
                    }
                
                    $data = $field_key_value_list[$language_code];
                
                    //prepare api return format
                    $mapped_data = [];
                    foreach ($data as $field_key_id => $field_value) {
                        foreach ($field_key as $each_field_key) {
                            if ($each_field_key['_id'] === $field_key_id) {
                                $each_map_data = [
                                    "field_key_id" => $field_key_id,
                                    "value" => $field_value,
                                    "field_key_name" => $each_field_key['field_key_name'],
                                    "field_type_name" => $each_field_key['field_type_name'],
                                ];
                                $mapped_data[] = $each_map_data;
                                break;
                            }
                        }
                    }
                    return response()->json(['field_data' => $mapped_data, 'message' => 'Field data show successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "update")
            {
                if ($sharedUser['board_shared_user_update_access'] == 1) {

                    $validate_field_key = $this->validate_field_key_id($field_key->toArray(), $request['field_key_value']);

                    if(!empty($validate_field_key))
                        return $validate_field_key;
        
                    //validate field type
                    $validate_field_type = $this->validate_field_type($field_key->toArray(), $request['field_key_value']);
        
                    if(!empty($validate_field_type))
                        return $validate_field_type;
                    
                    $language_list = Language::where('deleted_at', null)
                    ->get(['language_code']);  
    
                    $language_list = $language_list->toArray();            
                    $language_exist = in_array($language_code, array_column($language_list, 'language_code'));
    
                    if (!$language_exist) {
                        return response()->json(['error' => "Language code '$language_code' does not exist in the language data."], 422);
                    } 
    
                    $field_key_value_format = [
                        $language_code => $request['field_key_value']
                    ];
        
                    $data["field_key_value"]  = [];
                    $field_key_value_list = $field_data->field_key_value;
        
                    // Check if the request language field data stored. If havent store then store it, else update it
                    if (array_key_exists($language_code, $field_key_value_list)) {
        
                        $field_key_value_list[$language_code] = array_merge(
                            $field_key_value_list[$language_code],
                            $request['field_key_value']
                        );
                        $data['field_key_value'] = $field_key_value_list;
        
                    } else {
                        // Insert the whole object with the new language code
                        $merge = array_merge_recursive($field_key_value_list, $field_key_value_format);
                        $data['field_key_value'] = $merge;    
                    }
        
                    // Update the data
                    $field_data->update($data);
        
                    return response()->json(['message' => 'Field data updated successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            else
            {
                return response()->json(['message' => 'method not found'], 422);
            }
        } 
        else 
        {
            return response()->json(['message' => 'Email does not exist'], 422);
        }  
    }

    public function show(Request $request)
    {
        $request->validate([
            'component_id'  => 'required|string',
            'language_code' => 'nullable|string'
        ]);

        return $this->field_data_permission($request['component_id'], $request['email'], $request, 'show');
    }

    public function update(Request $request)
    {
        try{
            // Validate the request data
            $request->validate([
                'component_id'    => 'required|string',
                'language_code'   => 'nullable|string',
                'field_key_value' => 'required|array'
            ]);

            return $this->field_data_permission($request['component_id'], $request['email'], $request, 'update');

        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try{
            $request->validate([
                'component_id'  => 'required|string'
            ]);

            $field_data = FieldData::where('component_id', $request['component_id'])
            ->where('deleted_at', null)
            ->first();   

            if (empty($field_data)) {
                return response()->json(['error' => 'Field data no exist'], 422);
            }

            $field_data->delete();

            return response()->json(['message' => 'Field data deleted successfully']);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function validate_field_key_id ($field_key_array, $field_key_value)
    {
        try{
            $expected_ids = array_column($field_key_array, '_id');

            // Check if all expected '_id' values are present in $field_key
            $missing_ids = array_diff($expected_ids, array_keys($field_key_value));
    
            if (!empty($missing_ids)) {
                // Handle the case where some expected '_id' values are missing
                return response()->json(['error' => 'Missing Field Key: ' . implode(', ', $missing_ids)], 422);
            }

            // Check if all '_id' values in $field_data_values exist in $field_key
            $not_found_ids = array_diff(array_keys($field_key_value), array_column($field_key_array, '_id'));

            if (!empty($not_found_ids)) {
    
                // Handle the case where some '_id' values in $field_data_values are not found in $field_key
                return response()->json(['error' => 'Field Key not found: ' . implode(', ', $not_found_ids)], 422);
            }
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

    public function validate_field_type ($field_key_array, $field_key_value)
    {
        try{
            foreach ($field_key_value as $field_key_id => $field_value) {
                // Find the corresponding field key in the collection
                $field_key_collection = collect($field_key_array)->firstWhere('_id', $field_key_id);
            
                if (!$field_key_collection) {
                    return response()->json(['error' => "Field Key with _id: $field_key_id not found"], 422);
                }
            
                // Validate the value based on field_type_name
                $field_type_name = $field_key_collection['field_type_name'];

                if(empty($field_data))
                    break;

                switch ($field_type_name) {
                    case 'short_text':
                    case 'long_text':
                    case 'rich_text':
                    case 'email':
                        if (!is_string($field_value)) {
                            return response()->json(['error' => "Invalid value for $field_type_name field type for _id: $field_key_id"], 422);
                        }
                        break;
                    case 'integer':
                    case 'big_integer':
                    case 'decimal':
                    case 'float':
                        if (!is_numeric($field_value)) {
                            return response()->json(['error' => "Invalid value for $field_type_name field type for _id: $field_key_id"], 422);
                        }
                        break;
                    case 'date':
                    case 'datetime':
                    case 'time':
                        if (!strtotime($field_value)) {
                            return response()->json(['error' => "Invalid value for $field_type_name field type for _id: $field_key_id"], 422);
                        }
                        break;
                    case 'media':
                        // You may add specific validation logic for media type
                        break;
                    case 'boolean':
                        if (!is_bool($field_value)) {
                            return response()->json(['error' => "Invalid value for $field_type_name field type for _id: $field_key_id"], 422);
                        }
                        break;
                    case 'json':
                        // You may add specific validation logic for JSON type
                        break;
                    default:
                        return response()->json(['error' => "Unsupported field_type_name: $field_type_name for _id: $field_key_id"], 422);
                }
            }
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
}
