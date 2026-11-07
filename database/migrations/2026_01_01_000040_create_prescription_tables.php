<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('generic_name')->nullable();
            $table->string('form', 40)->nullable();
            $table->string('strength', 60)->nullable();
            $table->string('default_route', 40)->nullable();
            $table->string('manufacturer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['name', 'strength', 'form']);
            $table->index('generic_name');
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 40)->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('visit_id')->constrained()->restrictOnDelete();
            $table->foreignId('consultation_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            $table->date('prescription_date');
            $table->text('general_instructions')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('version')->default(1);
            // Amendment chain: the version this one replaces.
            $table->foreignId('previous_version_id')->nullable()->constrained('prescriptions')->restrictOnDelete();
            $table->string('amendment_reason')->nullable();
            // Repeat chain: the earlier prescription this one repeats.
            $table->foreignId('repeat_of_id')->nullable()->constrained('prescriptions')->restrictOnDelete();
            $table->string('repeat_reason')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'prescription_date']);
            $table->index(['doctor_id', 'prescription_date']);
            $table->index('status');
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->restrictOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('medicine_name');
            $table->string('generic_name')->nullable();
            $table->string('strength', 60)->nullable();
            $table->string('dosage', 100);
            $table->string('route', 40)->nullable();
            $table->string('frequency', 100);
            $table->string('timing', 100)->nullable();
            $table->unsignedSmallInteger('duration_days');
            $table->string('quantity', 60)->nullable();
            $table->string('instructions', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('doctor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->boolean('is_shared')->default(false);
            $table->text('general_instructions')->nullable();
            $table->json('items');
            $table->timestamps();
        });

        // Clinic-prescribed medication continuation, one course per finalized prescription item.
        Schema::create('medication_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('prescription_id')->constrained()->restrictOnDelete();
            $table->foreignId('prescription_item_id')->unique()->constrained()->restrictOnDelete();
            $table->string('medicine_name');
            $table->date('start_date');
            $table->unsignedSmallInteger('duration_days');
            $table->date('expected_end_date');
            $table->string('continuation_instructions', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('status_reason')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['status', 'expected_end_date']);
        });

        // Item-level record of each authorised repetition.
        Schema::create('medication_repetitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_prescription_id')->constrained('prescriptions')->restrictOnDelete();
            $table->foreignId('source_item_id')->constrained('prescription_items')->restrictOnDelete();
            $table->foreignId('new_prescription_id')->constrained('prescriptions')->restrictOnDelete();
            $table->foreignId('new_item_id')->constrained('prescription_items')->restrictOnDelete();
            $table->string('reason');
            $table->foreignId('authorized_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_repetitions');
        Schema::dropIfExists('medication_courses');
        Schema::dropIfExists('prescription_templates');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medicines');
    }
};
