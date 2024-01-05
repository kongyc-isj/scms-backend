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
                    '6593c0fcf2d5d4a05202f1b3' => 'Username',
                    '6593c13bf2d5d4a05202f1b4' => 'Password',
                    '6593c1cff2d5d4a05202f1b5' => 'Login',
                    '6593e698f2d5d4a05202f1b6' => 'Try For Free',
                    '659660cbf2d5d4a05202f1d9' => 'Invalid username or password',
                ],
                'zh-TW' => [
                    '6593c0fcf2d5d4a05202f1b3' => '用戶名稱',
                    '6593c13bf2d5d4a05202f1b4' => '賬密',
                    '6593c1cff2d5d4a05202f1b5' => '登錄',
                    '6593e698f2d5d4a05202f1b6' => '試玩',
                    '659660cbf2d5d4a05202f1d9' => '用戶名稱與賬密不匹配',
                ]
            ],
            'created_at' => '2023-11-07 04:02:44',
            'updated_at' => '2023-11-07 04:02:44',
            'deleted_at' => null
        ];

        // Insert data into the board table
        DB::table('field_data')->insert($field_data);    
    }
}
