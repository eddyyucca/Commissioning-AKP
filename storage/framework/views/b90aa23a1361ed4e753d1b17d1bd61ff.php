<header class="topbar">
    <div class="container-fluid px-3 px-lg-4">
        <div class="topbar-inner d-flex align-items-center justify-content-between gap-2">

            <div class="d-flex align-items-center gap-2 gap-lg-3 min-w-0">
                <button class="topbar-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <button class="topbar-btn d-none d-lg-flex" id="sidebarToggleBtn" title="Toggle Sidebar">
                    <i class="bi bi-layout-sidebar fs-5"></i>
                </button>

                <?php if (! (request()->routeIs('cms.home'))): ?>
                    <?php ($explicitBack = trim($__env->yieldContent('back-url'))); ?>
                    <a href="<?php echo e($explicitBack ?: route('cms.home')); ?>" class="topbar-btn" data-back <?php if($explicitBack): ?> data-back-explicit <?php endif; ?> title="Kembali" aria-label="Kembali">
                        <i class="bi bi-arrow-left fs-5"></i>
                    </a>
                <?php endif; ?>

                <div class="min-w-0">
                    <nav class="d-none d-lg-block" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" style="font-size:.78rem;">
                            <li class="breadcrumb-item"><a href="<?php echo e(route('cms.home')); ?>">Beranda</a></li>
                            <?php echo $__env->yieldContent('breadcrumb'); ?>
                        </ol>
                    </nav>
                    <h1 class="page-title mb-0 text-truncate">
                        <?php echo $__env->yieldContent('page-title', 'Commissioning AKP'); ?>
                    </h1>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <?php echo $__env->yieldPushContent('topbar-actions'); ?>

                <div class="dropdown">
                    <div class="user-pill" role="button" data-bs-toggle="dropdown" style="cursor:pointer;">
                        <div class="user-avatar"><?php echo e(\Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($portalUser->name ?? 'U', 0, 2))); ?></div>
                        <div class="d-none d-lg-block">
                            <div class="user-name"><?php echo e($portalUser->name ?? '-'); ?></div>
                            <div class="user-role"><?php echo e(config('sso.roles.'.($portalUser->role ?? '').'.0', 'Pengguna')); ?></div>
                        </div>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end" style="border-radius:14px;min-width:200px;">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold" style="font-size:.88rem;"><?php echo e($portalUser->name ?? '-'); ?></div>
                            <div class="text-muted" style="font-size:.76rem;">NIK <?php echo e($portalUser->employee_no ?? '-'); ?></div>
                        </li>
                        <li><a class="dropdown-item" href="<?php echo e(config('sso.portal_url')); ?>"><i class="bi bi-grid-3x3-gap me-2"></i>Portal AKPSatu</a></li>
                        <li>
                            <form method="POST" action="<?php echo e(route('portal.logout')); ?>"><?php echo csrf_field(); ?>
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</header>
<?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/partials/header.blade.php ENDPATH**/ ?>