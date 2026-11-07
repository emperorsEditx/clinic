<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_code', 40)->unique();
            $table->string('full_name');
            $table->unsignedSmallInteger('age')->nullable();
            $table->date('age_recorded_on')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20);
            $table->string('phone', 30)->nullable();
            $table->string('phone_normalized', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address', 500)->nullable();
            $table->date('registration_date');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_relation', 60)->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('full_name');
            $table->index('phone_normalized');
            $table->index('registration_date');
            $table->index('gender');
        });

        // Free-text history entries (append-only general notes).
        Schema::create('patient_medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('entry_type', 40)->default('note');
            $table->string('title')->nullable();
            $table->text('notes');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Conditions: chronic/current conditions, previous diseases, surgeries, family history.
        Schema::create('patient_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('category', 40);
            $table->string('name');
            $table->string('status', 20)->default('current');
            $table->date('onset_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->string('relation', 60)->nullable(); // family history: relation
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'category', 'status']);
        });

        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('allergen');
            $table->string('allergy_type', 20)->default('drug');
            $table->string('severity', 20)->default('unknown');
            $table->string('reaction')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'status']);
        });

        // Medicines taken from other sources / intercurrent medication (NOT clinic prescribed).
        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->string('medicine_name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('prescribing_source')->nullable();
            $table->string('status', 20)->default('current');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medications');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_conditions');
        Schema::dropIfExists('patient_medical_histories');
        Schema::dropIfExists('patients');
    }
};
