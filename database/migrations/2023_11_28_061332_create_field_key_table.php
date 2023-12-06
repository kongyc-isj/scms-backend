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
        Schema::create('field_key', function (Blueprint $table) {
            $table->id();
            $table->string('component_id');
            $table->string('field_type_id');
            $table->string('field_key_name');
            $table->string('field_key_description');
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
        Schema::dropIfExists('field_key');
    }
};
