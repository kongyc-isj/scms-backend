<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_gallery', function (Blueprint $table) {
            $table->id();
            $table->string('board_id');
            $table->string('media_name');
            $table->string('media_description');
            $table->string('media_url');
            $table->timestamp('created_at')->default(now());
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_gallery');
    }
};
