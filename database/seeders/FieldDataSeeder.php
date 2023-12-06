<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $field_data = [
            'component_id' => '6557029be0d2937b08fb7d83',
            'language_code' => ['en', 'zh-TW'],
            'field_key_value' => [
                'en' => [
                    'landing_index_username' => 'Username',
                    'landing_index_password' => 'Password',
                    'landing_index_login' => 'Login',
                    'landing_index_demo' => 'Try For Free',
                    'landing_index_error_msg' => 'Invalid username or password',
                ],
                'zh-TW' => [
                    'landing_index_username' => '用戶名稱',
                    'landing_index_password' => '賬密',
                    'landing_index_login' => '登錄',
                    'landing_index_demo' => '試玩',
                    'landing_index_error_msg' => '用戶名稱與賬密不匹配',
                ],
            ],
            'created_at' => '2023-11-07 04:02:44',
            'updated_at' => '2023-11-07 04:02:44',
            'deleted_at' => null
        ];

        // Insert data into the board table
        DB::table('field_data')->insert($field_data);    
    }
}
