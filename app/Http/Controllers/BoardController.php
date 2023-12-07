<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Board;
use Ramsey\Uuid\Uuid;

class BoardController extends Controller
{
    public function index(Request $request)
    {
        $email = $request->input('email');

        $board = Board::where('board_owner_user.board_owner_email', $email)
            ->orWhere('board_shared_user.board_shared_user_email', $email)
            ->get(['_id', 'board_name', 'board_description', 'board_api_key']);

        if (!$board) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['board' => $board], 200);
    }

    // Show specific spaces by owner email
    public function show(Request  $request, $id)
    {
        $space = Board::find($id)
        ->get(['_id', 'space_name', 'space_description', 'board_api_key']);

        return response()->json($space);
    }

    public function store(Request $request)
    {
        $request->validate([
            'space_id' => 'required|string',
            'board_name' => 'required|string',
            'board_description' => 'required|string',
            'board_owner_user.board_owner_email' => 'required|email'
        ]);

        $currentDateTime = Carbon::now()->format('Y-m-d H:i:s');

        $data = $request->all();

        $data['board_api_key'] =Uuid::uuid4()->toString();
        $data['board_owner_user']['board_owner_api_key'] =Uuid::uuid4()->toString();
        $data['board_shared_user'] = [];
        $data['created_at'] = $currentDateTime;
        $data['updated_at'] = null;
        $data['deleted_at'] = null;
        
        logger()->info($data);

        $board = Board::create($data);
        logger()->info($board);

        return response()->json($board, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'board_name' => 'required|string',
            'board_description' => 'required|string',
            'board_owner_user.board_owner_email' => 'required|email',
        ]);

        $data = $request->only(['board_name', 'board_description']);
        $board = Board::find($id);

        if (!$board) {
            logger()->info($board);
            return response()->json(['message' => 'Board not found']);
        }

        // Check if the provided email matches the space_owner_user_email
        if ($request->input('board_owner_user.board_owner_email') !== $board['board_owner_user']['board_owner_email']) {
            return response()->json(['message' => 'Email does not match space owner email']);
        }

        $board->update($data);

        return response()->json(['message' => 'Board updated successfully'], 200);
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'board_owner_user.board_owner_email' => 'required|email',
        ]);

        $board = Board::find($id);

        if (!$board) {
            return response()->json(['message' => 'Board not found']);
        }

        // Check if the provided email matches the space_owner_user_email
        if ($request->input('board_owner_user.board_owner_email') !== $board['board_owner_user']['board_owner_email']) {
            return response()->json(['message' => 'Email does not match space owner email']);
        }

        $board->delete();

        return response()->json(['message' => 'Board deleted successfully']);
    }

    public function get_share_user($id)
    {
        $space = Space::find($id);

        if (!$space) {
            return response()->json(['message' => 'Space not found'], 404);
        }

        $shareUsers = $space['space_shared_user'];

        return response()->json(['share_users' => $shareUsers], 200);
    }
    
    public function update_share_user(Request $request, $id)
    {
        $request->validate([
            'board_owner_user.board_owner_email' => 'required|email',
            'new_space_shared_user_emails.*' => 'required|email',
        ]);

        $space = Space::find($id);

        if (!$space) {
            return response()->json(['message' => 'Space not found'], 404);
        }

        // Check if the provided email matches the space_owner_user_email
        if ($request->input('space_owner_user.space_owner_user_email') !== $space['space_owner_user']['space_owner_user_email']) {
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

        return response()->json(['message' => 'Space share user insert successfully']);
    }

    public function delete_share_user(Request $request, $id)
    {
        $request->validate([
            'space_owner_user.space_owner_user_email' => 'required|email',
            'space_shared_user_email' => 'required|email',
        ]);

        $space = Space::find($id);

        if (!$space) {
            return response()->json(['message' => 'Space not found'], 404);
        }

        // Check if the provided email matches the space_owner_user_email
        if ($request->input('space_owner_user.space_owner_user_email') !== $space['space_owner_user']['space_owner_user_email']) {
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

        return response()->json(['message' => 'Space shared user deleted successfully']);
    }
}