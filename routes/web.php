<?php

use App\Http\Controllers\AuthOtpController;
use App\Http\Controllers\DashboardController;
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
    Route::post('/login', [AuthOtpController::class, 'login'])->name('login.store');
    Route::get('/signup', [AuthOtpController::class, 'showRegister'])->name('signup');
    Route::post('/signup', [AuthOtpController::class, 'register'])->name('signup.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $normalizedRole = strtolower(trim((string) $user?->role));
        return redirect()->route(in_array($normalizedRole, ['admin', 'owner', 'manager', 'photographer', 'editor'], true) ? 'admin.dashboard' : 'user.dashboard');
    })->name('dashboard');

    Route::middleware('role:admin,owner,manager,photographer,editor')->group(function (): void {
        Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/admin/projects', [DashboardController::class, 'adminProjectsIndex'])->name('admin.projects.index');
        Route::get('/admin/media-delivery', [DashboardController::class, 'adminMediaDeliveryIndex'])->name('admin.media-delivery.index');
        Route::get('/admin/media-delivery/watermark', [DashboardController::class, 'adminMediaWatermarkSettingsIndex'])->name('admin.media-delivery.watermark.index');
        Route::get('/admin/media-delivery/watermark/logo', [DashboardController::class, 'adminMediaWatermarkLogoView'])->name('admin.media-delivery.watermark.logo');
        Route::get('/admin/clients', [DashboardController::class, 'adminClientsIndex'])->name('admin.clients.index');
        Route::get('/admin/clients/{client}', [DashboardController::class, 'adminClientShow'])->name('admin.clients.show');
    });

    Route::middleware('role:admin,owner,manager')->group(function (): void {
        Route::get('/admin/leads', [DashboardController::class, 'adminLeadsIndex'])->name('admin.leads.index');
        Route::get('/admin/leads-ai-assistant', [DashboardController::class, 'adminAiAssistantLeadsIndex'])->name('admin.leads.ai.index');
        Route::get('/admin/leads-packages', [DashboardController::class, 'adminPackageLeadsIndex'])->name('admin.leads.packages.index');
        Route::get('/admin/quotes', [DashboardController::class, 'adminQuotesIndex'])->name('admin.quotes.index');
        Route::get('/admin/invoices', [DashboardController::class, 'adminInvoicesIndex'])->name('admin.invoices.index');
        Route::get('/admin/emails', [DashboardController::class, 'adminEmailsIndex'])->name('admin.emails.index');
        Route::get('/admin/emails/inbox', [DashboardController::class, 'adminEmailsInbox'])->name('admin.emails.inbox');
        Route::get('/admin/emails/sent', [DashboardController::class, 'adminEmailsSent'])->name('admin.emails.sent');
        Route::get('/admin/emails/drafts', [DashboardController::class, 'adminEmailsDrafts'])->name('admin.emails.drafts');
        Route::get('/admin/emails/automation', [DashboardController::class, 'adminEmailAutomationSettingsIndex'])->name('admin.emails.automation.index');
        Route::post('/admin/emails/automation', [DashboardController::class, 'adminEmailAutomationSettingsUpdate'])->name('admin.emails.automation.update');
        Route::post('/admin/emails/automation/backfill', [DashboardController::class, 'adminEmailAutomationBackfillRun'])->name('admin.emails.automation.backfill');
        Route::post('/admin/emails/send', [DashboardController::class, 'adminEmailSend'])->name('admin.emails.send');
        Route::post('/admin/emails/ai-write', [DashboardController::class, 'adminEmailAiWrite'])->name('admin.emails.ai-write');
        Route::post('/admin/emails/drafts/save', [DashboardController::class, 'adminEmailDraftStore'])->name('admin.emails.drafts.save');
        Route::post('/admin/emails/drafts/{draft}/send', [DashboardController::class, 'adminEmailDraftSend'])->name('admin.emails.drafts.send');
        Route::post('/admin/emails/drafts/{draft}/delete', [DashboardController::class, 'adminEmailDraftDelete'])->name('admin.emails.drafts.delete');
        Route::post('/admin/quotes/manual', [DashboardController::class, 'adminQuoteManualStore'])->name('admin.quotes.manual-store');
        Route::get('/admin/exports/leads.csv', [DashboardController::class, 'adminExportLeadsCsv'])->name('admin.exports.leads');
        Route::get('/admin/exports/quotes.csv', [DashboardController::class, 'adminExportQuotesCsv'])->name('admin.exports.quotes');
        Route::get('/admin/exports/followups.csv', [DashboardController::class, 'adminExportFollowUpsCsv'])->name('admin.exports.followups');
        Route::get('/admin/form-submissions', [DashboardController::class, 'adminFormSubmissions'])->name('admin.form-submissions');
        Route::get('/admin/form-submissions/{submission}', [DashboardController::class, 'adminFormSubmissionShow'])->name('admin.form-submissions.show');
        Route::post('/admin/form-submissions/{submission}/status', [DashboardController::class, 'adminFormSubmissionStatusUpdate'])->name('admin.form-submissions.status');
        Route::post('/admin/clients', [DashboardController::class, 'adminClientStore'])->name('admin.clients.store');
        Route::post('/admin/clients/{client}/projects', [DashboardController::class, 'adminClientProjectStore'])->name('admin.clients.projects.store');
        Route::post('/admin/projects/{project}/status', [DashboardController::class, 'adminClientProjectStatusUpdate'])->name('admin.projects.status');
        Route::post('/admin/projects/{project}/media', [DashboardController::class, 'adminProjectMediaStore'])->name('admin.projects.media.store');
        Route::get('/admin/projects/{project}/media/{media}/view', [DashboardController::class, 'adminProjectMediaView'])->name('admin.projects.media.view');
        Route::post('/admin/projects/{project}/media/{media}/delete', [DashboardController::class, 'adminProjectMediaDestroy'])->name('admin.projects.media.delete');
        Route::post('/admin/projects/{project}/delivery-zip', [DashboardController::class, 'adminProjectDeliveryZipStore'])->name('admin.projects.delivery-zip.store');
        Route::post('/admin/media-delivery/watermark', [DashboardController::class, 'adminMediaWatermarkSettingsUpdate'])->name('admin.media-delivery.watermark.update');
        Route::post('/admin/media-delivery/watermark/rebuild', [DashboardController::class, 'adminMediaWatermarkRebuild'])->name('admin.media-delivery.watermark.rebuild');
        Route::post('/admin/media-delivery/folders/migrate', [DashboardController::class, 'adminMediaFolderMigrationRun'])->name('admin.media-delivery.folders.migrate');
        Route::post('/admin/clients/{client}/invoices', [DashboardController::class, 'adminClientInvoiceStore'])->name('admin.clients.invoices.store');
        Route::post('/admin/clients/{client}/messages', [DashboardController::class, 'adminClientMessageStore'])->name('admin.clients.messages.store');
        Route::post('/admin/invoices/{invoice}/status', [DashboardController::class, 'adminInvoiceStatusUpdate'])->name('admin.invoices.status');
        Route::post('/admin/service-requests/{serviceRequest}/status', [DashboardController::class, 'adminServiceRequestStatusUpdate'])->name('admin.service-requests.status');
        Route::get('/admin/leads/{lead}', [DashboardController::class, 'adminLeadShow'])->name('admin.leads.show');
        Route::get('/admin/leads/{lead}/conversation.pdf', [DashboardController::class, 'adminLeadConversationPdf'])->name('admin.leads.conversation-pdf');
        Route::post('/admin/leads/{lead}/status', [DashboardController::class, 'adminLeadStatusUpdate'])->name('admin.leads.status');
        Route::post('/admin/leads/{lead}/email-send', [DashboardController::class, 'adminLeadEmailSend'])->name('admin.leads.email.send');
        Route::post('/admin/leads/{lead}/follow-up', [DashboardController::class, 'adminFollowUpStore'])->name('admin.leads.follow-up');
        Route::post('/admin/leads/{lead}/delete', [DashboardController::class, 'adminLeadDestroy'])->name('admin.leads.delete');
        Route::post('/admin/follow-ups/{followUp}/status', [DashboardController::class, 'adminFollowUpStatusUpdate'])->name('admin.follow-ups.status');
        Route::get('/admin/quotes/{quote}', [DashboardController::class, 'adminQuoteShow'])->name('admin.quotes.show');
        Route::post('/admin/quotes/{quote}/status', [DashboardController::class, 'adminQuoteStatusUpdate'])->name('admin.quotes.status');
        Route::post('/admin/quotes/{quote}/resend-email', [DashboardController::class, 'adminQuoteResendEmail'])->name('admin.quotes.resend-email');
        Route::post('/admin/quotes/{quote}/delete', [DashboardController::class, 'adminQuoteDestroy'])->name('admin.quotes.delete');
        Route::post('/admin/quotes/{quote}/line-items', [DashboardController::class, 'adminQuoteLineItemsUpdate'])->name('admin.quotes.line-items');
    });

    Route::middleware('role:admin,owner')->group(function (): void {
        Route::get('/admin/users', [DashboardController::class, 'adminUsersIndex'])->name('admin.users.index');
        Route::post('/admin/users', [DashboardController::class, 'adminUserStore'])->name('admin.users.store');
        Route::post('/admin/users/{user}/delete', [DashboardController::class, 'adminUserDestroy'])->name('admin.users.delete');
        Route::post('/admin/clients/{client}/delete', [DashboardController::class, 'adminClientDestroy'])->name('admin.clients.delete');
    });

    Route::get('/user/dashboard', [DashboardController::class, 'userDashboard'])->middleware('role:user,client,agent')->name('user.dashboard');
    Route::post('/user/service-requests', [DashboardController::class, 'userServiceRequestStore'])->middleware('role:user,client,agent')->name('user.service-requests.store');
    Route::get('/user/quotes/{quote}', [DashboardController::class, 'userQuoteShow'])->middleware('role:user,client,agent')->name('user.quotes.show');
    Route::post('/user/quotes/{quote}/revision-request', [DashboardController::class, 'userQuoteRevisionRequest'])->middleware('role:user,client,agent')->name('user.quotes.revision-request');
    Route::get('/user/projects/{project}/media/{media}/preview', [DashboardController::class, 'userProjectMediaPreview'])->middleware('role:user,client,agent')->name('user.projects.media.preview');
    Route::get('/user/projects/{project}/media/{media}/download', [DashboardController::class, 'userProjectMediaDownload'])->middleware('role:user,client,agent')->name('user.projects.media.download');
    Route::get('/user/projects/{project}/download-zip', [DashboardController::class, 'userProjectMediaZipDownload'])->middleware('role:user,client,agent')->name('user.projects.media.download-zip');
    Route::get('/notifications/feed', [DashboardController::class, 'notificationsFeed'])->name('notifications.feed');
    Route::post('/notifications/read-all-ajax', [DashboardController::class, 'notificationsReadAllAjax'])->name('notifications.read-all-ajax');
    Route::post('/notifications/{notification}/read-ajax', [DashboardController::class, 'notificationsReadAjax'])->name('notifications.read-ajax');
    Route::post('/notifications/read-all', [DashboardController::class, 'notificationsReadAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [DashboardController::class, 'notificationsRead'])->name('notifications.read');
});

Route::post('/logout', [AuthOtpController::class, 'logout'])->middleware('auth')->name('logout');
