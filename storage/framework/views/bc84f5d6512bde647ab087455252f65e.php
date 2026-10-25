<?php if(!empty($compact)): ?>
    <div class="brand-pill">
        <img src="<?php echo e(asset('assets/images/akp.png')); ?>" height="30" alt="<?php echo e(config('app.name')); ?>">
    </div>
<?php else: ?>
    <div class="sidebar-brand d-flex align-items-center justify-content-center">
        <img src="<?php echo e(asset('assets/images/akp.png')); ?>" alt="<?php echo e(config('app.name')); ?>" class="sidebar-logo">
    </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/partials/brand.blade.php ENDPATH**/ ?>