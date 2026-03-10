<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Lead Conversation Export</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #10223e; }
    .head { margin-bottom: 18px; }
    .title { margin: 0 0 6px; font-size: 20px; color: #0f2748; }
    .meta { margin: 0; color: #4a617f; line-height: 1.5; }
    .msg { border: 1px solid #d9e2ef; border-left-width: 4px; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
    .msg.user { border-left-color: #0f294a; }
    .msg.assistant { border-left-color: #c81e2f; }
    .role { margin: 0 0 4px; font-weight: 700; font-size: 11px; color: #4a617f; text-transform: uppercase; letter-spacing: .04em; }
    .text { margin: 0; line-height: 1.5; white-space: pre-wrap; }
    .time { margin-top: 6px; font-size: 10px; color: #6a7f9f; }
    .empty { color: #6a7f9f; font-style: italic; }
  </style>
</head>
<body>
  <div class="head">
    <h1 class="title">Conversation Transcript</h1>
    <p class="meta"><strong>Lead:</strong> <?php echo e($lead->name ?: 'Unnamed lead'); ?> (ID #<?php echo e($lead->id); ?>)</p>
    <p class="meta"><strong>Contact:</strong> <?php echo e($lead->email ?: ($lead->phone ?: 'No contact info')); ?></p>
    <p class="meta"><strong>Exported:</strong> <?php echo e(now()->format('Y-m-d H:i:s')); ?></p>
  </div>

  <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
      $role = $message->role === 'user' ? 'user' : 'assistant';
      $roleLabel = strtoupper($message->role ?: 'assistant');
    ?>
    <div class="msg <?php echo e($role); ?>">
      <p class="role"><?php echo e($roleLabel); ?></p>
      <p class="text"><?php echo e($message->content); ?></p>
      <p class="time"><?php echo e(optional($message->created_at)->format('Y-m-d H:i:s') ?: '-'); ?></p>
    </div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p class="empty">No messages available for this lead.</p>
  <?php endif; ?>
</body>
</html>
<?php /**PATH /home/asifk/projects/maccento/resources/views/admin/pdf/lead-conversation.blade.php ENDPATH**/ ?>