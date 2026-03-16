<?php

return [
    'retention_days' => [
        'request_edit_logs' => (int) env('RETENTION_REQUEST_EDIT_LOGS_DAYS', 365),
        'email_logs' => (int) env('RETENTION_EMAIL_LOGS_DAYS', 365),
        'outbound_webhook_deliveries' => (int) env('RETENTION_WEBHOOK_LOGS_DAYS', 90),
        'panel_notifications' => (int) env('RETENTION_NOTIFICATIONS_DAYS', 90),
    ],
];
