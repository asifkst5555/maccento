<?php

namespace App\Providers;

use App\Models\ApiIntegrationSetting;
use App\Models\PanelNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->applyApiIntegrationMailSettings();
        $this->applyApiIntegrationAiSettings();
        $this->applyApiIntegrationStorageSettings();

        View::composer('layouts.panel', function ($view): void {
            if (!auth()->check()) {
                return;
            }

            $userId = (int) auth()->id();
            $unreadCount = PanelNotification::query()
                ->where('user_id', $userId)
                ->whereNull('read_at')
                ->count();

            $items = PanelNotification::query()
                ->where('user_id', $userId)
                ->latest('id')
                ->limit(12)
                ->get();

            $view->with('panelUnreadNotifications', $unreadCount);
            $view->with('panelNotifications', $items);
        });
    }

    private function applyApiIntegrationMailSettings(): void
    {
        try {
            if (!Schema::hasTable('api_integration_settings')) {
                return;
            }

            $settings = ApiIntegrationSetting::query()->first();
            if (!$settings) {
                return;
            }

            $mailer = trim((string) ($settings->mail_mailer ?? ''));
            if ($mailer !== '') {
                config(['mail.default' => $mailer]);
            }

            $host = trim((string) ($settings->mail_host ?? ''));
            if ($host !== '') {
                config(['mail.mailers.smtp.host' => $host]);
            }

            if (!empty($settings->mail_port)) {
                config(['mail.mailers.smtp.port' => (int) $settings->mail_port]);
            }

            $username = trim((string) ($settings->mail_username ?? ''));
            if ($username !== '') {
                config(['mail.mailers.smtp.username' => $username]);
            }

            $password = (string) ($settings->mail_password ?? '');
            if ($password !== '') {
                config(['mail.mailers.smtp.password' => $password]);
            }

            $encryption = trim((string) ($settings->mail_encryption ?? ''));
            if ($encryption !== '') {
                config(['mail.mailers.smtp.encryption' => $encryption]);
            }

            $fromAddress = trim((string) ($settings->mail_from_address ?? ''));
            if ($fromAddress !== '') {
                config(['mail.from.address' => $fromAddress]);
                config(['mail.lead_alert_address' => $fromAddress]);
            }

            $fromName = trim((string) ($settings->mail_from_name ?? ''));
            if ($fromName !== '') {
                config(['mail.from.name' => $fromName]);
            }
        } catch (\Throwable $exception) {
            return;
        }
    }

    private function applyApiIntegrationAiSettings(): void
    {
        try {
            if (!Schema::hasTable('api_integration_settings')) {
                return;
            }

            $settings = ApiIntegrationSetting::query()->first();
            if (!$settings) {
                return;
            }

            $provider = trim((string) ($settings->ai_provider ?? ''));
            if ($provider !== '') {
                config(['ai.provider' => $provider]);
            }

            $defaultModel = trim((string) ($settings->ai_model ?? ''));
            if ($defaultModel !== '') {
                config(['ai.default_model' => $defaultModel]);
            }

            $openAiKey = trim((string) ($settings->openai_api_key ?? ''));
            if ($openAiKey !== '') {
                config(['ai.openai.api_key' => $openAiKey]);
            }

            $openAiBaseUrl = trim((string) ($settings->openai_base_url ?? ''));
            if ($openAiBaseUrl !== '') {
                config(['ai.openai.base_url' => $openAiBaseUrl]);
            }

            $openRouterKey = trim((string) ($settings->openrouter_api_key ?? ''));
            if ($openRouterKey !== '') {
                config(['ai.openrouter.api_key' => $openRouterKey]);
            }

            $openRouterBaseUrl = trim((string) ($settings->openrouter_base_url ?? ''));
            if ($openRouterBaseUrl !== '') {
                config(['ai.openrouter.base_url' => $openRouterBaseUrl]);
            }

            $openRouterModel = trim((string) ($settings->openrouter_model ?? ''));
            if ($openRouterModel !== '') {
                config(['ai.openrouter.model' => $openRouterModel]);
            }

            $geminiKey = trim((string) ($settings->gemini_api_key ?? ''));
            if ($geminiKey !== '') {
                config(['ai.gemini.api_key' => $geminiKey]);
            }

            $geminiBaseUrl = trim((string) ($settings->gemini_base_url ?? ''));
            if ($geminiBaseUrl !== '') {
                config(['ai.gemini.base_url' => $geminiBaseUrl]);
            }

            $geminiModel = trim((string) ($settings->gemini_model ?? ''));
            if ($geminiModel !== '') {
                config(['ai.gemini.model' => $geminiModel]);
            }
        } catch (\Throwable $exception) {
            return;
        }
    }

    private function applyApiIntegrationStorageSettings(): void
    {
        try {
            if (!Schema::hasTable('api_integration_settings')) {
                return;
            }

            $settings = ApiIntegrationSetting::query()->first();
            if (!$settings) {
                return;
            }

            $mediaDisk = trim((string) ($settings->media_disk ?? ''));
            if ($mediaDisk !== '') {
                config(['filesystems.default' => $mediaDisk]);
            }

            $s3Key = trim((string) ($settings->s3_key ?? ''));
            $s3Secret = (string) ($settings->s3_secret ?? '');
            $s3Region = trim((string) ($settings->s3_region ?? ''));
            $s3Bucket = trim((string) ($settings->s3_bucket ?? ''));
            $s3Endpoint = trim((string) ($settings->s3_endpoint ?? ''));
            $s3PathStyle = (bool) ($settings->s3_path_style ?? false);

            if ($s3Key !== '' && $s3Secret !== '' && $s3Bucket !== '') {
                config([
                    'filesystems.disks.s3.key' => $s3Key,
                    'filesystems.disks.s3.secret' => $s3Secret,
                    'filesystems.disks.s3.region' => $s3Region !== '' ? $s3Region : 'us-east-1',
                    'filesystems.disks.s3.bucket' => $s3Bucket,
                    'filesystems.disks.s3.endpoint' => $s3Endpoint !== '' ? $s3Endpoint : null,
                    'filesystems.disks.s3.use_path_style_endpoint' => $s3PathStyle,
                ]);
            }
        } catch (\Throwable $exception) {
            return;
        }
    }
}
