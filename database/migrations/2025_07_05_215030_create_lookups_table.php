<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->json('title')->nullable(); // For storing translations as JSON
            $table->timestamps();

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('lookups')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lookups');
    }
};