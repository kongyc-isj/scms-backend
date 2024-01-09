<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Component;
use App\Models\Board;
use App\Models\FieldKey;
use App\Models\FieldData;
use App\Models\FieldType;

class FieldKeyController extends Controller
{
    public function field_key_permission($component_id, $email, $data, $field_key_id, $method)
    {
        $component = Component::where('_id', $component_id)
            ->where('deleted_at', null)
            ->first();   

        if (!$component) {
            return response()->json(['message' => 'Component not found'], 404);
        }

        $board = Board::where('_id', $component->board_id)
            ->where('deleted_at', null)
            ->first();  

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 404);
        }

        $owner_board  = Board::where('board_owner_user.board_owner_email', $email)->first();
        $shared_board = Board::where('board_shared_user', 'elemMatch', ['board_shared_user_email' => $email])->first();


        if ($owner_board) 
        {
            if($method == "store")
            {
                $field_key = FieldKey::create($data); 

                $this->insert_field_key_to_field_data($field_key, $board);

                return response()->json(['message' => 'Field Key created successfully'], 200);
            }
            elseif($method == "index")
            {
                $field_key = FieldKey::where('component_id', $component_id)
                    ->where('deleted_at', null)
                    ->get(['_id', 'field_key_name', 'field_key_description', 'field_type_name']); 

                return response()->json(['field_key' => $field_key, 'message' => 'Field Key read successfully'], 200);
                }
            elseif($method == "show")
            {
                $field_key = FieldKey::where('_id', $field_key_id)
                    ->where('deleted_at', null)
                    ->first(['_id', 'field_key_name', 'field_key_description', 'field_type_name']);

                return response()->json(['field_key' => $field_key, 'message' => 'Field Key show successfully'], 200);
            }
            elseif($method == "update")
            {
                $field_key = FieldKey::where('_id', $field_key_id)
                    ->where('deleted_at', null)
                    ->first();          

                $field_key->update($data);

                return response()->json(['field_key' => $field_key, 'message' => 'Field Key updated successfully'], 200);
            }    
            elseif($method == "destroy")
            {
                $field_key = FieldKey::where('_id', $field_key_id)
                    ->where('deleted_at', null)
                    ->first();              

                $field_key->update($data);

                $this->delete_field_key_from_field_data($field_key, $board);

                return response()->json(['field_key' => $field_key, 'message' => 'Field Key deleted successfully'], 200);
            }            
            else
            {
                return response()->json(['message' => 'method not found'], 404);
            }
        } 
        elseif ($shared_board) 
        {
            // Convert the array to a collection
            $sharedUserCollection = collect($shared_board['board_shared_user']);
        
            // Find the specific shared user that matches the provided email
            $sharedUser = $sharedUserCollection->firstWhere('board_shared_user_email', $email);

            if($method == "store")
            {
                if ($sharedUser['board_shared_user_create_access'] == 1) 
                {
                    $field_key = FieldKey::create($data); 
                    $this->insert_field_key_to_field_data($field_key, $board);

                    return response()->json(['message' => 'Field Key created successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 404);
                }
            }
            elseif($method == "index")
            {
                if ($sharedUser['board_shared_user_read_access'] == 1) 
                {
                    $field_key = FieldKey::where('component_id', $component_id)
                    ->where('deleted_at', null)
                    ->get(['_id', 'field_key_name', 'field_key_description']); 

                    return response()->json(['field_key' => $field_key, 'message' => 'Field Key read successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 404);
                }
            }
            elseif($method == "show")
            {
                if ($sharedUser['board_shared_user_read_access'] == 1) {

                    $field_key = FieldKey::where('_id', $field_key_id)
                    ->where('deleted_at', null)
                    ->first();

                    return response()->json(['field_key' => $field_key, 'message' => 'Field Key show successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 404);
                }
            }
            elseif($method == "update")
            {
                if ($sharedUser['board_shared_user_update_access'] == 1) {

                    $field_key = FieldKey::where('_id', $field_key_id)
                    ->where('deleted_at', null)
                    ->first();          

                    $field_key->update($data);

                    return response()->json(['field_key' => $field_key, 'message' => 'Field Key updated successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 404);
                }
            }
            elseif($method == "destroy")
            {
                if ($sharedUser['board_shared_user_destroy_access'] == 1) {

                    $field_key = FieldKey::where('_id', $field_key_id)
                        ->where('deleted_at', null)
                        ->first();              

                    $field_key->update($data);

                    return response()->json(['field_key' => $field_key, 'message' => 'Field Key deleted successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 404);
                }
            }
            else
            {
                return response()->json(['message' => 'method not found'], 404);
            }
        } 
        else 
        {
            return response()->json(['message' => 'Email does not exist'], 404);
        }  
    }

    public function index(Request $request)
    {
        try {       
     
            $request->validate([
                'component_id' => 'required|string'
            ]);

            $data = $request->all();

            $component_id = $data['component_id'];
        
            return $this->field_key_permission($component_id, $request['email'], null, null, 'index');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }    
    }

    public function show(Request $request, $id)
    {
        try {
            $field_key = FieldKey::where('_id', $id)
                ->where('deleted_at', null)
                ->first();  

            if (!$field_key) {
                return response()->json(['message' => 'Field key not found'], 404);
            }
            
            $data = $request->all();

            return $this->field_key_permission($field_key->component_id, $request['email'], null, $id, 'show');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }   
    }

    public function store(Request $request)
    {
        try {
        // Validate the request
            $request->validate([
                'component_id'          => 'required|string',
                'field_type_name'       => 'required|string', 
                'field_key_name'        => 'required|string',
                'field_key_description' => 'required|string',
            ]);
            $data = $request->all();

            $data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at'] = null;
            $data['deleted_at'] = null;

            $component = Component::where('_id', $request['component_id'])
                ->where('deleted_at', null)
                ->first();  
                
            $field_key = FieldKey::where('field_key_name', $request['field_key_name'])
                ->where('component_id', $component->_id)
                ->where('deleted_at', null)
                ->first();   

            if (!empty($field_key)) {
                return response()->json(['message' => 'Field key name is used'], 422);
            }

            $field_type = FieldType::where('deleted_at', null)
                ->get(['field_type_name']);     
            
            $field_type_exist = false;

            foreach ($field_type as $each_field_type) {
                if ($each_field_type['field_type_name'] === $request['field_type_name']) {
                    $field_type_exist = true;
                    break;
                }
            }

            if (!$field_type_exist) {
                return response()->json(['error' => 'Field type no found'], 500);
            }

            return $this->field_key_permission($request['component_id'], $request['email'], $data, null, 'store');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }   
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'field_key_name'        => 'required|string',
                'field_key_description' => 'required|string'
            ]);

            $data               = $request->only(['field_key_name', 'field_key_description']);
            $data['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

            // Find the field key by ID
            $field_key = FieldKey::where('_id', $id)
                ->where('deleted_at', null)
                ->first();     

            if (!$field_key) {
                return response()->json(['message' => 'Field key not found'], 404);
            }

            $component = Component::where('_id', $field_key->component_id)
            ->where('deleted_at', null)
            ->first();  
            
            $check_field_key = FieldKey::where('field_key_name', $request['field_key_name'])
                ->where('component_id', $component['_id'])
                ->where('_id', '!=', $field_key['_id']) // Use '!=' to check not equal
                ->where('deleted_at', null)
                ->first();   

            if (!empty($check_field_key)) {
                return response()->json(['message' => 'Field key name is used'], 422);
            }

            // Update the field key
            return $this->field_key_permission($field_key->component_id, $request['email'], $data, $id, 'update');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => "$e"], 500);
        } 
    }

    public function destroy(Request $request, $id)
    {
        try {

            $data['deleted_at'] = Carbon::now()->format('Y-m-d H:i:s');

            $field_key = FieldKey::where('_id', $id)
                ->where('deleted_at', null)
                ->first();   

            if (!$field_key) {
                return response()->json(['message' => 'Field key not found'], 404);
            }

            return $this->field_key_permission($field_key->component_id, $request['email'], $data, $id, 'destroy');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => "$e"], 500);
        } 
    }

    public function insert_field_key_to_field_data($field_key, $board)
    {
        $field_data = FieldData::where('component_id', $field_key->component_id)
        ->where('deleted_at', null)
        ->first();   

        $field_key_format = [
            $field_key->_id => ""
        ];

        if (empty($field_data)) {

            $language_code          = $board->board_default_language_code;

            $field_key_value_format = [
                $language_code => $field_key_format
            ];

            $data['component_id']    = $field_key->component_id;
            $data['field_key_value'] = $field_key_value_format;
            $data['created_at']      = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at']      = null;
            $data['deleted_at']      = null;

            FieldData::create($data);
        }
        else {
            $field_key_value_formats = [];

            $field_key_value_list = $field_data->field_key_value;

            foreach ($field_key_value_list as $language_code => $language_field_data) {

                // Add the new field key id only if it doesn't exist in the current language subarray
                if (!isset($language_field_data[$field_key->_id])) {

                    $merge = array_merge_recursive($language_field_data, $field_key_format);

                    $field_key_value_format = [
                        $language_code => $merge
                    ];

                    $field_key_value_formats[] = $field_key_value_format;
                }
            }

            $data['field_key_value'] = array_merge(...$field_key_value_formats);

            $field_data->update($data);
        }
    }

    public function delete_field_key_from_field_data($field_key, $board)
    {
        $field_data = FieldData::where('component_id', $field_key->component_id)
        ->where('deleted_at', null)
        ->first();   

        $field_key_value_formats = [];

        $field_key_value_list = $field_data->field_key_value;

        foreach ($field_key_value_list as $language_code => $language_field_data) {
            // Check if the field_key_id exists in the current language subarray
            if (isset($language_field_data[$field_key->_id])) {

                // Remove the specific key from the language subarray
                unset($field_key_value_list[$language_code][$field_key->_id]);

                $field_key_value_formats[] = [
                    $language_code => $field_key_value_list[$language_code]
                ];
            }
        }
        
        $data['field_key_value'] = array_merge(...$field_key_value_formats);
        $field_data->update($data);
    
    }
}