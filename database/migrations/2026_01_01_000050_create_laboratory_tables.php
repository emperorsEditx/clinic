<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 40)->nullable()->unique();
            $table->string('category', 80);
            $table->string('sample_type', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['name', 'category']);
        });

        Schema::create('laboratory_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 40)->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('laboratory_test_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('test_name');
            $table->string('test_category', 80)->nullable();
            $table->string('priority', 20)->default('routine');
            $table->text('clinical_notes')->nullable();
            $table->string('laboratory_name')->nullable();
            $table->string('status', 20)->default('requested');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('sample_collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['patient_id', 'created_at']);
            $table->index('test_name');
        });

        Schema::create('laboratory_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 40)->unique();
            $table->foreignId('laboratory_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('test_name');
            $table->string('test_category', 80)->nullable();
            $table->string('laboratory_name')->nullable();
            $table->date('test_date')->nullable();
            $table->date('report_date');
            $table->text('result_summary')->nullable();
            $table->string('status', 20)->default('completed');
            $table->string('file_disk', 30)->default('local');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'report_date']);
            $table->index(['status', 'report_date']);
            $table->index('test_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_reports');
        Schema::dropIfExists('laboratory_requests');
        Schema::dropIfExists('laboratory_tests');
    }
};
