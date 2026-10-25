<?php $__env->startSection('title', 'Unit'); ?>
<?php $__env->startSection('page-title', 'Unit dan Alat'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Unit</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('topbar-actions'); ?>
    <?php if($canEdit): ?>
        <a href="<?php echo e(route('cms.units.import')); ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-upload"></i><span class="d-none d-sm-inline">Impor CSV</span></a>
        <a href="<?php echo e(route('cms.units.create')); ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Tambah unit</span></a>
    <?php endif; ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="<?php echo e(route('cms.units')); ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-4"><label class="form-label small mb-1">Cari</label>
            <input type="search" name="q" value="<?php echo e($q); ?>" class="form-control form-control-sm" placeholder="Kode, nama, plat, serial"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Kategori</label>
            <select name="kategori" class="form-select form-select-sm"><option value="">Semua</option>
                <?php $__currentLoopData = config('commissioning.categories'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($code); ?>" <?php if($category === $code): echo 'selected'; endif; ?>><?php echo e($code); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Area</label>
            <select name="area" class="form-select form-select-sm"><option value="">Semua</option>
                <?php $__currentLoopData = $areas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($a); ?>" <?php if($area === $a): echo 'selected'; endif; ?>><?php echo e($a); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">Semua</option>
                <?php $__currentLoopData = \App\Models\Unit::STATUS_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if($status === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-6 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="<?php echo e(route('cms.units')); ?>" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><?php echo e(number_format($units->total())); ?> unit</h2>
        <a href="<?php echo e(route('cms.report.units')); ?>" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Area</th><th>Status</th><th>Berlaku sampai</th><th></th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($left = $u->daysLeft()); ?>
                    <tr class="<?php echo e($u->is_active ? '' : 'text-muted'); ?>">
                        <td class="fw-semibold"><a href="<?php echo e(route('cms.units.show', $u)); ?>" class="text-decoration-none"><?php echo e($u->code); ?></a><?php if (! ($u->is_active)): ?><span class="badge bg-secondary-subtle text-secondary ms-1">nonaktif</span><?php endif; ?></td>
                        <td><?php echo e($u->name); ?><div class="small text-muted"><?php echo e(collect([$u->brand, $u->model])->filter()->implode(' ')); ?></div></td>
                        <td class="small"><?php echo e($u->category); ?></td>
                        <td class="small"><?php echo e($u->area ?: '-'); ?></td>
                        <td><span class="badge bg-<?php echo e(\App\Models\Unit::STATUS_BADGES[$u->ops]); ?>-subtle text-<?php echo e(\App\Models\Unit::STATUS_BADGES[$u->ops] === 'dark' ? 'dark' : \App\Models\Unit::STATUS_BADGES[$u->ops]); ?>"><?php echo e(\App\Models\Unit::STATUS_LABELS[$u->ops]); ?></span>
                            <?php if($u->hold_reason): ?><div class="small text-danger text-truncate" style="max-width:240px" title="<?php echo e($u->hold_reason); ?>"><?php echo e($u->hold_reason); ?></div><?php endif; ?></td>
                        <td class="small text-nowrap">
                            <?php if($u->commissioned_until): ?><?php echo e($u->commissioned_until->translatedFormat('d M Y')); ?><div class="<?php echo e($left < 0 ? 'text-danger' : ($left <= config('commissioning.expiring_days') ? 'text-warning' : 'text-muted')); ?>"><?php echo e($left < 0 ? 'lewat '.abs($left).' hari' : $left.' hari lagi'); ?></div>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td class="text-end"><a href="<?php echo e(route('cms.units.show', $u)); ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-truck-front fs-1 d-block mb-2"></i>Tidak ada unit yang cocok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($units->hasPages()): ?><div class="card-footer bg-white"><?php echo e($units->links()); ?></div><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/units/index.blade.php ENDPATH**/ ?>