<?php $__env->startSection('content'); ?>
<section class="panel-card">
  <h2 class="panel-section-title">Create Account</h2>
  <form method="post" action="<?php echo e(route('admin.users.store')); ?>" class="panel-form-row">
    <?php echo csrf_field(); ?>
    <input class="panel-input" type="text" name="name" placeholder="Full name" required>
    <input class="panel-input" type="email" name="email" placeholder="Email (login)" required>
    <input class="panel-input" type="text" name="phone" placeholder="Phone">
    <select class="panel-select" name="role" required>
      <option value="">Select role</option>
      <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($role); ?>"><?php echo e(ucfirst($role)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <input class="panel-input" type="text" name="password" placeholder="Password (optional, auto-generated if empty)">
    <input class="panel-input" type="text" name="company" placeholder="Company (client)">
    <textarea class="panel-textarea" name="notes" placeholder="Notes (optional)"></textarea>
    <button class="panel-btn panel-btn-primary" type="submit">Create Account</button>
  </form>
</section>

<section class="panel-card">
  <h2 class="panel-section-title">All Accounts</h2>
  <form method="get" class="panel-form-row">
    <input class="panel-input" type="text" name="search" value="<?php echo e($filters['search']); ?>" placeholder="Search name/email/phone">
    <select class="panel-select" name="role">
      <option value="">All roles</option>
      <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($role); ?>" <?php if($filters['role'] === $role): echo 'selected'; endif; ?>><?php echo e(ucfirst($role)); ?></option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <button class="panel-btn panel-btn-primary" type="submit">Filter</button>
    <a class="panel-link" href="<?php echo e(route('admin.users.index')); ?>">Clear</a>
  </form>

  <div class="panel-table-wrap">
    <table class="panel-table">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Created</th><th>Action</th></tr></thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
          <td>#<?php echo e($user->id); ?></td>
          <td><?php echo e($user->name); ?></td>
          <td><?php echo e($user->email); ?></td>
          <td><?php echo e($user->phone ?: '-'); ?></td>
          <td><span class="panel-badge"><?php echo e($user->role); ?></span></td>
          <td><?php echo e($user->created_at?->format('Y-m-d H:i') ?: '-'); ?></td>
          <td>
            <?php if((int) auth()->id() !== (int) $user->id): ?>
            <form method="post" action="<?php echo e(route('admin.users.delete', $user)); ?>" data-confirm="Delete this user account?">
              <?php echo csrf_field(); ?>
              <button class="panel-btn panel-btn-danger panel-btn-icon" type="submit" title="Delete user account" aria-label="Delete user account"><span class="panel-icon-trash" aria-hidden="true"><?php if (isset($component)) { $__componentOriginal669c17867d57615948fae15a035429b3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal669c17867d57615948fae15a035429b3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-icon','data' => ['name' => 'trash']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $attributes = $__attributesOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__attributesOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal669c17867d57615948fae15a035429b3)): ?>
<?php $component = $__componentOriginal669c17867d57615948fae15a035429b3; ?>
<?php unset($__componentOriginal669c17867d57615948fae15a035429b3); ?>
<?php endif; ?></span></button>
            </form>
            <?php else: ?>
            <span class="panel-badge">Current user</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="7" class="panel-muted">No user accounts found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (isset($component)) { $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.panel-pagination','data' => ['paginator' => $users]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('panel-pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($users)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92)): ?>
<?php $attributes = $__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92; ?>
<?php unset($__attributesOriginal9f9d3eae18f18ccf28f34f84596c1d92); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92)): ?>
<?php $component = $__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92; ?>
<?php unset($__componentOriginal9f9d3eae18f18ccf28f34f84596c1d92); ?>
<?php endif; ?>
</section>
<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.panel', [
  'title' => 'User Accounts',
  'heading' => 'User Accounts',
  'subheading' => 'Create internal team and client login accounts by role.',
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/asifk/projects/maccento/resources/views/admin/users-index.blade.php ENDPATH**/ ?>