<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Commissioning AKP'); ?> | <?php echo e(config('app.name', 'Commissioning AKP')); ?></title>
    <link rel="icon" href="<?php echo e(asset('assets/images/akp.png')); ?>" type="image/png">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo e(asset('assets/css/app.css')); ?>" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>

    
    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-init');
        }
    </script>
</head>

<body style="background:var(--app-bg);">
<div class="app-shell" id="appShell">

    
    <aside class="sidebar-desktop" id="desktopSidebar">
        <?php echo $__env->make('partials.brand', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </aside>

    
    <div class="offcanvas offcanvas-start sidebar-offcanvas" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header">
            <?php echo $__env->make('partials.brand', ['compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <div class="main-shell">
        <?php echo $__env->make('partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="content-shell">
            <?php echo $__env->make('partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <?php echo $__env->make('partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Collapse sidebar desktop (state tersimpan di localStorage persis seperti HRIS)
    const appShell = document.getElementById('appShell');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    if (localStorage.getItem('sidebarCollapsed') === 'true') appShell.classList.add('sidebar-collapsed');
    document.documentElement.classList.remove('sidebar-collapsed-init');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            appShell.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', appShell.classList.contains('sidebar-collapsed'));
        });
    }

    // Grup menu sidebar yang bisa dilipat
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-sidebar-toggle]');
        if (!toggle) return;
        const content = toggle.nextElementSibling;
        if (!content || !content.classList.contains('sidebar-collapse-content')) return;

        const closing = !content.classList.contains('closed');
        content.classList.toggle('closed', closing);
        toggle.classList.toggle('collapsed', closing);
    });
});
</script>
<script>
// Tombol "Kembali" di header (pola HRIS). Halaman anak punya induk tetap (data-back-explicit); selain itu kembali ke halaman
// sebelumnya bila datang dari aplikasi ini, kalau tidak ke Beranda.
document.addEventListener('click', function (e) {
    const back = e.target.closest('[data-back]');
    if (!back || back.hasAttribute('data-back-explicit') || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) return;
    try {
        const ref = document.referrer ? new URL(document.referrer) : null;
        if (ref && ref.origin === location.origin && ref.href !== location.href && history.length > 1) {
            e.preventDefault();
            history.back();
        }
    } catch (err) { /* href ke Beranda tetap berjalan */ }
});
</script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/layouts/app.blade.php ENDPATH**/ ?>