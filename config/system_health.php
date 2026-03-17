<?php

return [
    'failed_jobs_threshold' => (int) env('SYSTEM_HEALTH_FAILED_JOBS_THRESHOLD', 1),
    'failed_email_threshold' => (int) env('SYSTEM_HEALTH_FAILED_EMAIL_THRESHOLD', 1),
    'backup_max_age_days' => (int) env('SYSTEM_HEALTH_BACKUP_MAX_AGE_DAYS', 2),
    'backup_keep' => (int) env('SYSTEM_HEALTH_BACKUP_KEEP', 30),
    'backup_mysqldump_path' => env('SYSTEM_HEALTH_BACKUP_MYSQLDUMP_PATH', ''),
    'backup_mysql_path' => env('SYSTEM_HEALTH_BACKUP_MYSQL_PATH', ''),
];
