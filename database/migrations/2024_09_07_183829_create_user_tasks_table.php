<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->bigIncrements('task_id');
            $table->string('title');
            $table->text('description');
            $table->integer('priority');
            $table->unsignedBigInteger('assign_to')->nullable();
            $table->foreign('assign_to')->references('user_id')->on('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'in-progress', 'done'])->default('pending');
            $table->string('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};
