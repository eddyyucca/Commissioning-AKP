<?php $__env->startSection('title', 'Daftar Proses'); ?>
<?php $__env->startSection('page-title', 'Daftar Commissioning'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Daftar proses</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('topbar-actions'); ?>
    <?php if(portal_user()->hasRole('admin', 'inspector', 'safety', 'workshop')): ?>
        <a href="<?php echo e(route('cms.create')); ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-plus-circle"></i><span class="d-none d-sm-inline">Mulai commissioning</span></a>
    <?php endif; ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="<?php echo e(route('cms.index')); ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-4"><label class="form-label small mb-1">Cari</label>
            <input type="search" name="q" value="<?php echo e($q); ?>" class="form-control form-control-sm" placeholder="No., unit, pemohon"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">Semua</option>
                <option value="aktif" <?php if($status === 'aktif'): echo 'selected'; endif; ?>>Sedang berjalan</option>
                <?php $__currentLoopData = \App\Models\Commissioning::STATUS_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if($status === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Jenis</label>
            <select name="jenis" class="form-select form-select-sm"><option value="">Semua</option>
                <?php $__currentLoopData = \App\Models\Commissioning::KIND_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if($kind === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="<?php echo e(route('cms.index')); ?>" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><?php echo e(number_format($items->total())); ?> proses</h2>
        <a href="<?php echo e(route('cms.report.commissionings')); ?>" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>No.</th><th>Unit</th><th>Jenis</th><th>Status</th><th>Tahap</th><th>Pemohon</th><th>Dibuat</th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($b = \App\Models\Commissioning::STATUS_BADGES[$c->status]); ?>
                    <tr>
                        <td><a href="<?php echo e(route('cms.show', $c)); ?>" class="fw-semibold text-decoration-none small"><?php echo e($c->number); ?></a></td>
                        <td><a href="<?php echo e(route('cms.units.show', $c->unit_code)); ?>" class="text-decoration-none fw-semibold"><?php echo e($c->unit_code); ?></a><div class="small text-muted"><?php echo e($c->unit_name); ?></div></td>
                        <td class="small"><?php echo e($c->kindLabel()); ?><?php if($c->reason): ?><div class="text-muted"><?php echo e($c->reasonLabel()); ?></div><?php endif; ?></td>
                        <td><span class="badge bg-<?php echo e($b); ?>-subtle text-<?php echo e($b === 'dark' ? 'dark' : $b); ?>"><?php echo e($c->statusLabel()); ?></span><?php if($c->result): ?><div class="small text-muted"><?php echo e(\App\Models\Commissioning::RESULT_LABELS[$c->result]); ?></div><?php endif; ?></td>
                        <td class="small"><?php echo e($c->stage()); ?></td>
                        <td class="small"><?php echo e($c->requested_by_name); ?></td>
                        <td class="small text-nowrap"><?php echo e($c->created_at->translatedFormat('d M Y')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-journal-check fs-1 d-block mb-2"></i>Belum ada proses yang cocok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($items->hasPages()): ?><div class="card-footer bg-white"><?php echo e($items->links()); ?></div><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/index.blade.php ENDPATH**/ ?>