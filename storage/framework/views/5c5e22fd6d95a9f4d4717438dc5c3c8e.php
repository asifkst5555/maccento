<?php echo e($heading); ?>


<?php if(!empty($intro)): ?>
<?php echo e($intro); ?>


<?php endif; ?>
<?php $__currentLoopData = $bodyLines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php echo e($line); ?>


<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php if(!empty($ctaLabel) && !empty($ctaUrl)): ?>
<?php echo e($ctaLabel); ?>: <?php echo e($ctaUrl); ?>


<?php endif; ?>
<?php echo e($footerNote ?: "Best regards,\nAlessio Battista\nMaccento Real Estate Media"); ?>

<?php /**PATH /home/asifk/projects/maccento/resources/views/emails/branded-notification-text.blade.php ENDPATH**/ ?>