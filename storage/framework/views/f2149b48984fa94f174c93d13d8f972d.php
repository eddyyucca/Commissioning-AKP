
<?php
    $u = portal_user();
    $canStart = (bool) $u?->hasRole('admin', 'inspector', 'safety', 'workshop');
    $isAdmin = (bool) $u?->hasRole('admin');

    $item = fn (string $title, string $icon, string $route, array $match) => [
        'title' => $title, 'icon' => $icon, 'href' => route($route), 'active' => request()->routeIs(...$match),
    ];

    $openFindings = \App\Models\Finding::query()->where('status', '!=', 'closed')->count();
    $newAlerts = \App\Models\Alert::query()->active()->whereNull('acknowledged_at')->whereIn('severity', ['kritis', 'tinggi'])->count();

    $menus = [
        $item('Dashboard', 'bi bi-grid-1x2-fill', 'cms.home', ['cms.home']),
        [
            'title' => 'Commissioning',
            'children' => array_values(array_filter([
                $canStart ? $item('Mulai Commissioning', 'bi bi-plus-circle', 'cms.create', ['cms.create']) : null,
                $item('Daftar Proses', 'bi bi-journal-check', 'cms.index', ['cms.index', 'cms.show', 'cms.certificate']),
                $item('Temuan', 'bi bi-exclamation-diamond', 'cms.findings', ['cms.findings*']) + ['badge' => $openFindings],
                $item('Insiden', 'bi bi-cone-striped', 'cms.incidents', ['cms.incidents*']),
            ])),
        ],
        [
            'title' => 'Pemantauan',
            'children' => [
                $item('Alert', 'bi bi-bell', 'cms.alerts', ['cms.alerts*']) + ['badge' => $newAlerts],
                $item('Laporan', 'bi bi-file-earmark-bar-graph', 'cms.report', ['cms.report*']),
            ],
        ],
        [
            'title' => 'Data Master',
            'children' => [
                $item('Unit', 'bi bi-truck-front', 'cms.units', ['cms.units*']),
                $item('Checklist', 'bi bi-ui-checks', 'cms.checklist', ['cms.checklist*']),
            ],
        ],
    ];

    foreach ($menus as &$menu) {
        if (isset($menu['children'])) {
            $menu['active'] = collect($menu['children'])->contains('active', true);
        }
    }
    unset($menu);
?>

<div class="sidebar-section">
    <?php $__currentLoopData = $menus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(isset($menu['children'])): ?>
            <div class="sidebar-group mt-2">
                <div class="sidebar-group-toggle <?php echo e($menu['active'] ? 'has-active' : 'collapsed'); ?>" data-sidebar-toggle>
                    <span><?php echo e($menu['title']); ?></span>
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </div>
                <div class="sidebar-collapse-content <?php echo e($menu['active'] ? '' : 'closed'); ?>">
                    <div class="sidebar-collapse-inner">
                        <?php $__currentLoopData = $menu['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('partials.sidebar-link', ['item' => $child], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php echo $__env->make('partials.sidebar-link', ['item' => $menu], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>