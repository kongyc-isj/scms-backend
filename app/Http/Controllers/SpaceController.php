<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Space;

class SpaceController extends Controller
{
    // Create a new space
    public function store(Request $request)
    {
        try { 
            $request->validate([
                'space_name' => 'required|string',
                'space_description' => 'required|string',
                'space_owner_user.space_owner_user_email' => 'required|email',
            ]);

            $data = $request->all();

            $data['space_shared_user'] = [];
            $data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $data['updated_at'] = null;
            $data['deleted_at'] = null;

            logger()->info($data);

            $space = Space::create($data);

            return response()->json(['space' => $space, 'message' => 'Space created successfully'], 200);
        } 
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }    
    }

    // Show all spaces by owner email
    public function index(Request $request)   //curl http://localhost:8000/spaces/space_owner_user_email?Jeff@example.com
    {
        try { 
            $email = $request->input('email');

            $space = Space::where('space_owner_user.space_owner_user_email', $email)
                ->orWhere('space_shared_user.space_shared_user_email', $email)
                ->get(['_id', 'space_name', 'space_description']);

            return response()->json(['space' => $space, 'message' => 'Space show successfully'], 200);       
        } 
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }  
    }   


    // Show specific spaces by owner email
    public function show(Request  $request, $id)
    {
        try { 
            $email = $request->input('email');

            $space = Space::where('_id', $id)
            ->where('deleted_at', null)
            ->first();  

            if (!$space) {
                return response()->json(['message' => 'Space not found'], 404);
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
                return response()->json(['space' => [], 'message' => 'No match email with space'], 400);
            }
        }
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }  
    }
        
    // Update a space
    public function update(Request $request, $id)
    {
        try { 
            $request->validate([
                'space_name' => 'required|string',
                'space_description' => 'required|string',
                'space_owner_user.space_owner_user_email' => 'required|email',
            ]);

            $data = $request->only(['space_name', 'space_description']);

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$space) {
                logger()->info($space);
                return response()->json(['message' => 'Space not found']);
            }

            // Check if the provided email matches the space_owner_user_email
            if ($request->input('space_owner_user.space_owner_user_email') !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Email does not match space owner email']);
            }

            $space->update($data);

            return response()->json(['message' => 'Space updated successfully'], 200);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }  
    }

    // Delete a space by owner email
    public function destroy(Request $request, $id)
    {
        try { 
            $request->validate([
                'space_owner_user.space_owner_user_email' => 'required|email',
            ]);

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (empty($space)) {
                return response()->json(['message' => 'Space not found']);
            }

            // Check if the provided email matches the space_owner_user_email
            if ($request->input('space_owner_user.space_owner_user_email') !== $space['space_owner_user']['space_owner_user_email']) {
                return response()->json(['message' => 'Email does not match space owner email']);
            }

            $data['deleted_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $space->update($data);

            return response()->json(['message' => 'Space deleted successfully']);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }  
    }

    public function get_share_user($id)
    {
        try { 
            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

            if (!$space) {
                return response()->json(['message' => 'Space not found'], 404);
            }

            $shareUsers = $space['space_shared_user'];

            return response()->json(['space_share_user' => $shareUsers], 200);
        }  
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }  
    }
    
    public function update_share_user(Request $request, $id)
    {
        try{
            $request->validate([
                'space_owner_user.space_owner_user_email' => 'required|email|exists:spaces,space_owner_user.space_owner_user_email',
                'new_space_shared_user_emails.*' => 'required|email',
            ]);

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

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
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }  
    }

    public function delete_share_user(Request $request, $id)
    {
        try{
            $request->validate([
                'space_owner_user.space_owner_user_email' => 'required|email',
                'space_shared_user_email' => 'required|email',
            ]);

            $space = Space::where('_id', $id)
                ->where('deleted_at', null)
                ->first();

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
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }  
    }
}