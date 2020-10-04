<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('user_cats', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('cat_id');
            $table->boolean('is_liked')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'cat_id']);

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('cat_id')->references('id')->on('cats');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('user_cats');
    }
}
