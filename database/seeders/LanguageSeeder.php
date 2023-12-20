<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $languages = [
            [
                'language_name' => 'English',
                'language_code' => 'en',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Chinese (Simplified)',
                'language_code' => 'zh-CN',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Chinese (Traditional)',
                'language_code' => 'zh-TW',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Spanish',
                'language_code' => 'es',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'French',
                'language_code' => 'fr',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'German',
                'language_code' => 'de',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Italian',
                'language_code' => 'it',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Japanese',
                'language_code' => 'ja',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Portuguese',
                'language_code' => 'pt',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Russian',
                'language_code' => 'ru',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Arabic',
                'language_code' => 'ar',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Hindi',
                'language_code' => 'hi',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Dutch',
                'language_code' => 'nl',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Korean',
                'language_code' => 'ko',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Turkish',
                'language_code' => 'tr',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Swedish',
                'language_code' => 'sv',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Finnish',
                'language_code' => 'fi',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            [
                'language_name' => 'Norwegian',
                'language_code' => 'no',
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'deleted_at' => null,
            ],
            // Add more languages as needed
        ];

        DB::table('languages')->insert($languages);    
    }
}
