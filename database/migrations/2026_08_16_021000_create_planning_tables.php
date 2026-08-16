<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_boards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('planning_columns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('board_id');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('board_id');
            $table->foreign('board_id')->references('id')->on('planning_boards')->cascadeOnDelete();
        });

        Schema::create('planning_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('column_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('column_id');
            $table->foreign('column_id')->references('id')->on('planning_columns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_tasks');
        Schema::dropIfExists('planning_columns');
        Schema::dropIfExists('planning_boards');
    }
};
