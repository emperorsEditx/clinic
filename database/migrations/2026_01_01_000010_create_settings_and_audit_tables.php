<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // Concurrency-safe counters for human-facing document numbers
        // (patient IDs, prescription numbers, ...). Rows are locked with
        // SELECT ... FOR UPDATE inside a transaction when incremented.
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('period', 10)->default('all');
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();
            $table->unique(['name', 'period']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action', 60);
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('clinic_settings');
    }
};
