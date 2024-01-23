<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Language;

class LanguageController extends Controller
{
    public function index()
    {
        try {
            $languages = Language::where('deleted_at', null)->get(['language_name', 'language_code']);

            $languages_without_id = $languages->map(function ($language) {
                unset($language['_id']);
                return $language;
            });

            return response()->json(['languages' => $languages_without_id], 200);

        } catch (\Exception $e) {
            logger()->error($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
