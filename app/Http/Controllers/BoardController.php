<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Space;
use App\Models\Board;
use App\Models\Language;
use Ramsey\Uuid\Uuid;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        try
        {
            $request->validate([
                'space_id' => 'required|string'
            ]);

            $email = $request->input('email');

            $owner_board  = Board::where('board_owner_user.board_owner_email', $email)
                ->where('space_id', $request['space_id'])
                ->where('deleted_at', null)
                ->get(['_id', 'board_name', 'board_description', 'board_default_language_code', 'board_api_key', 'board_shared_user']);

            $shared_board = Board::where('board_shared_user', 'elemMatch', ['board_shared_user_email' => $email])
                ->where('space_id', $request['space_id'])
                ->where('deleted_at', null)
                ->get(['_id', 'board_name', 'board_description', 'board_default_language_code', 'board_api_key', 'board_shared_user']);

            $merged = [];

            // Merge own created space list
            foreach ($owner_board as $item) {
                $id = $item['_id'];
                $item['is_owner'] = 1; // Set is_owner to 1 for owner boards
                if (!isset($merged[$id])) {
                    $merged[$id] = $item;
                }
            }
            
            // Merge get invited share boards' space list with is_owner attribute
            foreach ($shared_board as $item) {
                $id = $item['_id'];
                $item['is_owner'] = 0; // Set is_owner to 0 for shared boards
                if (!isset($merged[$id])) {
                    $merged[$id] = $item;
                }
            }

            $merged_result = array_values($merged);

            if(empty ($merged_result))
            {
                return response()->json(['board' => [], 'message' => 'Board show successfully'], 200);      
            }
            $jsonData = json_encode($merged_result); 
            $boards   = json_decode($jsonData, true);

            if(!empty($boards)){
                foreach ($boards as &$board) {
                    foreach ($board['board_shared_user'] as &$user) {
                        $userCopy = $user; // Make a copy of the nested array
                        $userCopy['board_shared_user_create_access'] = (bool) $user['board_shared_user_create_access'];
                        $userCopy['board_shared_user_read_access']   = (bool) $user['board_shared_user_read_access'];
                        $userCopy['board_shared_user_update_access'] = (bool) $user['board_shared_user_update_access'];
                        $userCopy['board_shared_user_delete_access'] = (bool) $user['board_shared_user_delete_access'];
                        $user = $userCopy; // Assign the copy back to the original array
                    }
                }
            }

            return response()->json(['board' => $boards, 'message' => 'Board show successfully'], 200);
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }   
    }

    // Show specific board by owner email
    public function show(Request $request, $id)
    {
        try
        {
            $email = $request->input('email');

            $board = Board::where('_id', $id)
            ->where('deleted_at', null)
            ->first();  

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }      

            $owner_board  = Board::where('board_owner_user.board_owner_email', $email)
                ->where('_id',$id)
                ->where('deleted_at', null)
                ->first(['_id', 'board_name', 'board_description', 'board_default_language_code', 'board_api_key', 'board_shared_user']);

            $shared_board = Board::where('board_shared_user', 'elemMatch', ['board_shared_user_email' => $email])
                ->where('_id',$id)
                ->where('deleted_at', null)
                ->first(['_id', 'board_name', 'board_description', 'board_default_language_code', 'board_api_key', 'board_shared_user']);

            if (isset($owner_board))
            {
                $jsonData = json_encode($owner_board); 
                $owner_board_access   = json_decode($jsonData, true);

                if(!empty ($owner_board_access['board_shared_user']))
                {
                    foreach ($owner_board_access['board_shared_user'] as &$user) {
                        $user['board_shared_user_create_access'] = (bool) $user['board_shared_user_create_access'];
                        $user['board_shared_user_read_access']   = (bool) $user['board_shared_user_read_access'];
                        $user['board_shared_user_update_access'] = (bool) $user['board_shared_user_update_access'];
                        $user['board_shared_user_delete_access'] = (bool) $user['board_shared_user_delete_access'];
                    }
                }

                return response()->json(['board' => $owner_board_access, 'message' => 'Board show successfully'], 200);
            }
            elseif (isset($shared_board))
            {
                $jsonData = json_encode($shared_board); 
                $shared_board_access   = json_decode($jsonData, true);

                if(!empty ($shared_board_access['board_shared_user']))
                {
                    foreach ($shared_board_access['board_shared_user'] as &$user) {
                        $user['board_shared_user_create_access'] = (bool) $user['board_shared_user_create_access'];
                        $user['board_shared_user_read_access']   = (bool) $user['board_shared_user_read_access'];
                        $user['board_shared_user_update_access'] = (bool) $user['board_shared_user_update_access'];
                        $user['board_shared_user_delete_access'] = (bool) $user['board_shared_user_delete_access'];
                    }
                }

                return response()->json(['board' => $shared_board_access, 'message' => 'Board show successfully'], 200);
            }
            else
            {
                return response()->json(['board' => [], 'message' => 'No match email with board'], 422);
            }
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }

    public function store(Request $request)
    {
        try
        {
            $request->validate([
                'space_id' => 'required|string',
                'board_name' => 'required|string',
                'board_description' => 'required|string',
                'board_default_language_code' => 'required|string',
                'board_shared_user' => 'nullable|array',
                'board_shared_user.*.board_shared_user_email' => 'nullable|email',
                'board_shared_user.*.board_shared_user_create_access' => 'nullable|integer',
                'board_shared_user.*.board_shared_user_read_access'   => 'nullable|integer',
                'board_shared_user.*.board_shared_user_update_access' => 'nullable|integer',
                'board_shared_user.*.board_shared_user_delete_access' => 'nullable|integer'
            ]);

            $email    = $request->input('email');
            $data     = $request->all();
            $language = Language::where('language_code', $data['board_default_language_code'])
                ->where('deleted_at', null)
                ->first();  

            if (!$language) {
                return response()->json(['message' => 'Language not found'], 422);
            }   
            
            $board_shared_user = $request['board_shared_user'];

            $data['board_owner_user']['board_owner_email']   = $email;
            $data['board_api_key']                           = Uuid::uuid4()->toString();
            $data['board_owner_user']['board_owner_api_key'] = Uuid::uuid4()->toString();
            $data['board_shared_user']                       = (empty($board_shared_user)) ? [] : $board_shared_user;
            $data['created_at']                              = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at']                              = null;
            $data['deleted_at']                              = null;

            $space = Space::where('_id', $request['space_id'])
                ->where('deleted_at', null)
                ->first();
            
            if (empty($space)) {
                return response()->json(['message' => 'Space no found'], 422);
            }

            if ($email !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Only space owner can create board'], 422);
            }

            $check_board = Board::where('board_name', $data['board_name'])
                ->where('space_id', $data['space_id'])
                ->where('deleted_at', null)
                ->first();  

            if (!empty($check_board)) {
                logger()->info($check_board);
                return response()->json(['message' => 'Board name has been taken in this space'], 422);
            }

            $board = Board::create($data);

            return response()->json(['message' => 'Board created successfully'], 200);

        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }   
    }

    public function update(Request $request, $id)
    {
        try
        {
            $request->validate([
                'board_name'                  => 'required|string',
                'board_description'           => 'required|string',
                'board_shared_user'           => 'sometimes|array'
            ]);

            $email = $request->input('email');
            $data  = $request->only(['board_name', 'board_description']);

            $board  = Board::where('_id', $id)
            ->where('deleted_at', null)
            ->first();

            if (empty($board)) {
                return response()->json(['message' => 'Board no found'], 422);
            }

            // Check if the provided email matches the board_owner_user_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to update board'], 422);
            }

            $check_board_name = Board::where('board_name', $data['board_name'])
                ->where('space_id', $board['space_id'])
                ->where('_id', '!=', $board['_id']) // Use '!=' to check not equal
                ->where('deleted_at', null)
                ->first(); 

            if (!empty($check_board_name)) {
                return response()->json(['message' => 'Board name has been taken in this space'], 422);
            }
            
            $share_user_array  = $request->input('board_shared_user');
            $board_share_users = $board['board_shared_user'];

            $unique_emails = [];
            foreach ($share_user_array as $index => $item) {
                $email = $item['board_shared_user_email'];
                if (in_array($email, $unique_emails)) {
                    return response()->json(['message' => "Duplicate email $email found"], 422);
                }
                $unique_emails[] = $email;
            }

            // Convert db_share_user to associative array for easier lookup
            $board_share_users_assoc = array_column($board_share_users, null, 'board_shared_user_email');
                        
            // Iterate over request_share_user
            foreach ($share_user_array as &$share_user) {
                $email = $share_user['board_shared_user_email'];
            
                // Check if the email exists in db_share_user
                if (isset($board_share_users_assoc[$email])) {
                    // Email exists, update the data
                    $board_share_users_assoc[$email] = $share_user;
                } else {
                    // Email doesn't exist, add to db_share_user
                    $board_share_users_assoc[$email] = $share_user;
                }
            }
            
            // Remove entries from db_share_user that are not in request_share_user
            $board_share_users_assoc = array_filter($board_share_users_assoc, function ($user) use ($share_user_array) {
                return in_array($user, $share_user_array);
            });

            $board_share_users_updated = array_values($board_share_users_assoc);
            logger($board_share_users_updated);

            $data['board_shared_user'] = $board_share_users_updated;
            $board->update($data);

            return response()->json(['message' => 'Board updated successfully'], 200);
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }   
    }

    public function destroy(Request $request, $id)
    {
        try
        {
            $email = $request->input('email');

            $board  = Board::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }

            // Check if the provided email matches the board_owner_user_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to delete board'], 422);
            }

            $data['deleted_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $board->update($data);

            return response()->json(['message' => 'Board deleted successfully'], 200);
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 500);
        }   
    }

    public function get_share_user(Request $request, $id)
    {
        try {
            $email = $request->input('email');

            $board = Board::find($id);

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }

            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to get share user list'], 422);
            }

            $shareUsers = $board['board_shared_user'];

            return response()->json(['board_share_users' => $shareUsers], 200);

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function create_share_user(Request $request, $id)
    {
        try {
            $request->validate([
                'board_shared_user.board_shared_user_email' => 'required|email',
                'board_shared_user.board_shared_user_create_access' => 'required|integer',
                'board_shared_user.board_shared_user_read_access' => 'required|integer',
                'board_shared_user.board_shared_user_update_access' => 'required|integer',
                'board_shared_user.board_shared_user_delete_access' => 'required|integer',
            ]);
            $email = $request->input('email');

            $board = Board::find($id);

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }

            // Check if the provided email matches the board_owner_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to create share user'], 422);
            }

            // Extract the single board_shared_user data from the request
            $board_shared_user = $request->input('board_shared_user');

            // Check if the user already exists based on email
            $existingUserIndex = array_search($board_shared_user['board_shared_user_email'], array_column($board['board_shared_user'], 'board_shared_user_email'));

            if ($existingUserIndex !== false) {
                return response()->json(['message' => 'User with this email already exists'], 422);
            }

            // Add new user data
            $boardSharedUsers = $board['board_shared_user'];
            $boardSharedUsers[] = $board_shared_user;

            // Update the board with the modified board_shared_user array
            $board->update(['board_shared_user' => $boardSharedUsers]);

            return response()->json(['message' => 'Board share user created successfully'], 200);

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update_share_user(Request $request, $id)
    {
        try {
            $request->validate([
                'board_shared_user.board_shared_user_email' => 'required|email',
                'board_shared_user.board_shared_user_create_access' => 'required|integer',
                'board_shared_user.board_shared_user_read_access' => 'required|integer',
                'board_shared_user.board_shared_user_update_access' => 'required|integer',
                'board_shared_user.board_shared_user_delete_access' => 'required|integer',
            ]);
            $email = $request->input('email');

            $board = Board::find($id);

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }

            // Check if the provided email matches the board_owner_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to update share user'], 422);
            }

            // Extract the single board_shared_user data from the request
            $userData = $request->input('board_shared_user');

            // Find the index of the matching shared user based on email
            $index = array_search($userData['board_shared_user_email'], array_column($board['board_shared_user'], 'board_shared_user_email'));

            if ($index !== false) {
                // Update existing user data
                $boardSharedUsers = $board['board_shared_user'];
                $boardSharedUsers[$index] = $userData;

                // Update the board with the modified board_shared_user array
                $board->update(['board_shared_user' => $boardSharedUsers]);

                return response()->json(['message' => 'Board share user updated successfully'], 200);
            } 
            else {
                return response()->json(['message' => 'Shared user not found'], 422);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function delete_share_user(Request $request, $id)
    {
        try {
            $request->validate([
                'board_shared_user_email' => 'required|array',
                'board_shared_user_email.*' => 'required|email',
            ]);
            $email = $request->input('email');

            $board = Board::find($id);

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }

            // Check if the provided email matches the board_owner_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to delete share user'], 422);
            }

            // Get the array of shared users to delete
            $sharedUserEmailsToDelete = $request->input('board_shared_user_email');

            // Extract the emails from the board shared users
            $boardSharedUserEmails = array_column($board['board_shared_user'], 'board_shared_user_email');
            
            // Identify the emails that are not found
            $notFoundEmails = array_diff($sharedUserEmailsToDelete, $boardSharedUserEmails);

            if (!empty($notFoundEmails)) {
                return response()->json(['message' => 'Some emails not found', 'not_found_emails' => $notFoundEmails], 422);
            }
            
            // Remove shared users with matching email addresses
            $boardSharedUsers = array_filter($board['board_shared_user'], function ($user) use ($sharedUserEmailsToDelete) {
                return !in_array($user['board_shared_user_email'], $sharedUserEmailsToDelete);
            });

            // Update the board with the modified board_shared_user array
            $board->update(['board_shared_user' => array_values($boardSharedUsers)]);

            return response()->json(['message' => 'Board shared users deleted successfully'], 200);
        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    
    public function update_api_key(Request $request, $id)
    {
        try{
            $request->validate([
                'email' => 'required|email',
            ]);

            $data = $request->all();
            $data['board_api_key'] =Uuid::uuid4()->toString();

            $board = Board::find($id);

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }

            // Check if the provided email matches the board_owner_user_email
            if ($request->input('email') !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to update board'], 422);
            }

            $board->update($data);

            return response()->json(['message' => $board['board_api_key']], 200);            
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}