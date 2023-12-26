<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $field_types = [
            // Text
            [
                'field_type_tag' => 'text',
                'field_type_name' => 'short_text',
                'field_type_description' => 'Small or long text like title or description',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'field_type_tag' => 'text',
                'field_type_name' => 'long_text',
                'field_type_description' => 'Long text field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],

            // Rich Text
            [
                'field_type_tag' => 'rich_text',
                'field_type_name' => 'rich_text',
                'field_type_description' => 'Formatted rich text field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],

            // Email
            [
                'field_type_tag' => 'email',
                'field_type_name' => 'email',
                'field_type_description' => 'Email address field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],

            // Number
            [
                'field_type_tag' => 'number',
                'field_type_name' => 'integer',
                'field_type_description' => 'Integer field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'field_type_tag' => 'number',
                'field_type_name' => 'big_integer',
                'field_type_description' => 'Big Integer field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'field_type_tag' => 'number',
                'field_type_name' => 'decimal',
                'field_type_description' => 'Decimal field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'field_type_tag' => 'number',
                'field_type_name' => 'float',
                'field_type_description' => 'Float field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],

            // Date
            [
                'field_type_tag' => 'date',
                'field_type_name' => 'date',
                'field_type_description' => 'Date field (e.g., 01/01/2023)',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'field_type_tag' => 'date',
                'field_type_name' => 'datetime',
                'field_type_description' => 'Datetime field (e.g., 01/01/2023 00:00 AM)',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'field_type_tag' => 'date',
                'field_type_name' => 'time',
                'field_type_description' => 'Time field (e.g., 00:00 AM)',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],

            // Media
            [
                'field_type_tag' => 'media',
                'field_type_name' => 'media',
                'field_type_description' => 'Media file field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
            [
                'field_type_tag' => 'boolean',
                'field_type_name' => 'boolean',
                'field_type_description' => 'Boolean field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],

            // JSON
            [
                'field_type_tag' => 'json',
                'field_type_name' => 'json',
                'field_type_description' => 'JSON field',
                'created_at' => '2023-11-07 04:02:44',
                'updated_at' => '2023-11-07 04:02:44',
                'deleted_at' => null,
            ],
        ];

        // Insert data into the field type table
        DB::table('field_type')->insert($field_types);    
    }
}
