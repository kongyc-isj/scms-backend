<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MediaGallerySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $media_gallery = [
            [
                'board_id' => '65570aaae0d2937b08fb7d84',
                'media_name' => 'aslot-001_ch',
                'media_description' => 'fortune dragon deluxe ch image',
                'media_url' => 'https://aslot-001_ch.png'
            ],
            [
                'board_id' => '65570aaae0d2937b08fb7d84',
                'media_name' => 'aslot-001_en',
                'media_description' => 'fortune dragon deluxe en image',
                'media_url' => 'https://aslot-001_en.png'
            ]
        ];

        // Insert data into the board table
        DB::table('media_gallery')->insert($media_gallery);  
    }
}
