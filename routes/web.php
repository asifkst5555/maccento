<?php

use App\Http\Controllers\AuthOtpController;
use App\Http\Controllers\AdminAssistantController;
use App\Http\Controllers\Api\SendGridInboundController;
use App\Http\Controllers\Api\SendGridWebhookController;
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
Route::post('/webhooks/sendgrid/events', [SendGridWebhookController::class, 'events'])->middleware('throttle:sendgrid-webhook');
Route::post('/webhooks/sendgrid/inbound', [SendGridInboundController::class, 'parse'])->middleware('throttle:sendgrid-webhook');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthOtpController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthOtpController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.store');
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
        Route::get('/admin/assistant/session', [AdminAssistantController::class, 'session'])->name('admin.assistant.session');
        Route::post('/admin/assistant/message', [AdminAssistantController::class, 'message'])->name('admin.assistant.message');
        Route::get('/admin/projects', [DashboardController::class, 'adminProjectsIndex'])->name('admin.projects.index');
        Route::get('/admin/projects/{project}/workspace', [DashboardController::class, 'adminProjectWorkspace'])->name('admin.projects.workspace');
        Route::get('/admin/projects/{project}/calendar.ics', [DashboardController::class, 'adminProjectCalendarIcs'])->name('admin.projects.calendar');
        Route::post('/admin/projects', [DashboardController::class, 'adminProjectStore'])->name('admin.projects.store');
        Route::get('/admin/media-delivery', [DashboardController::class, 'adminMediaDeliveryIndex'])->name('admin.media-delivery.index');
        Route::get('/admin/projects/{project}/media/{media}/view', [DashboardController::class, 'adminProjectMediaView'])->name('admin.projects.media.view');
        Route::post('/admin/projects/{project}/comments', [DashboardController::class, 'adminProjectCommentStore'])->name('admin.projects.comments.store');
        Route::post('/admin/projects/{project}/comments/{comment}/delete', [DashboardController::class, 'adminProjectCommentDestroy'])->name('admin.projects.comments.delete');
        Route::post('/admin/projects/{project}/comments/{comment}/edit', [DashboardController::class, 'adminProjectCommentUpdate'])->name('admin.projects.comments.update');
        Route::post('/admin/projects/{project}/media', [DashboardController::class, 'adminProjectMediaStore'])->name('admin.projects.media.store');
        Route::post('/admin/projects/{project}/media/{media}/delete', [DashboardController::class, 'adminProjectMediaDestroy'])->name('admin.projects.media.delete');
        Route::post('/admin/projects/{project}/raw-zip', [DashboardController::class, 'adminProjectRawZipStore'])->name('admin.projects.raw-zip.store');
        Route::post('/admin/projects/{project}/delivery-zip', [DashboardController::class, 'adminProjectDeliveryZipStore'])->name('admin.projects.delivery-zip.store');
        Route::get('/admin/messages', [DashboardController::class, 'adminClientMessagesIndex'])->name('admin.messages.index');
        Route::post('/admin/messages', [DashboardController::class, 'adminClientMessagesCenterStore'])->name('admin.messages.store');
        Route::post('/admin/messages/users', [DashboardController::class, 'adminUserMessagesStore'])->name('admin.user-messages.store');
    });

    Route::middleware('role:admin,owner,manager')->group(function (): void {
        Route::get('/admin/leads', [DashboardController::class, 'adminLeadsIndex'])->name('admin.leads.index');
        Route::get('/admin/leads-ai-assistant', [DashboardController::class, 'adminAiAssistantLeadsIndex'])->name('admin.leads.ai.index');
        Route::get('/admin/leads-packages', [DashboardController::class, 'adminPackageLeadsIndex'])->name('admin.leads.packages.index');
        Route::get('/admin/quotes', [DashboardController::class, 'adminQuotesIndex'])->name('admin.quotes.index');
        Route::get('/admin/reports', [DashboardController::class, 'adminReportsIndex'])->name('admin.reports.index');
        Route::get('/admin/invoices', [DashboardController::class, 'adminInvoicesIndex'])->name('admin.invoices.index');
        Route::get('/admin/clients', [DashboardController::class, 'adminClientsIndex'])->name('admin.clients.index');
        Route::get('/admin/clients/{client}', [DashboardController::class, 'adminClientShow'])->name('admin.clients.show');
        Route::get('/admin/booking-requests', [DashboardController::class, 'adminBookingRequestsIndex'])->name('admin.booking-requests.index');
        Route::get('/admin/service-requests', [DashboardController::class, 'adminServiceRequestsIndex'])->name('admin.service-requests.index');
        Route::get('/admin/request-audit-log', [DashboardController::class, 'adminRequestEditLogsIndex'])->name('admin.request-edit-logs.index');
        Route::get('/admin/system-health', [DashboardController::class, 'adminSystemHealthIndex'])->name('admin.system-health.index');
        Route::get('/admin/backup-restore', [DashboardController::class, 'adminBackupRestoreIndex'])->name('admin.backup-restore.index');
        Route::post('/admin/system-health/backup-settings', [DashboardController::class, 'adminBackupSettingsUpdate'])->name('admin.system-health.backup-settings.update');
        Route::post('/admin/system-health/backup-now', [DashboardController::class, 'adminBackupRunNow'])->name('admin.system-health.backup-now');
        Route::post('/admin/system-health/backup-restore', [DashboardController::class, 'adminBackupRestore'])->name('admin.system-health.backup-restore');
        Route::post('/admin/system-health/backup-upload-restore', [DashboardController::class, 'adminBackupUploadRestore'])->name('admin.system-health.backup-upload-restore');
        Route::get('/admin/system-health/backup-download', [DashboardController::class, 'adminBackupDownload'])->name('admin.system-health.backup-download');
        Route::get('/admin/emails', [DashboardController::class, 'adminEmailsIndex'])->name('admin.emails.index');
        Route::get('/admin/settings', [DashboardController::class, 'adminSettingsIndex'])->name('admin.settings.index');
        Route::post('/admin/settings/currency', [DashboardController::class, 'adminCurrencySettingsUpdate'])->name('admin.settings.currency.update');
        Route::get('/admin/api-integrations', [DashboardController::class, 'adminApiIntegrationsIndex'])->name('admin.api-integrations.index');
        Route::post('/admin/api-integrations', [DashboardController::class, 'adminApiIntegrationsUpdate'])->name('admin.api-integrations.update');
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
        Route::post('/admin/emails/inbox/{inbound}/delete', [DashboardController::class, 'adminEmailInboxDelete'])->name('admin.emails.inbox.delete');
        Route::post('/admin/emails/sent/{emailLog}/delete', [DashboardController::class, 'adminEmailSentDelete'])->name('admin.emails.sent.delete');
        Route::post('/admin/quotes/manual', [DashboardController::class, 'adminQuoteManualStore'])->name('admin.quotes.manual-store');
        Route::get('/admin/exports/leads.csv', [DashboardController::class, 'adminExportLeadsCsv'])->name('admin.exports.leads');
        Route::get('/admin/exports/quotes.csv', [DashboardController::class, 'adminExportQuotesCsv'])->name('admin.exports.quotes');
        Route::get('/admin/exports/followups.csv', [DashboardController::class, 'adminExportFollowUpsCsv'])->name('admin.exports.followups');
        Route::get('/admin/form-submissions', [DashboardController::class, 'adminFormSubmissions'])->name('admin.form-submissions');
        Route::get('/admin/form-submissions/{submission}', [DashboardController::class, 'adminFormSubmissionShow'])->name('admin.form-submissions.show');
        Route::post('/admin/form-submissions/{submission}/status', [DashboardController::class, 'adminFormSubmissionStatusUpdate'])->name('admin.form-submissions.status');
        Route::post('/admin/form-submissions/{submission}/delete', [DashboardController::class, 'adminFormSubmissionDestroy'])->name('admin.form-submissions.delete');
        Route::post('/admin/clients/{client}/projects', [DashboardController::class, 'adminClientProjectStore'])->name('admin.clients.projects.store');
        Route::get('/admin/clients/{client}/export', [DashboardController::class, 'adminClientExport'])->name('admin.clients.export');
        Route::post('/admin/clients/{client}/anonymize', [DashboardController::class, 'adminClientAnonymize'])->name('admin.clients.anonymize');
        Route::post('/admin/projects/{project}/status', [DashboardController::class, 'adminClientProjectStatusUpdate'])->name('admin.projects.status');
        Route::post('/admin/projects/{project}/assignments', [DashboardController::class, 'adminProjectAssignmentsUpdate'])->name('admin.projects.assignments.update');
        Route::post('/admin/projects/{project}/tasks', [DashboardController::class, 'adminProjectTaskStore'])->name('admin.projects.tasks.store');
        Route::post('/admin/projects/{project}/tasks/{task}', [DashboardController::class, 'adminProjectTaskUpdate'])->name('admin.projects.tasks.update');
        Route::post('/admin/projects/{project}/tasks/{task}/delete', [DashboardController::class, 'adminProjectTaskDestroy'])->name('admin.projects.tasks.delete');
        Route::get('/admin/media-delivery/watermark', [DashboardController::class, 'adminMediaWatermarkSettingsIndex'])->name('admin.media-delivery.watermark.index');
        Route::get('/admin/media-delivery/watermark/logo', [DashboardController::class, 'adminMediaWatermarkLogoView'])->name('admin.media-delivery.watermark.logo');
        Route::post('/admin/media-delivery/watermark', [DashboardController::class, 'adminMediaWatermarkSettingsUpdate'])->name('admin.media-delivery.watermark.update');
        Route::post('/admin/media-delivery/watermark/rebuild', [DashboardController::class, 'adminMediaWatermarkRebuild'])->name('admin.media-delivery.watermark.rebuild');
        Route::post('/admin/media-delivery/folders/migrate', [DashboardController::class, 'adminMediaFolderMigrationRun'])->name('admin.media-delivery.folders.migrate');
        Route::post('/admin/clients/{client}/invoices', [DashboardController::class, 'adminClientInvoiceStore'])->name('admin.clients.invoices.store');
        Route::post('/admin/clients/{client}/messages', [DashboardController::class, 'adminClientMessageStore'])->name('admin.clients.messages.store');
        Route::post('/admin/invoices/{invoice}/status', [DashboardController::class, 'adminInvoiceStatusUpdate'])->name('admin.invoices.status');
        Route::post('/admin/invoices/{invoice}/payments', [DashboardController::class, 'adminInvoicePaymentStore'])->name('admin.invoices.payments.store');
        Route::post('/admin/invoices/{invoice}/delete', [DashboardController::class, 'adminInvoiceDestroy'])->name('admin.invoices.delete');
        Route::get('/admin/invoices/{invoice}/download', [DashboardController::class, 'adminInvoicePdfDownload'])->name('admin.invoices.download');
        Route::post('/admin/invoices/settings', [DashboardController::class, 'adminInvoiceSettingsUpdate'])->name('admin.invoices.settings.update');
        Route::post('/admin/booking-requests/{bookingRequest}/status', [DashboardController::class, 'adminBookingRequestStatusUpdate'])->name('admin.booking-requests.status');
        Route::post('/admin/booking-requests/{bookingRequest}/edit', [DashboardController::class, 'adminBookingRequestUpdate'])->name('admin.booking-requests.update');
        Route::post('/admin/booking-requests/{bookingRequest}/delete', [DashboardController::class, 'adminBookingRequestDestroy'])->name('admin.booking-requests.delete');
        Route::post('/admin/service-requests/{serviceRequest}/status', [DashboardController::class, 'adminServiceRequestStatusUpdate'])->name('admin.service-requests.status');
        Route::post('/admin/service-requests/{serviceRequest}/edit', [DashboardController::class, 'adminServiceRequestUpdate'])->name('admin.service-requests.update');
        Route::post('/admin/service-requests/{serviceRequest}/delete', [DashboardController::class, 'adminServiceRequestDestroy'])->name('admin.service-requests.delete');
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
        Route::post('/admin/clients', [DashboardController::class, 'adminClientStore'])->name('admin.clients.store');
        Route::post('/admin/clients/{client}/delete', [DashboardController::class, 'adminClientDestroy'])->name('admin.clients.delete');
        Route::post('/admin/projects/{project}/delete', [DashboardController::class, 'adminProjectDestroy'])->name('admin.projects.delete');
    });

    Route::middleware('role:user,client,agent')->group(function (): void {
        Route::get('/user/dashboard', [DashboardController::class, 'userDashboard'])->name('user.dashboard');
        Route::get('/user/projects', [DashboardController::class, 'userProjectsIndex'])->name('user.projects.index');
        Route::get('/user/projects/{project}', [DashboardController::class, 'userProjectShow'])->name('user.projects.show');
        Route::get('/user/service-requests', [DashboardController::class, 'userServiceRequestsIndex'])->name('user.service-requests.index');
        Route::get('/user/booking-requests', [DashboardController::class, 'userBookingRequestsIndex'])->name('user.booking-requests.index');
        Route::get('/user/invoices', [DashboardController::class, 'userInvoicesIndex'])->name('user.invoices.index');
        Route::get('/user/invoices/{invoice}/pay', [DashboardController::class, 'userInvoicePay'])->name('user.invoices.pay');
        Route::post('/user/invoices/{invoice}/pay/stripe', [DashboardController::class, 'userInvoiceStripeCheckout'])->name('user.invoices.stripe.checkout');
        Route::get('/user/invoices/{invoice}/pay/stripe/success', [DashboardController::class, 'userInvoiceStripeSuccess'])->name('user.invoices.stripe.success');
        Route::post('/user/invoices/{invoice}/pay/paypal', [DashboardController::class, 'userInvoicePayPalCreate'])->name('user.invoices.paypal.create');
        Route::get('/user/invoices/{invoice}/pay/paypal/success', [DashboardController::class, 'userInvoicePayPalSuccess'])->name('user.invoices.paypal.success');
        Route::post('/user/invoices/{invoice}/pay/manual', [DashboardController::class, 'userInvoiceManualNotify'])->name('user.invoices.manual.notify');
        Route::get('/user/quotes', [DashboardController::class, 'userQuotesIndex'])->name('user.quotes.index');
        Route::get('/user/messages', [DashboardController::class, 'userMessagesIndex'])->name('user.messages.index');
        Route::post('/user/messages', [DashboardController::class, 'userAdminMessageStore'])->name('user.messages.store');
        Route::get('/user/deliveries', [DashboardController::class, 'userDeliveriesIndex'])->name('user.deliveries.index');
        Route::get('/user/account', [DashboardController::class, 'userAccountIndex'])->name('user.account.index');
        Route::post('/user/account', [DashboardController::class, 'userAccountUpdate'])->name('user.account.update');
        Route::post('/user/service-requests', [DashboardController::class, 'userServiceRequestStore'])->name('user.service-requests.store');
        Route::post('/user/service-requests/{serviceRequest}/edit', [DashboardController::class, 'userServiceRequestUpdate'])->name('user.service-requests.update');
        Route::post('/user/service-requests/{serviceRequest}/delete', [DashboardController::class, 'userServiceRequestDestroy'])->name('user.service-requests.delete');
        Route::post('/user/requests', [DashboardController::class, 'userUnifiedRequestStore'])->name('user.requests.store');
        Route::post('/user/booking-requests', [DashboardController::class, 'userBookingRequestStore'])->name('user.booking-requests.store');
        Route::post('/user/booking-requests/{bookingRequest}/edit', [DashboardController::class, 'userBookingRequestUpdate'])->name('user.booking-requests.update');
        Route::post('/user/booking-requests/{bookingRequest}/delete', [DashboardController::class, 'userBookingRequestDestroy'])->name('user.booking-requests.delete');
        Route::get('/user/invoices/{invoice}/download', [DashboardController::class, 'userInvoicePdfDownload'])->name('user.invoices.download');
        Route::get('/user/quotes/{quote}', [DashboardController::class, 'userQuoteShow'])->name('user.quotes.show');
        Route::post('/user/quotes/{quote}/revision-request', [DashboardController::class, 'userQuoteRevisionRequest'])->name('user.quotes.revision-request');
        Route::post('/user/projects/{project}/comments', [DashboardController::class, 'userProjectCommentStore'])->name('user.projects.comments.store');
        Route::post('/user/projects/{project}/comments/{comment}/delete', [DashboardController::class, 'userProjectCommentDestroy'])->name('user.projects.comments.delete');
        Route::post('/user/projects/{project}/comments/{comment}/edit', [DashboardController::class, 'userProjectCommentUpdate'])->name('user.projects.comments.update');
        Route::get('/user/projects/{project}/media/{media}/preview', [DashboardController::class, 'userProjectMediaPreview'])->name('user.projects.media.preview');
        Route::get('/user/projects/{project}/media/{media}/download', [DashboardController::class, 'userProjectMediaDownload'])->name('user.projects.media.download');
        Route::get('/user/projects/{project}/download-zip', [DashboardController::class, 'userProjectMediaZipDownload'])->name('user.projects.media.download-zip');
    });
    Route::get('/notifications/feed', [DashboardController::class, 'notificationsFeed'])->name('notifications.feed');
    Route::post('/notifications/read-all-ajax', [DashboardController::class, 'notificationsReadAllAjax'])->name('notifications.read-all-ajax');
    Route::post('/notifications/{notification}/read-ajax', [DashboardController::class, 'notificationsReadAjax'])->name('notifications.read-ajax');
    Route::post('/notifications/read-all', [DashboardController::class, 'notificationsReadAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [DashboardController::class, 'notificationsRead'])->name('notifications.read');
});

Route::post('/logout', [AuthOtpController::class, 'logout'])->middleware('auth')->name('logout');


















