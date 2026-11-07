<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\Laboratory\LabReportController;
use App\Http\Controllers\Laboratory\LabRequestController;
use App\Http\Controllers\Laboratory\LabTestController;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PrescriptionTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Patients & medical history
    Route::get('patients/search', [PatientController::class, 'search'])->name('patients.search')->middleware('throttle:120,1');
    Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])->name('patients.restore');
    Route::get('patients/{patient}/print', [PatientController::class, 'printProfile'])->name('patients.print');
    Route::get('patients/{patient}/history.pdf', [PatientController::class, 'historyPdf'])->name('patients.history-pdf');
    Route::resource('patients', PatientController::class);

    Route::whereIn('type', ['condition', 'allergy', 'medication', 'note'])->group(function () {
        Route::post('patients/{patient}/history/{type}', [MedicalHistoryController::class, 'store'])->name('patients.history.store');
        Route::put('patients/{patient}/history/{type}/{entry}', [MedicalHistoryController::class, 'update'])->name('patients.history.update');
        Route::delete('patients/{patient}/history/{type}/{entry}', [MedicalHistoryController::class, 'destroy'])->name('patients.history.destroy');
        Route::get('patients/{patient}/history/{type}/{entry}/changes', [MedicalHistoryController::class, 'changes'])->name('patients.history.changes');
    });

    // Appointments & visits
    Route::resource('appointments', AppointmentController::class)->except('destroy');
    Route::post('appointments/{appointment}/status', [AppointmentController::class, 'status'])->name('appointments.status');
    Route::post('appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');

    Route::resource('visits', VisitController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('visits/{visit}/cancel', [VisitController::class, 'cancel'])->name('visits.cancel');
    Route::post('visits/{visit}/consult', [VisitController::class, 'consult'])->name('visits.consult');

    // Consultations
    Route::resource('consultations', ConsultationController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::get('consultations/{consultation}/amend', [ConsultationController::class, 'amendForm'])->name('consultations.amend');
    Route::post('consultations/{consultation}/amend', [ConsultationController::class, 'amend'])->name('consultations.amend.store');

    // Prescriptions & medications
    Route::get('prescription-templates', [PrescriptionTemplateController::class, 'index'])->name('prescription-templates.index');
    Route::delete('prescription-templates/{template}', [PrescriptionTemplateController::class, 'destroy'])->name('prescription-templates.destroy');
    Route::resource('prescriptions', PrescriptionController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::post('prescriptions/{prescription}/amend', [PrescriptionController::class, 'amend'])->name('prescriptions.amend');
    Route::get('prescriptions/{prescription}/pdf', [PrescriptionController::class, 'pdf'])->name('prescriptions.pdf');
    Route::post('prescriptions/{prescription}/template', [PrescriptionController::class, 'saveTemplate'])->name('prescriptions.template');
    Route::get('patients/{patient}/repeat-prescription', [PrescriptionController::class, 'repeatForm'])->name('prescriptions.repeat');
    Route::post('patients/{patient}/repeat-prescription', [PrescriptionController::class, 'repeat'])->name('prescriptions.repeat.store');

    Route::get('medications', [MedicationController::class, 'index'])->name('medications.index');
    Route::post('medication-courses/{course}/status', [MedicationController::class, 'status'])->name('medications.status');

    Route::get('medicines/search', [MedicineController::class, 'search'])->name('medicines.search')->middleware('throttle:240,1');
    Route::resource('medicines', MedicineController::class)->only(['index', 'store', 'update']);

    Route::get('follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::post('follow-ups/{followUp}/status', [FollowUpController::class, 'status'])->name('follow-ups.status');

    // Laboratory
    Route::prefix('laboratory')->name('laboratory.')->group(function () {
        Route::get('requests', [LabRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/create', [LabRequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [LabRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{laboratoryRequest}', [LabRequestController::class, 'show'])->name('requests.show');
        Route::post('requests/{laboratoryRequest}/status', [LabRequestController::class, 'status'])->name('requests.status');
        Route::post('requests/{laboratoryRequest}/reports', [LabReportController::class, 'storeForRequest'])->name('requests.upload');

        Route::get('reports', [LabReportController::class, 'index'])->name('reports.index');
        Route::get('reports/create', [LabReportController::class, 'create'])->name('reports.create');
        Route::post('reports', [LabReportController::class, 'store'])->name('reports.store');
        Route::get('reports/{report}', [LabReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{report}/file', [LabReportController::class, 'download'])->name('reports.download');
        Route::post('reports/{report}/review', [LabReportController::class, 'review'])->name('reports.review');

        Route::get('tests', [LabTestController::class, 'index'])->name('tests.index');
        Route::post('tests', [LabTestController::class, 'store'])->name('tests.store');
        Route::put('tests/{laboratoryTest}', [LabTestController::class, 'update'])->name('tests.update');
    });

    // Administration
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{key}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{key}/export/{format}', [ReportController::class, 'export'])->whereIn('format', ['xlsx', 'csv', 'pdf', 'print'])->name('reports.export');

    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
});
