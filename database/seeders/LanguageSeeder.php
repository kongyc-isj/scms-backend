<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null
            ],
            [
                'language_name' => 'Chinese (Simplified)',
                'language_code' => 'zh-CN',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null
            ],
        ];

        DB::table('languages')->insert($languages);    
    }
}
