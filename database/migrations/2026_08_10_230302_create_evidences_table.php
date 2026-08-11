<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('activity_submission_id')->nullable();
            $table->uuid('participant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type')->default('text');
            $table->text('content');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('participant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('activity_submission_id')->references('id')->on('activity_submissions')->cascadeOnDelete();
            $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidences');
    }
};
