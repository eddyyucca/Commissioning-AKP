<?php $__env->startSection('title', 'Alert'); ?>
<?php $__env->startSection('page-title', 'Alert Unit dan Alat'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Alert</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('topbar-actions'); ?>
    <?php if($canScan): ?>
        <form method="POST" action="<?php echo e(route('cms.alerts.scan')); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-arrow-clockwise"></i><span class="d-none d-sm-inline">Pindai sekarang</span></button></form>
    <?php endif; ?>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .sev-kritis { border-left: 4px solid #dc2626; } .sev-tinggi { border-left: 4px solid #f59e0b; }
    .sev-sedang { border-left: 4px solid #2563eb; } .sev-info { border-left: 4px solid #94a3b8; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3 mb-3">
    <?php $__currentLoopData = \App\Models\Alert::SEVERITY_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-md-3">
            <a href="<?php echo e(route('cms.alerts', ['tingkat' => $key])); ?>" class="text-decoration-none text-reset"><div class="erp-card h-100"><div class="erp-card-body d-flex align-items-center gap-3">
                <div class="kpi-icon bg-<?php echo e(\App\Models\Alert::SEVERITY_BADGES[$key]); ?>-subtle text-<?php echo e(\App\Models\Alert::SEVERITY_BADGES[$key]); ?>"><i class="bi bi-bell"></i></div>
                <div><div class="kpi-label"><?php echo e($label); ?></div><div class="kpi-value fs-5 fw-bold"><?php echo e($counts[$key] ?? 0); ?></div></div>
            </div></div></a>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="<?php echo e(route('cms.alerts')); ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-3"><label class="form-label small mb-1">Cari</label><input type="search" name="q" value="<?php echo e($q); ?>" class="form-control form-control-sm" placeholder="Unit atau uraian"></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <?php $__currentLoopData = ['aktif' => 'Aktif', 'baru' => 'Belum dilihat', 'selesai' => 'Selesai', 'semua' => 'Semua']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($state === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-6 col-md-2"><label class="form-label small mb-1">Tingkat</label>
            <select name="tingkat" class="form-select form-select-sm"><option value="">Semua</option><?php $__currentLoopData = \App\Models\Alert::SEVERITY_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($severity === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-12 col-md-3"><label class="form-label small mb-1">Jenis</label>
            <select name="jenis" class="form-select form-select-sm"><option value="">Semua</option><?php $__currentLoopData = \App\Models\Alert::TYPE_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($type === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="<?php echo e(route('cms.alerts')); ?>" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0"><?php echo e(number_format($items->total())); ?> alert</h2>
        <div class="d-flex gap-3 align-items-center">
            <a href="<?php echo e(route('cms.report.alerts')); ?>" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
            <?php if($canAck): ?><form method="POST" action="<?php echo e(route('cms.alerts.ackall')); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-outline-secondary">Tandai semua sudah dilihat</button></form><?php endif; ?>
        </div>
    </div>
    <div class="list-group list-group-flush">
        <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="list-group-item sev-<?php echo e($a->severity); ?> <?php echo e($a->resolved_at ? 'opacity-50' : ''); ?>">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <span class="badge bg-<?php echo e(\App\Models\Alert::SEVERITY_BADGES[$a->severity]); ?>-subtle text-<?php echo e(\App\Models\Alert::SEVERITY_BADGES[$a->severity]); ?>"><?php echo e(\App\Models\Alert::SEVERITY_LABELS[$a->severity]); ?></span>
                        <span class="badge bg-light text-muted border"><?php echo e($a->typeLabel()); ?></span>
                        <?php if($a->unit): ?><a href="<?php echo e(route('cms.units.show', $a->unit)); ?>" class="fw-semibold text-decoration-none ms-1"><?php echo e($a->unit->code); ?></a><?php endif; ?>
                        <div class="fw-semibold mt-1"><?php echo e($a->title); ?></div>
                        <div class="small text-muted"><?php echo e($a->message); ?></div>
                        <div class="small text-muted">Pertama terlihat <?php echo e($a->first_seen_at->diffForHumans()); ?><?php if($a->due_date): ?> &middot; tenggat <?php echo e($a->due_date->translatedFormat('d M Y')); ?><?php endif; ?>
                            <?php if($a->acknowledged_at): ?> &middot; dilihat <?php echo e($a->acknowledged_by_name); ?> (<?php echo e($a->acknowledged_at->diffForHumans()); ?>)<?php endif; ?>
                            <?php if($a->resolved_at): ?> &middot; <span class="text-success">selesai <?php echo e($a->resolved_at->diffForHumans()); ?></span><?php endif; ?></div>
                    </div>
                    <div class="text-nowrap">
                        <?php if(! $a->resolved_at && ! $a->acknowledged_at && $canAck): ?>
                            <form method="POST" action="<?php echo e(route('cms.alerts.ack', $a)); ?>"><?php echo csrf_field(); ?><button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Sudah dilihat</button></form>
                        <?php elseif(! $a->resolved_at && $a->acknowledged_at): ?><span class="badge bg-success-subtle text-success"><i class="bi bi-eye"></i> Dilihat</span><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center text-muted py-5"><i class="bi bi-bell-slash fs-1 d-block mb-2 text-success"></i>Tidak ada alert pada pilihan ini.</div>
        <?php endif; ?>
    </div>
    <?php if($items->hasPages()): ?><div class="card-footer bg-white"><?php echo e($items->links()); ?></div><?php endif; ?>
</div>
<p class="small text-muted mt-3">Alert dihitung otomatis dari masa berlaku commissioning, dokumen unit, temuan, hold, dan unit idle. Alert hilang sendiri ketika kondisinya sudah beres.</p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/alerts.blade.php ENDPATH**/ ?>