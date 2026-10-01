<?php

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\LabTestOrderController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.pages.auth.login');
});

Route::get('/dashboard', function () {
    return view('admin.pages.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth', 'role_id:1')->group(function () {

    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    Route::get('/doctors/search', [DoctorController::class, 'search'])->name('doctors.search');

    Route::resource('users', UserController::class);
    Route::resource('patients', PatientController::class);
    Route::resource('doctors', DoctorController::class);
    Route::resource('appointments', AppointmentController::class);
    Route::resource('prescriptions', PrescriptionController::class);
    Route::resource('lab-test-orders', LabTestOrderController::class);
    Route::resource('wards', WardController::class);
    Route::resource('beds', BedController::class);
    Route::resource('admissions', AdmissionController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('invoice-items', InvoiceItemController::class);

    Route::post('prescriptions/{prescription}/generate-invoice', [PrescriptionController::class, 'generateInvoice'])
    ->name('prescriptions.generate-invoice');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth', 'role_id:1,2')->group(function () {

    Route::resource('appointments', AppointmentController::class);
    Route::resource('prescriptions', PrescriptionController::class);
    Route::resource('lab-test-orders', LabTestOrderController::class);
});


Route::middleware('auth', 'role_id:1,2,3')->group(function () {

    Route::resource('patients', PatientController::class);
    Route::resource('appointments', AppointmentController::class);
    Route::resource('lab-test-orders', LabTestOrderController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    Route::get('/doctors/search', [DoctorController::class, 'search'])->name('doctors.search');
    
});




require __DIR__ . '/auth.php';
