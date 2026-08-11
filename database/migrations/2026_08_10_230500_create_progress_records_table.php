<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('enrollment_id');
            $table->uuid('program_id');
            $table->uuid('participant_id');
            $table->decimal('percentage', 5, 2)->default(0);
            $table->unsignedInteger('completed_activities')->default(0);
            $table->unsignedInteger('total_activities')->default(0);
            $table->timestamps();

            $table->unique('enrollment_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_records');
    }
};
