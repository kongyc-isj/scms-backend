<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\MediaGallery;
use App\Models\Board;
use Carbon\Carbon;

class MediaController extends Controller
{
    public function media_permission($board_id, $email, $data, $id, $method)
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
                $media_data = MediaGallery::create(['media_name' => $data['media_name'], 'board_id' =>  $board_id ]);

                // File was successfully stored
                $media_url = 'staging/dist/img/' . $media_data->_id . '.' . $data['file']->getClientOriginalExtension();
    
                //upload image to s3-
                $result = Storage::disk('s3')->put($media_url, file_get_contents($data['file']), 'public');
    
                if($result)
                {
                    //prepare media url to store in db
                    $store_data = ['media_url'   => Storage::disk('s3')->url($media_url)];
                    $media_data->update($store_data);
    
                    return response()->json(['message' => 'Media uploaded successfully'], 200);
                }
                else
                {
                    return response()->json(['message' => 'Failed to store to server'], 500);
                }
            }

            elseif($method == "index")
            {
                $media_gallery = MediaGallery::where('board_id', $board_id)
                    ->where('deleted_at', null)
                    ->get(['_id', 'media_name', 'media_url']); 

                logger(222222);

                return response()->json(['media' => $media_gallery, 'message' => 'Media read successfully'], 200);
            }

            elseif($method == "show")
            {
                $media_gallery = MediaGallery::where('_id', $id)
                    ->where('deleted_at', null)
                    ->first(['_id', 'media_name', 'media_url']);     
                
                return response()->json(['media' => $media_gallery, 'message' => 'Media show successfully'], 200);
            }

            elseif($method == "update")
            {
                $media_data = MediaGallery::where('_id', $id)
                    ->where('deleted_at', null)
                    ->first();     

                if (!$media_data) {
                    return response()->json(['message' => 'Media not found'], 422);
                }

                $media_url = 'staging/dist/img/' . $media_data->_id . '.' . $data['file']->getClientOriginalExtension();

                $result    = Storage::disk('s3')->put($media_url, file_get_contents($data['file']), 'public');

                if($result)
                {
                    //prepare media url to store in db
                    $data[] = ['media_url'   => Storage::disk('s3')->url($media_url)];

                    $media_data->update($data);

                    return response()->json(['message' => 'Media updated successfully'], 200);
                }
                else
                {
                    return response()->json(['message' => 'Failed to update media to server'], 422);
                }
            }    
            
            elseif($method == "destroy")
            {
                $media_data = MediaGallery::where('board_id', $id)
                    ->where('deleted_at', null)
                    ->get(['_id']);     
                
                if (empty($media_data)) {
                    return response()->json(['message' => 'Media data not found'], 422);
                }
            
                // Get the array of shared users to delete
                $media_id_to_delete = $data['media_id'];
            
                $existing_media_ids = MediaGallery::whereIn('_id', $media_id_to_delete)
                    ->whereNull('deleted_at')
                    ->pluck('_id')
                    ->toArray();
            
                // Check if any requested IDs are not found
                $missing_ids = array_diff($media_id_to_delete, $existing_media_ids);
            
                if (!empty($missing_ids)) {
                    return response()->json(['error' => 'Media with the following IDs not found: ' . implode(', ', $missing_ids)], 404);
                }
            
                MediaGallery::whereIn('_id', $media_id_to_delete)->update(['deleted_at' => now()]);
            
                return response()->json(['message' => 'Media deleted successfully'], 200);
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
            logger($sharedUser);
            if($method == "store")
            {
                if ($sharedUser['board_shared_user_create_access'] == 1) 
                {
                    $media_data = MediaGallery::create(['media_name' => $data['media_name'], 'board_id' =>  $board_id ]);

                    // File was successfully stored
                    $media_url = 'staging/dist/img/' . $media_data->_id . '.' . $data['file']->getClientOriginalExtension();
        
                    //upload image to s3-
                    $result = Storage::disk('s3')->put($media_url, file_get_contents($data['file']), 'public');
        
                    if($result)
                    {
                        //prepare media url to store in db
                        $data = ['media_url'   => Storage::disk('s3')->url($media_url)];
                        $media_data->update($data);
        
                        return response()->json(['message' => 'Media uploaded successfully'], 200);
                    }
                    else
                    {
                        return response()->json(['message' => 'Failed to store to server'], 500);
                    }
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
                    $media_gallery = MediaGallery::where('board_id', $board_id)
                        ->where('deleted_at', null)
                        ->get(['_id', 'media_name', 'media_url']); 
                    logger(222222);
                    return response()->json(['media' => $media_gallery, 'message' => 'Media read successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "show")
            {
                if ($sharedUser['board_shared_user_read_access'] == 1) {

                    $media_gallery = MediaGallery::where('_id', $id)
                        ->where('deleted_at', null)
                        ->first(['_id', 'media_name', 'media_url']);     
                    
                    return response()->json(['media' => $media_gallery, 'message' => 'Media show successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "update")
            {
                if ($sharedUser['board_shared_user_update_access'] == 1) {

                    $media_data = MediaGallery::where('_id', $id)
                        ->where('deleted_at', null)
                        ->first();     

                    if (!$media_data) {
                        return response()->json(['message' => 'Media not found'], 422);
                    }

                    $media_url = 'staging/dist/img/' . $media_data->_id . '.' . $data['file']->getClientOriginalExtension();

                    $result    = Storage::disk('s3')->put($media_url, file_get_contents($data['file']), 'public');

                    if($result)
                    {
                        //prepare media url to store in db
                        $data[] = ['media_url'   => Storage::disk('s3')->url($media_url)];

                        $media_data->update($data);

                        return response()->json(['message' => 'Media updated successfully'], 200);
                    }
                    else
                    {
                        return response()->json(['message' => 'Failed to update media to server'], 422);
                    }
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "destroy")
            {
                if ($sharedUser['board_shared_user_delete_access'] == 1) {

                    $media_data = MediaGallery::where('board_id', $id)
                        ->where('deleted_at', null)
                        ->get(['_id']);     
                    
                    if (empty($media_data)) {
                        return response()->json(['message' => 'Media data not found'], 422);
                    }
                
                    // Get the array of shared users to delete
                    $media_id_to_delete = $data['media_id'];
                
                    $existing_media_ids = MediaGallery::whereIn('_id', $media_id_to_delete)
                        ->whereNull('deleted_at')
                        ->pluck('_id')
                        ->toArray();
                
                    // Check if any requested IDs are not found
                    $missing_ids = array_diff($media_id_to_delete, $existing_media_ids);
                
                    if (!empty($missing_ids)) {
                        return response()->json(['error' => 'Media with the following IDs not found: ' . implode(', ', $missing_ids)], 404);
                    }
                
                    MediaGallery::whereIn('_id', $media_id_to_delete)->update(['deleted_at' => now()]);
                
                    return response()->json(['message' => 'Media deleted successfully'], 200);
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


    public function store(Request $request)
    {
        try {
            $request->validate([
                'board_id'   => 'required|string',
                'media_name' => 'required|string',
                'file'       => 'required|file|mimes:jpeg,png,gif,mp4,mov,avi,wmv'
            ]);
            $email      = $request->input('email');
            $file       = $request->file('file');
            $board_id   = $request->input('board_id');
            $media_name = $request->input('media_name');

            $data       = $request->all();

            //create new data to media gallery table 3

            return $this->media_permission($board_id, $email, $data, null, 'store');


        } catch (\Exception $e) {
            logger($e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }

    public function index(Request $request)
    {
        try {       
            $request->validate([
                'board_id' => 'required|string'
            ]);

            $data = $request->all();

            $board_id = $data['board_id'];
            $email    = $data['email'];
            logger(11111);

            return $this->media_permission($board_id, $email, $data, null, 'index');

        
        } catch (\Exception $e) {
            logger($e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }    
    }

    public function show(Request $request, $id)
    {
        try {
            $data  = $request->all();
            $email = $data['email'];

            $media_data = MediaGallery::where('_id', $id)
                ->where('deleted_at', null)
                ->first(); 

            if(empty($media_data)){
                return response()->json(['message' => 'Media not found'], 422);
            }

            return $this->media_permission($media_data->board_id, $email, $data, $id, 'show');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }   
    }

    public function update_media(Request $request, $id)
    {
        try{
            $request->validate([
                'media_name' => 'required|string',
                'file'       => 'required|file|mimes:jpeg,png,gif,mp4,mov,avi,wmv'
            ]);
            
            $data = $request->all();
            $email = $data['email'];

            $media_data = MediaGallery::where('_id', $id)
                ->where('deleted_at', null)
                ->first(); 

            if(empty($media_data)){
                return response()->json(['message' => 'Media not found'], 422);
            }

            $data['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

            return $this->media_permission($media_data->board_id, $email, $data, $id, 'update');


        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 422);
        }   
    }

    public function destroy(Request $request, $board_id)
    {
        try {
            $request->validate([
                'media_id' => 'required|array',
                'media_id.*' => 'required|string',
            ]);

            $email = $request->input('email');
            $data  = $request->all();

            return $this->media_permission($board_id, $email, $data, null, 'destroy');

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
