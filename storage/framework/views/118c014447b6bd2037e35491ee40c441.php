<a href="<?php echo e($item['href']); ?>" class="sidebar-link <?php echo e($item['active'] ? 'active' : ''); ?>">
    <span class="sidebar-link-icon"><i class="<?php echo e($item['icon'] ?: 'bi bi-circle'); ?>"></i></span>
    <span class="sidebar-link-text"><?php echo e($item['title']); ?></span>
    <?php if(! empty($item['badge'])): ?><span class="badge rounded-pill bg-warning text-dark ms-auto"><?php echo e($item['badge']); ?></span><?php endif; ?>
</a>
<?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/partials/sidebar-link.blade.php ENDPATH**/ ?>