<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('author_user_id');
            $table->uuid('participant_id')->nullable();
            $table->date('entry_date');
            $table->text('content');
            $table->string('visibility')->default('private');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('entry_date');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('author_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('participant_id')->references('id')->on('participants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
