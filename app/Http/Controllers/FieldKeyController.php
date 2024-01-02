<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Component;
use App\Models\Board;
use App\Models\FieldKey;

class FieldKeyController extends Controller
{
    public function field_key_permission($component_id, $email, $data, $field_key_id, $method)
    {
        $component = Component::where('_id', $component_id)
            ->where('deleted_at', null)
            ->first();   

        logger()->info($component_id);

        logger()->info($component);

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
                logger()->info($field_key);
                return response()->json(['field_key' => $field_key, 'message' => 'Field Key created successfully'], 200);
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

                    return response()->json(['field_key' => $field_key, 'message' => 'Field Key created successfully'], 200);
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

            $component_id = $data['component_id'];
            $email        = $data['email'];

            $data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at'] = null;
            $data['deleted_at'] = null;

            return $this->field_key_permission($component_id, $request['email'], $data, null, 'store');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }   
    }

    public function update(Request $request, $id)
    {
        try {
        // Validate the request
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
}