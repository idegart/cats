<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cats', function (Blueprint $table) {
            $table->id();
            $table->string('basename')->unique();
            $table->string('file');
            $table->string('tg_file_id')->nullable();
            $table->string('tg_file_unique_id')->nullable();
            $table->integer('likes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cats');
    }
}
