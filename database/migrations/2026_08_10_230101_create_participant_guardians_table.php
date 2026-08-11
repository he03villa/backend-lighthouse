<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_guardians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('participant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('relationship')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->jsonb('permissions')->nullable();
            $table->timestamps();

            $table->unique(['participant_id', 'user_id']);
            $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_guardians');
    }
};
