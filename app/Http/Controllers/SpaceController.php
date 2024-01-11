<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Space;
use App\Models\Board;

class SpaceController extends Controller
{
    // Create a new space
    public function store(Request $request)
    {
        try { 
            $request->validate([
                'space_name' => 'required|string',
                'space_description' => 'required|string'
            ]);

            $data = $request->all();

            $data['space_owner_user']['space_owner_user_email'] = $request->email;
            $data['space_shared_user']                       = [];
            $data['created_at']                              = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at']                              = null;
            $data['deleted_at']                              = null;

            $check_space = Space::where('space_name', $data['space_name'])
                ->where('deleted_at', null)
                ->first();  

            if (!empty($check_space)) {
                logger()->info($check_space);
                return response()->json(['message' => 'Space name is used'], 422);
            }

            $space = Space::create($data);

            return response()->json(['space' => $space, 'message' => 'Space created successfully'], 200);
        } 
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }    
    }

    // Show all spaces by owner email
    public function index(Request $request)   
    {
        try { 
            $email = $request->email;

            //retrieve data if have create own space
            $owner_space  = Space::where('space_owner_user.space_owner_user_email', $email)
                ->where('deleted_at', null)
                ->get(['_id', 'space_name', 'space_description']);

            $owner_board  = Board::where('board_owner_user.board_owner_email', $email)
                ->where('deleted_at', null)
                ->get(['space_id']);
            
            $space_ids_owner_board = [];
            foreach ($owner_board as $item) {

                $itemArray = json_decode(json_encode($item), true);
                $space_ids_owner_board[] = $itemArray['space_id'];
            }

            //pass in the space_id list from share board to retrieve the space list
            $space_from_owner_board = Space::whereIn('_id', $space_ids_owner_board)
                ->where('deleted_at', null)
                ->get(['_id', 'space_name', 'space_description']);

            //retrieve data if have been invited to other's board
            $shared_board = Board::where('board_shared_user', 'elemMatch', ['board_shared_user_email' => $email])
                ->where('deleted_at', null)
                ->get(['space_id']);

            //prepare space_id list which is belong to the share board
            $space_ids_shared_board = [];
            foreach ($shared_board as $item) {

                $itemArray = json_decode(json_encode($item), true);
                $space_ids_shared_board[] = $itemArray['space_id'];
            }
            //pass in the space_id list from share board to retrieve the space list
            $space_from_share_board = Space::whereIn('_id', $space_ids_shared_board)
                ->where('deleted_at', null)
                ->get(['_id', 'space_name', 'space_description']);

            //merge the own created space list and get invited share boards' space list

            $merged = [];

            // Merge own created space list
            foreach ($owner_space as $item) {
                $id = $item['_id'];
                if (!isset($merged[$id])) {
                    $merged[$id] = $item;
                }
            }

            foreach ($space_from_owner_board as $item) {
                $id = $item['_id'];
                if (!isset($merged[$id])) {
                    $merged[$id] = $item;
                }
            }
            
            // Merge get invited share boards' space list
            foreach ($space_from_share_board as $item) {
                $id = $item['_id'];
                if (!isset($merged[$id])) {
                    $merged[$id] = $item;
                }
            }
            
            $merged_result = array_values($merged);

            if(empty ($merged_result))
            {
                return response()->json(['space' => [], 'message' => 'No match email with space'], 422);      
            }

            return response()->json(['space' => $merged_result, 'message' => 'Space show successfully'], 200);

        } 
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }   


    // Show specific spaces by owner email
    public function show(Request $request, $id)
    {
        try { 
            $email = $request->email;

            $space = Space::where('_id', $id)
            ->where('deleted_at', null)
            ->first();  

            if (!$space) {
                return response()->json(['message' => 'Space not found'], 422);
            }      

            $owner_space  = Space::where('space_owner_user.space_owner_user_email', $email)
                ->where('_id',$id)
                ->where('deleted_at', null)
                ->first(['_id', 'space_name', 'space_description']);

            $shared_space = Space::where('space_shared_user', 'elemMatch', ['space_shared_user_email' => $email])
                ->where('_id',$id)
                ->where('deleted_at', null)
                ->first(['_id', 'space_name', 'space_description']);

            if (isset($owner_space))
            {
                return response()->json(['space' => $owner_space, 'message' => 'Space show successfully'], 200);
            }
            elseif (isset($shared_space))
            {
                return response()->json(['space' => $shared_space, 'message' => 'Space show successfully'], 200);
            }
            else
            {
                return response()->json(['space' => [], 'message' => 'No match email with space'], 422);
            }
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }
        
    // Update a space
    public function update(Request $request, $id)
    {
        try { 
            $request->validate([
                'space_name' => 'required|string',
                'space_description' => 'required|string'
            ]);

            $email = $request->email;

            $data = $request->only(['space_name', 'space_description']);
            
            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$space) {
                return response()->json(['message' => 'Space not found'], 422);
            }

            $check_space = Space::where('space_name', $data['space_name'])
            ->where('_id', '!=', $id) // Use '!=' to check not equal
            ->where('deleted_at', null)
            ->first(); 

            if (!empty($check_space)) {
                logger()->info($check_space);
                return response()->json(['message' => 'Space name is used'], 422);
            }

            // Check if the provided email matches the space_owner_user_email
            if ($email !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Email does not match space owner email'], 422);
            }

            $space->update($data);

            return response()->json(['message' => 'Space updated successfully'], 200);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }

    // Delete a space by owner email
    public function destroy(Request $request, $id)
    {
        try { 
            $email = $request->email;

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (empty($space)) {
                return response()->json(['message' => 'Space not found'], 422);
            }

            // Check if the provided email matches the space_owner_user_email
            if ($email !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Email does not match space owner email'], 422);
            }

            $data['deleted_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $space->update($data);

            return response()->json(['message' => 'Space deleted successfully'], 200);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }

    public function get_share_user(Request $request, $id)
    {
        try { 
            $email = $request->email;

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$space) {
                return response()->json(['message' => 'Space not found'], 422);
            }

            // Check if the provided email matches the space_owner_user_email
            if ($email !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Email does not match space owner email'], 422);
            }

            $shareUsers = $space['space_shared_user'];

            return response()->json(['space_share_user' => $shareUsers], 200);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }
    
    public function update_share_user(Request $request, $id)
    {
        try{
            $email = $request->email;
      
            $request->validate([
                'new_space_shared_user_emails.*' => 'required|email',
            ]);

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$space) {
                return response()->json(['message' => 'Space not found'], 422);
            }

            // Check if the provided email matches the space_owner_user_email
            if (($email) !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Email does not match space owner email'], 422);
            }

            // Extract the new_space_shared_user_emails array from the request
            $newSpaceSharedUserEmails = $request->input('new_space_shared_user_emails');

            // Remove duplicates from the new_space_shared_user_emails array
            $uniqueEmails = array_unique($newSpaceSharedUserEmails);

            // Check for existing emails in the space_shared_user array
            $existingEmails = array_column($space['space_shared_user'], 'space_shared_user_email');
            $duplicates = array_intersect($uniqueEmails, $existingEmails);

            // If duplicates exist, return a response with the duplicated emails
            if (!empty($duplicates)) {
                return response()->json(['message' => 'Duplicate emails found', 'duplicates' => $duplicates], 422);
            }

            // Push each new unique space_shared_user_email into the space_shared_user array
            foreach ($uniqueEmails as $newSpaceSharedUserEmail) {
                $space->push('space_shared_user', ['space_shared_user_email' => $newSpaceSharedUserEmail]);
            }

            return response()->json(['message' => 'Space share user insert successfully'], 200);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }

    public function delete_share_user(Request $request, $id)
    {
        try{
            $email = $request->email;

            $request->validate([
                'space_shared_user_email' => 'required|email'
            ]);

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$space) {
                return response()->json(['message' => 'Space not found'], 422);
            }

            // Check if the provided email matches the space_owner_user_email
            if ($email !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Email does not match space owner email'], 422);
            }

            $spaceSharedUserEmail = $request->input('space_shared_user_email');

            // Check if the provided space_shared_user_email exists in the array
            $existingEmails = array_column($space['space_shared_user'], 'space_shared_user_email');

            if (!in_array($spaceSharedUserEmail, $existingEmails)) {
                return response()->json(['message' => 'Space shared user email not found in the array'], 422);
            }

            // Remove the specified space_shared_user_email from the array
            $space['space_shared_user'] = array_values(array_filter($space['space_shared_user'], function ($user) use ($spaceSharedUserEmail) {
                return $user['space_shared_user_email'] !== $spaceSharedUserEmail;
            }));

            // Save the updated space document
            $space->save();

            return response()->json(['message' => 'Space shared user deleted successfully'], 200);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }
}