<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;

class ValidateAccessToken
{
    public function handle(Request $request, Closure $next)
    {
        try{
            // Check if the access token is present in the request headers
            $access_token = $request->bearerToken();

            // Call the validate_token API on the OAuth server
            $validation_response = $this->callValidateTokenApi($access_token);

            if ($validation_response->failed()) {
                return response()->json(['error' => 'Invalid access token'], 401);
            }

            $validation_response = json_decode($validation_response, true);

            $request = $this->customMerge($request, $validation_response);

            return $next($request);
        } 
        catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['error' => $e], 500);
        }    

    }

    private function customMerge($array1, $array2) {
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($array1[$key]) && is_array($array1[$key])) {
                $array1[$key] = customMerge($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
    
        return $array1;
    }


    private function callValidateTokenApi($access_token)
    {
        // Call the validate_token API on the OAuth server
        //$domain = 'https://scms-stg-oauth.ippcoin.com';  
        $domain = 'http://localhost:8001';  

        $url = '/api/users/token_validation';
        $endpoint = $domain . $url;
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
        ])->post($endpoint);
    
        // Get the response body as an array
        $data = $response->json();
                
        return $response;
    }
}