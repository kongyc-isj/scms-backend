<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Board;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $api_key = $request->header('scms-api-key'); 

        if (!$api_key) {
            return response()->json(['message' => 'Secret key not provided'], 401);
        }

        // Check if the provided secret key exists in the boards table
        $board = Board::where('board_api_key', $api_key)->first();

        if (!$board) {
            return response()->json(['message' => 'Invalid secret key'], 401);
        }

        // Pass the board to the request for use in the controller
        $request->merge(['board' => $board]);

        return $next($request);
    }
}