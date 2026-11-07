<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 40)->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('appointment_type', 40);
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->text('notes')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['appointment_date', 'status']);
            $table->index(['doctor_id', 'appointment_date']);
            $table->index(['patient_id', 'appointment_date']);
        });

        // A visit is the physical encounter (arrival / check-in). Clinical content lives in consultations.
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number', 40)->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->dateTime('visit_at');
            $table->string('visit_type', 40)->default('walk_in');
            $table->string('chief_complaint')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['visit_at', 'status']);
            $table->index(['patient_id', 'visit_at']);
            $table->index(['doctor_id', 'visit_at']);
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('consultation_number', 40)->unique();
            $table->foreignId('visit_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->text('symptoms')->nullable();
            $table->text('clinical_observations')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('amendment_count')->default(0);
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
            $table->index(['doctor_id', 'status']);
            $table->index('completed_at');
        });

        Schema::create('consultation_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('systolic_bp')->nullable();
            $table->unsignedSmallInteger('diastolic_bp')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('pulse_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 1)->nullable();
            $table->unsignedTinyInteger('oxygen_saturation')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // Tracked corrections to completed consultations. Each row keeps the before/after values.
        Schema::create('consultation_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->restrictOnDelete();
            $table->string('reason');
            $table->json('changes');
            $table->foreignId('amended_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->date('follow_up_date');
            $table->text('instructions')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('completed_visit_id')->nullable()->constrained('visits')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['follow_up_date', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('consultation_amendments');
        Schema::dropIfExists('consultation_vitals');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('appointments');
    }
};
