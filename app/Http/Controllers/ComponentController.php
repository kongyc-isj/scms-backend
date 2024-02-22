<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Component;
use App\Models\Board;
use App\Models\AuditLog;

class ComponentController extends Controller
{
    public function component_permission($board_id, $email, $data, $component_id, $method)
    {
        $board = Board::where('_id', $board_id)
            ->where('deleted_at', null)
            ->first();  
        if (!$board) {
            return response()->json(['message' => 'Board not found'], 422);
        }

        $owner_board  = Board::where('board_owner_user.board_owner_email', $email)
                        ->where('_id', $board_id)    
                        ->where('deleted_at', null)
                        ->first();


        $shared_board = Board::where('board_shared_user', 'elemMatch', ['board_shared_user_email' => $email])
                        ->where('_id', $board_id)   
                        ->where('deleted_at', null) 
                        ->first();

        if ($owner_board) 
        {
            if($method == "store")
            {
                $component  = Component::create($data); 

                AuditLog::logAction($owner_board->id, $email, 'create_component', 'Component created with name: ' . $component->component_name);
                
                return response()->json(['component' => $component, 'message' => 'Component created successfully'], 200);
            }
            elseif($method == "index")
            {
                $component = Component::where('board_id', $board_id)
                    ->where('deleted_at', null)
                    ->get(['_id', 'component_name', 'component_description', 'updated_at']); 

                return response()->json(['component' => $component, 'message' => 'Component show successfully'], 200);
                }
            elseif($method == "show")
            {
                $component = Component::where('_id', $component_id)
                    ->where('deleted_at', null)
                    ->first(['_id', 'component_name', 'component_description', 'updated_at']);

                return response()->json(['component' => $component, 'message' => 'Component show successfully'], 200);
            }
            elseif($method == "update")
            {
                $component = Component::where('_id', $component_id)
                    ->where('deleted_at', null)
                    ->first();          

                $component->update($data);

                AuditLog::logAction($owner_board->id, $email, 'update_component', 'Component updated with name: ' . $component->component_name);

                return response()->json(['component' => $component, 'message' => 'Component updated successfully'], 200);
            }    
            elseif($method == "destroy")
            {
                $component = Component::where('_id', $component_id)
                    ->where('deleted_at', null)
                    ->first();              

                $component->update($data);

                AuditLog::logAction($owner_board->id, $email, 'delete_component', 'Component deleted with name: ' . $component->component_name);

                return response()->json(['component' => $component, 'message' => 'Component deleted successfully'], 200);
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

            if($method == "store")
            {
                if ($sharedUser['board_shared_user_create_access'] == 1) 
                {
                    $component = Component::create($data);

                    AuditLog::logAction($shared_board->id, $email, 'create_component', 'Component created with name: ' . $component->component_name);

                    return response()->json(['component' => $component, 'message' => 'Component created successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "index")
            {
                if ($sharedUser['board_shared_user_read_access'] == 1) 
                {
                    $component = Component::where('board_id', $shared_board->_id)
                        ->where('deleted_at', null)
                        ->get(['_id', 'component_name', 'component_description']);                    
                    return response()->json(['component' => $component, 'message' => 'Component read successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "show")
            {
                if ($sharedUser['board_shared_user_read_access'] == 1) {

                    $component = Component::where('_id', $component_id)
                    ->where('deleted_at', null)
                    ->first();          

                    return response()->json(['component' => $component, 'message' => 'Component read successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "update")
            {
                if ($sharedUser['board_shared_user_update_access'] == 1) {

                    $component = Component::where('_id', $component_id)
                        ->where('deleted_at', null)
                        ->first();          

                    $component->update($data);

                    AuditLog::logAction($shared_board->id, $email, 'update_component', 'Component updated with name: ' . $component->component_name);

                    return response()->json(['component' => $component, 'message' => 'Component updated successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "destroy")
            {
                if ($sharedUser['board_shared_user_delete_access'] == 1) {

                    $component = Component::where('_id', $component_id)
                        ->where('deleted_at', null)
                        ->first();          

                    $component->update($data);

                    AuditLog::logAction($shared_board->id, $email, 'delete_component', 'Component deleted with name: ' . $component->component_name);

                    return response()->json(['component' => $component, 'message' => 'Component deleted successfully'], 200);
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

    /**
     * Display a listing of the components.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {       
     
            $request->validate([
                'board_id' => 'required|string'
            ]);

            $data = $request->all();

            $board_id = $data['board_id'];
            $email    = $data['email'];
        
            return $this->component_permission($board_id, $request['email'], null, null, 'index');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }    
    }

    /**
     * Store a newly created component in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'board_id' => 'required|string',
                'component_name' => 'required|string',
                'component_description' => 'sometimes|string'
            ]);

            $data = $request->all();

            $board_id = $data['board_id'];

            $data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at'] = null;
            $data['deleted_at'] = null;

            $check_component = Component::where('component_name', $data['component_name'])
            ->where('board_id', $data['board_id'])
            ->where('deleted_at', null)
            ->first();  

            if (!empty($check_component)) {
                logger()->info($check_component);
                return response()->json(['message' => 'Component name cannot duplicate'], 422);
            }

            return $this->component_permission($board_id, $request['email'], $data, null, 'store');
            
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }   
    }

    /**
     * Display the specified component.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $data = $request->all();

            $component = Component::where('_id', $id)
                ->where('deleted_at', null)
                ->first();     

            return $this->component_permission($component->board_id, $request['email'], null, $id, 'show');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }   
    }

    /**
     * Update the specified component in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $request->validate([
                'component_name'        => 'required|string',
                'component_description' => 'sometimes|string'
            ]);

            $data               = $request->only(['component_name', 'component_description']);
            $data['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

            $component = Component::where('_id', $id)
                ->where('deleted_at', null)
                ->first();     

            if (!$component) {
                return response()->json(['message' => 'Component not found'], 422);
            }

            $check_component = Component::where('component_name', $data['component_name'])
                ->where('board_id', $component['board_id'])
                ->where('_id', '!=', $component['_id']) // Use '!=' to check not equal
                ->where('deleted_at', null)
                ->first(); 

            if (!empty($check_component)) {
                logger()->info($check_component);
                return response()->json(['message' => 'Component name cannot be duplicate'], 422);
            }

            return $this->component_permission($component->board_id, $request['email'], $data, $id, 'update');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }   
    }
    /**
     * Remove the specified component from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try{
            $data['deleted_at'] = Carbon::now()->format('Y-m-d H:i:s');

            $component = Component::where('_id', $id)
                ->where('deleted_at', null)
                ->first(); 

            if (!$component) {
                return response()->json(['message' => 'Component not found'], 422);
            }

            return $this->component_permission($component->board_id, $request['email'], $data, $id, 'destroy');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        } 
    }
}