<?php

namespace App\Console\Commands;

use App\Models\ProjectTask;
use App\Services\PanelNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendProjectTaskReminders extends Command
{
    protected $signature = 'projects:task-reminders {--notify : Send notifications for due tasks}';
    protected $description = 'Notify assignees about due or overdue project tasks.';

    public function handle(PanelNotificationService $notificationService): int
    {
        if (!$this->option('notify')) {
            $this->line('Notification option not enabled. Use --notify.');
            return self::SUCCESS;
        }

        $today = now()->toDateString();
        $tasks = ProjectTask::query()
            ->whereIn('status', ['open', 'in_progress', 'blocked'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today)
            ->with(['project:id,title,client_id', 'assignee:id,name,email'])
            ->get();

        $notified = 0;
        foreach ($tasks as $task) {
            $cacheKey = 'task_reminder:' . $task->id . ':' . $task->due_date;
            if (Cache::has($cacheKey)) {
                continue;
            }

            $message = $task->title . ' (due ' . $task->due_date?->format('Y-m-d') . ')';
            $actionUrl = $task->project ? route('admin.projects.workspace', $task->project) : route('admin.projects.index');

            if ($task->assigned_to) {
                $notificationService->notifyUser(
                    (int) $task->assigned_to,
                    'project_task_due',
                    'Task due',
                    $message,
                    $actionUrl,
                    ['task_id' => $task->id, 'project_id' => $task->client_project_id]
                );
            } else {
                $notificationService->notifyInternal(
                    'project_task_due',
                    'Task due (unassigned)',
                    $message,
                    $actionUrl,
                    ['task_id' => $task->id, 'project_id' => $task->client_project_id]
                );
            }

            Cache::put($cacheKey, true, now()->addHours(12));
            $notified++;
        }

        $this->line('Task reminders sent: ' . $notified);
        return self::SUCCESS;
    }
}
