<?php

use App\Http\Controllers\AuthOtpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::view('/', 'welcome', ['page' => 'home'])->name('home');
Route::view('/about-us', 'welcome', ['page' => 'about'])->name('about');
Route::view('/our-services', 'welcome', ['page' => 'services'])->name('services');
Route::view('/portfolio', 'welcome', ['page' => 'portfolio'])->name('portfolio');
Route::view('/our-plan', 'welcome', ['page' => 'plan'])->name('plan');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthOtpController::class, 'showLogin'])->name('login');
    Route::get('/signup', [AuthOtpController::class, 'showRegister'])->name('signup');
    Route::post('/signup', [AuthOtpController::class, 'register'])->name('signup.store');
    Route::post('/login/request-otp', [AuthOtpController::class, 'requestOtp'])->name('login.request-otp');
    Route::post('/login/verify-otp', [AuthOtpController::class, 'verifyOtp'])->name('login.verify-otp');
});

Route::post('/logout', [AuthOtpController::class, 'logout'])->middleware('auth')->name('logout');
