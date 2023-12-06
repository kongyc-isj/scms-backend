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
}