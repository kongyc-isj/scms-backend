<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jenssegers\Mongodb\Eloquent\Model;
use Carbon\Carbon;
use App\Models\FieldData;
use App\Models\FieldKey;
use App\Models\Board;
use App\Models\Component;
use App\Models\Language;
use App\Models\MediaGallery;
use App\Models\AuditLog;
use DateTime;
use Stichoza\GoogleTranslate\GoogleTranslate;

class FieldDataController extends Controller
{
    public function field_data_permission($component_id, $email, $request, $method)
    {
        $component = Component::where('_id', $component_id)
            ->where('deleted_at', null)
            ->first();   

        if (!$component) {
            return response()->json(['message' => 'Component not found'], 422);
        }

        $board = Board::where('_id', $component->board_id)
            ->where('deleted_at', null)
            ->first();  

        if (!$board) {
            return response()->json(['message' => 'Board not found'], 422);
        }

        $field_data = FieldData::where('component_id', $component->id)
            ->where('deleted_at', null)
            ->first(); 

        if (!$field_data) {
            return response()->json(['field_data' => [], 'message' => 'Field Data not found'], 200);
        }

        $field_key = FieldKey::where('component_id', $component->_id)
        ->where('deleted_at', null)
        ->get();

        if (!$field_key) {
            return response()->json(['message' => 'Field Key not found'], 422);
        }

        $owner_board  = Board::where('board_owner_user.board_owner_email', $email)
                        ->where('_id', $board->_id)    
                        ->where('deleted_at', null)
                        ->first();


        $shared_board = Board::where('board_shared_user', 'elemMatch', ['board_shared_user_email' => $email])
                        ->where('_id', $board->_id)   
                        ->where('deleted_at', null) 
                        ->first();
                        
        $field_data_list       = $field_data->toArray();
        $field_key_value_list  = $field_data_list['field_key_value'];
        $language_code         = empty($request['language_code']) ? $board->board_default_language_code : $request['language_code'];
        
        if ($owner_board) 
        {
            if($method == "show")
            {
                //if valid language given but it is no recorded then return error

                //language no exist and need to auto translate
                if (!array_key_exists($language_code, $field_key_value_list)) {
                    $data = $field_key_value_list[$owner_board->board_default_language_code];
                }
                //language existed but need to translate
                elseif (array_key_exists($language_code, $field_key_value_list) && $request['translate_existed_data'] == 1){
                    $data = $field_key_value_list[$language_code];
                }
                //language existed and no need to translate
                else{
                    $data = $field_key_value_list[$language_code];
                }

                //prepare api return format
                $mapped_data = [];
                foreach ($data as $field_key_name => $field_value) {
                    foreach ($field_key as $each_field_key) {
                        if ($each_field_key['field_key_name'] === $field_key_name) {

                            if($each_field_key['field_type_name'] == 'media' && !empty($field_value)){

                                $media_list = [];

                                foreach ($field_value as $each_field_value){

                                    $media = MediaGallery::where('_id', $each_field_value)
                                    ->where('deleted_at', null)
                                    ->first(['media_name', 'media_url']);

                                    if (!$media)
                                        continue;
                                     
                                    $media_list[] = $media;
                                } 
                                $field_value = $media_list;
                            }

                            if($each_field_key['field_type_name'] == 'long_text' || $each_field_key['field_type_name'] == 'short_text' && $owner_board->board_default_language_code != $language_code){
                            
                                //do for auto translate for not existed language
                                if(!array_key_exists($language_code, $field_key_value_list) && $request['translate_existed_data'] == 0)
                                {
                                    $tr = new GoogleTranslate(); 
                                    $tr->setSource($owner_board->board_default_language_code); 
                                    $tr->setTarget($language_code); 

                                    if (!empty($field_value))
                                    {
                                        $field_value = $tr->translate($field_value);  
                                    }
                                    else
                                    {
                                        $field_value = "";
                                    }
                                }

                                //do for manual translate button for existed language
                                if(array_key_exists($language_code, $field_key_value_list) && $request['translate_existed_data'] == 1)
                                {
                                    $default_field_data = $field_key_value_list[$owner_board->board_default_language_code];

                                    $tr = new GoogleTranslate(); 
                                    $tr->setSource($owner_board->board_default_language_code); 
                                    $tr->setTarget($language_code); 

                                    if (!empty($default_field_data[$field_key_name]))
                                    {
                                        $field_value = $tr->translate($default_field_data[$field_key_name]);  
                                    }
                                    else
                                    {
                                        $field_value = "";
                                    }
                                }
                            }
                            if($each_field_key['field_type_name'] == 'json') {               
                                $field_value = json_encode($field_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);  
                            }

                            $each_map_data = [
                                "field_key_id" => $each_field_key['_id'],
                                "value" => $field_value,
                                "field_key_name" => $each_field_key['field_key_name'],
                                "field_type_name" => $each_field_key['field_type_name'],
                            ];
                            $mapped_data[] = $each_map_data;
                            break;
                        }
                    }
                }
                return response()->json(['board_default_language'=>$owner_board->board_default_language_code, 'field_data' => $mapped_data, 'message' => 'Field data show successfully'], 200);
            }
            elseif($method == "update")
            {
                $validate_field_key = $this->validate_field_key_id($field_key->toArray(), $request['field_key_value']);
                if(!empty($validate_field_key))
                    return $validate_field_key;
    
                //validate field type
                $validate_field_type = $this->validate_field_type($field_key->toArray(), $request['field_key_value'], $owner_board->_id);
    
                if(!empty($validate_field_type))
                    return $validate_field_type;
                
                $language_list = Language::where('deleted_at', null)
                ->get(['language_code']);  

                $language_list = $language_list->toArray();            
                $language_exist = in_array($language_code, array_column($language_list, 'language_code'));

                if (!$language_exist) {
                    return response()->json(['message' => "Language code '$language_code' does not exist in the language data."], 422);
                } 

                $field_key_value_format = [
                    $language_code => $request['field_key_value']
                ];
    
                $data["field_key_value"]  = [];
                $field_key_value_list = $field_data->field_key_value;
    
                // Check if the request language field data stored. If havent store then store it, else update it
                if (array_key_exists($language_code, $field_key_value_list)) {
    
                    $field_key_value_list[$language_code] = array_merge(
                        $field_key_value_list[$language_code],
                        $request['field_key_value']
                    );
                    $data['field_key_value'] = $field_key_value_list;
    
                } else {
                    // Insert the whole object with the new language code
                    $merge = array_merge_recursive($field_key_value_list, $field_key_value_format);
                    $data['field_key_value'] = $merge;    
                }
    
                // Update the data
                $field_data->update($data);

                AuditLog::logAction($owner_board->id, $email, 'update_field_data', 'Field data updated with component name: ' . $component->component_name);
    
                return response()->json(['message' => 'Field data updated successfully'], 200);
            }     
            elseif($method == "field_data_language")
            {
                $component = Component::where('_id', $request['component_id'])
                ->where('deleted_at', null)
                ->first();   
    
                if (!$component) {
                    return response()->json(['message' => 'Component not found'], 422);
                }
    
                $field_data = FieldData::where('component_id', $component->id)
                ->where('deleted_at', null)
                ->first(); 
    
                if (!$field_data) {
                    return response()->json(['message' => 'Component not found'], 422);
                }
    
                $language_list  = $field_data['field_key_value']; //->toArray();         
                $language_array = (array_keys($language_list));   
    
                $check_in_array = in_array($request['language_code'], $language_array);
    
                return response()->json(['language_code_exist' => $check_in_array, 'message' => 'Field data language checked successfully'], 200);
                    
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

            if($method == "show")
            {
                if ($sharedUser['board_shared_user_read_access'] == 1) {
                    //if valid language given but it is no recorded then return error

                    //language no exist and need to auto translate
                    if (!array_key_exists($language_code, $field_key_value_list)) {
                        $data = $field_key_value_list[$shared_board->board_default_language_code];
                    }
                    //language existed but need to translate
                    elseif (array_key_exists($language_code, $field_key_value_list) && $request['translate_existed_data'] == 1){
                        $data = $field_key_value_list[$language_code];
                    }
                    //language existed and no need to translate
                    else{
                        $data = $field_key_value_list[$language_code];
                    }
                                
                    //prepare api return format
                    $mapped_data = [];
                    foreach ($data as $field_key_name => $field_value) {
                        foreach ($field_key as $each_field_key) {
                            if ($each_field_key['field_key_name'] === $field_key_name) {

                                if($each_field_key['field_type_name'] == 'media' && !empty($field_value)){

                                    $media_list = [];
    
                                    foreach ($field_value as $each_field_value){
    
                                        $media = MediaGallery::where('_id', $each_field_value)
                                        ->where('deleted_at', null)
                                        ->first(['media_name', 'media_url']);
    
                                        if (!$media){
                                            continue;
                                        }
                                        
                                        $media_list[] = $media;
                                    } 
                                    $field_value = $media_list;    
                                }

                                if($each_field_key['field_type_name'] == 'long_text' || $each_field_key['field_type_name'] == 'short_text'  && $shared_board->board_default_language_code != $language_code){
                            
                                    //do for auto translate for not existed language
                                    if(!array_key_exists($language_code, $field_key_value_list) && $request['translate_existed_data'] == 0)
                                    {
                                        $tr = new GoogleTranslate(); 
                                        $tr->setSource($shared_board->board_default_language_code); 
                                        $tr->setTarget($language_code); 

                                        if (!empty($field_value))
                                        {
                                            $field_value = $tr->translate($field_value);  
                                        }
                                        else
                                        {
                                            $field_value = "";
                                        }
                                    }
    
                                    //do for manual translate button for existed language
                                    if(array_key_exists($language_code, $field_key_value_list) && $request['translate_existed_data'] == 1)
                                    {
                                        $default_field_data = $field_key_value_list[$shared_board->board_default_language_code];
                                        $tr = new GoogleTranslate(); 
                                        $tr->setSource($shared_board->board_default_language_code); 
                                        $tr->setTarget($language_code); 

                                        if (!empty($default_field_data[$field_key_name]))
                                        {
                                            $field_value = $tr->translate($default_field_data[$field_key_name]);  
                                        }
                                        else
                                        {
                                            $field_value = "";
                                        }                                    
                                    }
                                }
                                if($each_field_key['field_type_name'] == 'json') {               
                                    $field_value = json_encode($field_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);  
                                }

                                $each_map_data = [
                                    "field_key_id" => $each_field_key['_id'],
                                    "value" => $field_value,
                                    "field_key_name" => $each_field_key['field_key_name'],
                                    "field_type_name" => $each_field_key['field_type_name'],
                                ];
                                $mapped_data[] = $each_map_data;
                                break;
                            }
                        }
                    }
                    return response()->json(['board_default_language'=>$shared_board->board_default_language_code, 'field_data' => $mapped_data, 'message' => 'Field data show successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "update")
            {
                if ($sharedUser['board_shared_user_update_access'] == 1) {

                    $validate_field_key = $this->validate_field_key_id($field_key->toArray(), $request['field_key_value']);

                    if(!empty($validate_field_key))
                        return $validate_field_key;
        
                    //validate field type
                    $validate_field_type = $this->validate_field_type($field_key->toArray(), $request['field_key_value'], $shared_board->_id);
        
                    if(!empty($validate_field_type))
                        return $validate_field_type;
                    
                    $language_list = Language::where('deleted_at', null)
                    ->get(['language_code']);  
    
                    $language_list = $language_list->toArray();            
                    $language_exist = in_array($language_code, array_column($language_list, 'language_code'));
    
                    if (!$language_exist) {
                        return response()->json(['message' => "Language code '$language_code' does not exist in the language data."], 422);
                    } 
    
                    $field_key_value_format = [
                        $language_code => $request['field_key_value']
                    ];
        
                    $data["field_key_value"]  = [];
                    $field_key_value_list = $field_data->field_key_value;
        
                    // Check if the request language field data stored. If havent store then store it, else update it
                    if (array_key_exists($language_code, $field_key_value_list)) {
        
                        $field_key_value_list[$language_code] = array_merge(
                            $field_key_value_list[$language_code],
                            $request['field_key_value']
                        );
                        $data['field_key_value'] = $field_key_value_list;
        
                    } else {
                        // Insert the whole object with the new language code
                        $merge = array_merge_recursive($field_key_value_list, $field_key_value_format);
                        $data['field_key_value'] = $merge;    
                    }
                    // Update the data
                    $field_data->update($data);

                    AuditLog::logAction($shared_board->id, $email, 'update_field_data', 'Field data updated with component name: ' . $component->component_name);
        
                    return response()->json(['message' => 'Field data updated successfully'], 200);
                } 
                else 
                {
                    return response()->json(['message' => 'Permission denied'], 422);
                }
            }
            elseif($method == "field_data_language")
            {
                $component = Component::where('_id', $request['component_id'])
                ->where('deleted_at', null)
                ->first();   
    
                if (!$component) {
                    return response()->json(['message' => 'Component not found'], 422);
                }
    
                $field_data = FieldData::where('component_id', $component->id)
                ->where('deleted_at', null)
                ->first(); 
    
                if (!$field_data) {
                    return response()->json(['message' => 'Component not found'], 422);
                }
    
                $language_list  = $field_data['field_key_value']; //->toArray();         
                $language_array = (array_keys($language_list));   
    
                $check_in_array = in_array($request['language_code'], $language_array);
    
                return response()->json(['language_code_exist' => $check_in_array, 'message' => 'Field data language checked successfully'], 200);
                    
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

    public function show(Request $request)
    {
        $request->validate([
            'component_id'           => 'required|string',
            'language_code'          => 'nullable|string',
            'translate_existed_data' => 'required|string'
        ]);

        return $this->field_data_permission($request['component_id'], $request['email'], $request, 'show');
    }

    public function update(Request $request)
    {
        try{
            // Validate the request data
            $request->validate([
                'component_id'    => 'required|string',
                'language_code'   => 'nullable|string',
                'field_key_value' => 'required|array'
            ]);

            return $this->field_data_permission($request['component_id'], $request['email'], $request, 'update');

        }
        catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try{
            $request->validate([
                'component_id'  => 'required|string'
            ]);

            $field_data = FieldData::where('component_id', $request['component_id'])
            ->where('deleted_at', null)
            ->first();   

            if (empty($field_data)) {
                return response()->json(['message' => 'Field data no exist'], 422);
            }

            $field_data->delete();

            return response()->json(['message' => 'Field data deleted successfully']);
        }
        catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }    

    public function field_data_language(Request $request)
    {
        try{
            // Validate the request data
            $request->validate([
                'component_id'    => 'required|string',
                'language_code'   => 'required|string'
            ]);

            return $this->field_data_permission($request['component_id'], $request['email'], $request, 'field_data_language');

        }
        catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function validate_field_key_id ($field_key_array, $field_key_value)
    {
        try{
            $expected_field_key_names = array_column($field_key_array, 'field_key_name');

            // Check if all expected '_id' values are present in $field_key
            $missing_field_key_names = array_diff($expected_field_key_names, array_keys($field_key_value));
    
            if (!empty($missing_field_key_names)) {
                // Handle the case where some expected '_id' values are missing
                return response()->json(['message' => 'Missing Field Key: ' . implode(', ', $missing_field_key_names)], 422);
            }

            // Check if all '_id' values in $field_data_values exist in $field_key
            $not_found_field_key_names = array_diff(array_keys($field_key_value), array_column($field_key_array, 'field_key_name'));

            if (!empty($not_found_field_key_names)) {
    
                // Handle the case where some '_id' values in $field_data_values are not found in $field_key
                return response()->json(['message' => 'Field Key not found: ' . implode(', ', $not_found_field_key_names)], 422);
            }
        }
        catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    public function validate_field_type ($field_key_array, $field_key_value, $board_id)
    {
        try{
            foreach ($field_key_value as $field_key_name => $field_value) {
                // Find the corresponding field key in the collection
                $field_key_collection = collect($field_key_array)->firstWhere('field_key_name', $field_key_name);
            
                if (!$field_key_collection) {
                    return response()->json(['message' => "Field Key with _id: $field_key_name not found"], 422);
                }
            
                // Validate the value based on field_type_name
                $field_type_name = $field_key_collection['field_type_name'];

                if($field_value == null)
                {
                    continue;
                }

                switch ($field_type_name) {

                    case 'short_text':
                        $max_length = 255; 
                        if (!is_string($field_value) || strlen($field_value) > $max_length) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Maximum length $max_length exceeded."], 422);
                        }
                        break;      

                    case 'long_text':
                        $max_length = 65536;  
                        if (!is_string($field_value) || strlen($field_value) > $max_length) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Maximum length $max_length exceeded."], 422);
                        }
                        break;

                    case 'rich_text':
                        if (!is_string($field_value) || empty(trim($field_value))) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Rich text content cannot be empty."], 422);
                        }
                        
                        // Example: Check if the content contains any HTML tags (may vary based on your requirements)
                        if (strip_tags($field_value) === $field_value) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Rich text content must include HTML formatting."], 422);
                        }
                        break;   

                    case 'email':
                        if (!filter_var($field_value, FILTER_VALIDATE_EMAIL)) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid email address."], 422);
                        }
                        break;

                    case 'integer':
                        $min_value = -2147483647;
                        $max_value = 2147483647;
                    
                        if (!is_numeric($field_value) || $field_value < $min_value || $field_value > $max_value || !filter_var($field_value, FILTER_VALIDATE_INT)) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid integer within the range of -2,147,483,647 to 2,147,483,647."], 422);
                        }
                        break;

                    case 'decimal':
                        $min_value = -9007199254740.99;
                        $max_value = 9007199254740.99;
                        $pattern = '/^-?\d+(\.\d{1,2})?$/';

                        if (!is_numeric($field_value) || $field_value < $min_value || $field_value > $max_value || !preg_match($pattern, $field_value)) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid decimal within the range of $min_value to $max_value."], 422);
                        }
                        break;

                    case 'big_integer':
                        $max_value =  9007199254740991;
                        $min_value = -9007199254740991;

                        if (!is_numeric($field_value) || $field_value < $min_value || $field_value > $max_value) {// || !filter_var($field_value, FILTER_VALIDATE_INT)) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid big integer within the range of $min_value to $max_value."], 422);
                        }
                        break;

                    case 'float':
                        $min_value = -90071992547.9999;
                        $max_value = 90071992547.9999;
                    
                        if (!is_numeric($field_value) || $field_value < $min_value || $field_value > $max_value) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid float within the range of $min_value to $max_value."], 422);
                        }
                        break;

                    case 'datetime':
                        $format = 'Y-m-d H:i:s';
                    
                        $datetime = DateTime::createFromFormat($format, $field_value);
                    
                        if (!$datetime || $datetime->format($format) !== $field_value) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid datetime in the format 'Y-m-d H:i:s'."], 422);
                        }
                        break;                

                    case 'date':
                        $format = 'Y-m-d';                     

                        $date = DateTime::createFromFormat($format, $field_value);                     

                        if (!$date || $date->format($format) !== $field_value) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid date in the format 'YYYY-MM-DD'."], 422);
                        }
                        break;     

                    case 'time':
                        $format = 'h:i:s A';
                    
                        $time = DateTime::createFromFormat($format, $field_value);
                    
                        if (!$time || $time->format($format) !== $field_value) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid time in the format 'HH:MM:SS XM'."], 422);
                        }
                        break;

                    case 'media':

                        foreach ($field_value as $each_field_value){
                            $media = MediaGallery::where('_id', $each_field_value)
                            ->where('deleted_at', null)
                            ->first();
                    
                            if (!$media) {
                                return response()->json(['message' => 'Media not found'], 422);
                            }
    
                            if ($media->board_id != $board_id) {
                                return response()->json(['message' => 'Media is not available in this board'], 422);
                            }
                        }

                        break;

                    case 'boolean':
                        if (!is_bool($field_value)) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name"], 422);
                        }
                        break;

                    case 'json':
                        // Assuming $field_value is the JSON value
                     
                        // If $field_value is an array, encode it to JSON
                        if (is_array($field_value)) {
                            $field_value  = json_encode($field_value);
                            $decoded_json = json_decode($field_value);
                        }
                        else {
                           $decoded_json  = json_decode($field_value);
                        }
                     
                        if ($decoded_json === null && json_last_error() !== JSON_ERROR_NONE) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid JSON string or array."], 422);
                        }
                        $check_is_encoded = preg_match('/[^\x20-\x7E]/', $field_value);

                        if ($check_is_encoded == 1 || is_numeric($decoded_json)) {
                            return response()->json(['message' => "Invalid value for $field_type_name field type for field key name: $field_key_name. Please provide a valid JSON string or array."], 422);
                        }
                        break;

                    default:
                        return response()->json(['message' => "Unsupported field_type_name: $field_type_name for field key name: $field_key_name"], 422);
                }
            }
        }
        catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }
}
