<?php

use App\Http\Controllers\Api\AdminLeadController;
use App\Http\Controllers\Api\ChatHistoryController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\ChatSessionController;
use App\Http\Controllers\Api\PackageBuilderController;
use App\Http\Controllers\Api\SendGridInboundController;
use App\Http\Controllers\Api\SendGridWebhookController;
use App\Http\Controllers\Api\WebsiteFormSubmissionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/chat/session', [ChatSessionController::class, 'store']);
Route::post('/chat/close/{conversation}', [ChatSessionController::class, 'close']);
Route::post('/chat/message/{conversation}', [ChatMessageController::class, 'store']);
Route::get('/chat/history/{conversation}', [ChatHistoryController::class, 'show']);
Route::post('/package-builder/calculate', [PackageBuilderController::class, 'calculate']);
Route::post('/package-builder/submit', [PackageBuilderController::class, 'submit']);
Route::post('/website-form/submit', [WebsiteFormSubmissionController::class, 'store']);
Route::post('/webhooks/sendgrid/events', [SendGridWebhookController::class, 'events'])->middleware('throttle:sendgrid-webhook');
Route::post('/webhooks/sendgrid/inbound', [SendGridInboundController::class, 'parse'])->middleware('throttle:sendgrid-webhook');

Route::middleware(['auth:sanctum', 'role:admin,owner,manager'])->prefix('admin')->group(function (): void {
    Route::get('/leads', [AdminLeadController::class, 'index']);
    Route::get('/leads/{lead}', [AdminLeadController::class, 'show']);
    Route::post('/leads/{lead}/status', [AdminLeadController::class, 'updateStatus']);
    Route::post('/leads/{lead}/follow-up', [AdminLeadController::class, 'scheduleFollowUp']);
});
