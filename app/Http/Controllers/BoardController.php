<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
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
                return response()->json(['board' => [], 'message' => 'No match email with board'], 200);      
            }

            return response()->json(['board' => $merged_result, 'message' => 'Board show successfully'], 200);
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
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
                return response()->json(['message' => 'Board not found'], 200);
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
                return response()->json(['board' => $owner_board, 'message' => 'Board show successfully'], 200);
            }
            elseif (isset($shared_board))
            {
                return response()->json(['board' => $shared_board, 'message' => 'Board show successfully'], 200);
            }
            else
            {
                return response()->json(['board' => [], 'message' => 'No match email with board'], 200);
            }
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
        }  
    }

    public function store(Request $request)
    {
        try
        {
            $request->validate([
                'space_id'                           => 'required|string',
                'board_name'                         => 'required|string',
                'board_description'                  => 'required|string',
                'board_default_language_code'        => 'required|string',
                'board_share_user'                   => 'sometimes|string'
            ]);

            $email    = $request->input('email');
            $data     = $request->all();
            $language = Language::where('language_code', $data['board_default_language_code'])
                ->where('deleted_at', null)
                ->first();  

            if (!$language) {
                return response()->json(['message' => 'Language not found'], 200);
            }      
            $data['board_owner_user']['board_owner_email']   = $email;
            $data['board_api_key']                           = Uuid::uuid4()->toString();
            $data['board_owner_user']['board_owner_api_key'] = Uuid::uuid4()->toString();
            $data['created_at']                              = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at']                              = null;
            $data['deleted_at']                              = null;

            $check_board = Board::where('board_name', $data['board_name'])
                ->where('space_id', $data['space_id'])
                ->where('deleted_at', null)
                ->first();  

            if (!empty($check_board)) {
                logger()->info($check_board);
                return response()->json(['message' => 'Board name cannot duplicate'], 200);
            }

            $board = Board::create($data);

            return response()->json(['message' => 'Board created successfully'], 200);

        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
        }   
    }

    public function update(Request $request, $id)
    {
        try
        {
            $request->validate([
                'board_name'                  => 'required|string',
                'board_description'           => 'required|string',
                'board_default_language_code' => 'required|string',
                'board_shared_user'           => 'sometimes|array'
            ]);

            $email = $request->input('email');
            $data  = $request->only(['board_name', 'board_description', 'board_default_language_code']);

            $owner_board  = Board::where('board_owner_user.board_owner_email', $email)
                ->where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (empty($owner_board)) {
                logger()->info($owner_board);
                return response()->json(['message' => 'Board not found'], 200);
            }

            $check_board = Board::where('board_name', $data['board_name'])
                ->where('space_id', $owner_board['space_id'])
                ->where('_id', '!=', $owner_board['_id']) // Use '!=' to check not equal
                ->where('deleted_at', null)
                ->first(); 

            if (!empty($check_board)) {
                logger()->info($check_board);
                return response()->json(['message' => 'Board name cannot be duplicate'], 200);
            }

            $language = Language::where('language_code', $data['board_default_language_code'])
                ->where('deleted_at', null)
                ->first();  

            if (!$language) {
                return response()->json(['message' => 'Language not found'], 200);
            }

            $share_user_array  = $request->input('board_shared_user');
            $board_share_users = $owner_board['board_shared_user'];

            //Update each shared user
            foreach ($share_user_array as $share_user) {
                // Find the index of the matching shared user based on email
                $index = array_search($share_user['board_shared_user_email'], array_column($owner_board['board_shared_user'], 'board_shared_user_email'));

                if ($index !== false) {
                    // Update existing user data
                    $board_share_users[$index] = $share_user;
                } else {
                    return response()->json(['message' => 'Shared user not found'], 200);
                }
            }

            $data['board_shared_user'] = $board_share_users;
            $owner_board->update($data);

            return response()->json(['message' => 'Board updated successfully'], 200);
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
        }   
    }

    public function destroy(Request $request, $id)
    {
        try
        {
            $email = $request->input('email');

            $board = Board::find($id);

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 200);
            }

            // Check if the provided email matches the board_owner_user_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to delete board'], 200);
            }

            $data['deleted_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $board->update($data);

            return response()->json(['message' => 'Board deleted successfully'], 200);
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
        }   
    }

    public function get_share_user(Request $request, $id)
    {
        try {
            $email = $request->input('email');

            $board = Board::find($id);

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 200);
            }

            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to get share user list'], 200);
            }

            $shareUsers = $board['board_shared_user'];

            return response()->json(['board_share_users' => $shareUsers], 200);

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
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
                return response()->json(['message' => 'Board not found'], 200);
            }

            // Check if the provided email matches the board_owner_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to create share user'], 200);
            }

            // Extract the single board_shared_user data from the request
            $board_shared_user = $request->input('board_shared_user');

            // Check if the user already exists based on email
            $existingUserIndex = array_search($board_shared_user['board_shared_user_email'], array_column($board['board_shared_user'], 'board_shared_user_email'));

            if ($existingUserIndex !== false) {
                return response()->json(['message' => 'User with this email already exists'], 200);
            }

            // Add new user data
            $boardSharedUsers = $board['board_shared_user'];
            $boardSharedUsers[] = $board_shared_user;

            // Update the board with the modified board_shared_user array
            $board->update(['board_shared_user' => $boardSharedUsers]);

            return response()->json(['message' => 'Board share user created successfully'], 200);

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
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
                return response()->json(['message' => 'Board not found'], 200);
            }

            // Check if the provided email matches the board_owner_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to update share user'], 200);
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
                return response()->json(['message' => 'Shared user not found'], 200);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
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
                return response()->json(['message' => 'Board not found'], 200);
            }

            // Check if the provided email matches the board_owner_email
            if ($email !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to delete share user'], 200);
            }

            // Get the array of shared users to delete
            $sharedUserEmailsToDelete = $request->input('board_shared_user_email');

            // Extract the emails from the board shared users
            $boardSharedUserEmails = array_column($board['board_shared_user'], 'board_shared_user_email');
            
            // Identify the emails that are not found
            $notFoundEmails = array_diff($sharedUserEmailsToDelete, $boardSharedUserEmails);

            if (!empty($notFoundEmails)) {
                return response()->json(['message' => 'Some emails not found', 'not_found_emails' => $notFoundEmails], 200);
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
            return response()->json(['message' => $e], 200);
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
                return response()->json(['message' => 'Board not found'], 200);
            }

            // Check if the provided email matches the board_owner_user_email
            if ($request->input('email') !== $board['board_owner_user']['board_owner_email']) {
                return response()->json(['message' => 'Share user does not have permission to update board'], 200);
            }

            $board->update($data);

            return response()->json(['message' => $board['board_api_key']], 200);            
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e], 200);
        }
    }
}