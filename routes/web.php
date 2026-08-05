<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('forms', FormController::class);
    
    Route::get('/forms/{form}/submissions', [FormSubmissionController::class, 'index'])->name('forms.submissions.index');
    Route::get('/submissions/{submission}', [FormSubmissionController::class, 'show'])->name('submissions.show');
});

use App\Http\Controllers\PublicFormController;

Route::get('/p/{share_token}', [PublicFormController::class, 'show'])->name('forms.public.show');
Route::post('/p/{share_token}/submit', [PublicFormController::class, 'submit'])->name('forms.public.submit');
