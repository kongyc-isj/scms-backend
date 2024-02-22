<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\Board;
use DateTime;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    public function show(Request $request, $id)
    {
        try
        {
            $request->validate([
                'filter_email' => 'nullable|string',
                'start'        => 'nullable|date',  
                'end'          => 'nullable|date'
            ]);

            $email        = $request->input('email');
            $filter_email = $request->input('filter_email');
            $start        = $request->input('start');
            $end          = $request->input('end');

            if(!empty($start) && !empty($end))
            {
                $start = Carbon::parse($start);
                $end   = Carbon::parse($end);
            }

            $board = Board::where('_id', $id)
                ->first();  

            if (!$board) {
                return response()->json(['message' => 'Board not found'], 422);
            }      

            //logger($board);
            $owner_board  = Board::where('board_owner_user.board_owner_email', $email)
                ->where('_id',$id)
                ->first();

            if (!empty($owner_board))
            {

                $audit_log = AuditLog::where('board_id', $id)
                    ->when($filter_email !== null, function ($query) use ($filter_email) {
                        $query->where('email', $filter_email);
                    })
                    ->when($start !== null && $end !== null, function ($query) use ($start, $end) {
                        $query->whereBetween('created_at', array($start, $end));
                    })
                    ->where('deleted_at', null)
                    ->get(['board_id', 'email', 'action', 'description', 'created_at']);


                $audit_log_array = json_decode($audit_log, true);

                // Loop through each entry in the array
                foreach ($audit_log_array as &$entry) {
                    // Extract the ISO 8601 timestamp
                    $iso_timestamp = $entry['created_at'];
                
                    // Create a Carbon instance from the ISO 8601 string
                    $carbon = Carbon::parse($iso_timestamp);
                
                    // Format the Carbon instance to the desired format
                    $formatted_timestamp = $carbon->format('Y-m-d H:i:s');
                
                    // Update the 'created_at' field in the entry with the formatted timestamp
                    $entry['created_at'] = $formatted_timestamp;
                }
            
                return response()->json(['audit_log' => $audit_log_array, 'message' => 'Audit log show successfully'], 200);
            }

            else
            {
                return response()->json(['audit_log' => [], 'message' => 'Your email does not owned the board'], 422);
            }
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }  
    }
}
