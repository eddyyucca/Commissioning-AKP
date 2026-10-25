<?php $__env->startSection('title', 'Temuan'); ?>
<?php $__env->startSection('page-title', 'Temuan'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Temuan</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="<?php echo e(route('cms.findings')); ?>" class="row g-2 align-items-end">
        <div class="col-12 col-md-4"><label class="form-label small mb-1">Cari</label><input type="search" name="q" value="<?php echo e($q); ?>" class="form-control form-control-sm" placeholder="Temuan, unit, PIC"></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="aktif" <?php if($status === 'aktif'): echo 'selected'; endif; ?>>Belum selesai</option><option value="all" <?php if($status === 'all'): echo 'selected'; endif; ?>>Semua</option>
                <?php $__currentLoopData = \App\Models\Finding::STATUS_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($status === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Tingkat</label>
            <select name="tingkat" class="form-select form-select-sm"><option value="">Semua</option><?php $__currentLoopData = \App\Models\Finding::SEVERITY_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($severity === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="<?php echo e(route('cms.findings')); ?>" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><?php echo e(number_format($items->total())); ?> temuan</h2>
        <a href="<?php echo e(route('cms.report.findings')); ?>" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="list-group list-group-flush">
        <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="list-group-item">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <span class="badge bg-<?php echo e(\App\Models\Finding::SEVERITY_BADGES[$f->severity]); ?>-subtle text-<?php echo e(\App\Models\Finding::SEVERITY_BADGES[$f->severity]); ?>"><?php echo e(\App\Models\Finding::SEVERITY_LABELS[$f->severity]); ?></span>
                        <a href="<?php echo e(route('cms.units.show', $f->unit)); ?>" class="fw-semibold text-decoration-none ms-1"><?php echo e($f->unit->code); ?></a>
                        <span class="ms-1"><?php echo e($f->title); ?></span>
                        <div class="small text-muted">
                            <a href="<?php echo e(route('cms.show', $f->commissioning->number)); ?>" class="text-decoration-none"><?php echo e($f->commissioning->number); ?></a>
                            <?php if($f->description): ?> &middot; <?php echo e($f->description); ?><?php endif; ?>
                        </div>
                        <div class="small text-muted"><?php echo e($f->pic_name ? 'PIC '.$f->pic_name.' · ' : ''); ?><?php echo e($f->due_date ? 'tenggat '.$f->due_date->translatedFormat('d M Y') : 'belum ada tenggat'); ?><?php if($f->isOverdue()): ?> <span class="text-danger">(terlambat)</span><?php endif; ?> <?php if($f->action_plan): ?> &middot; <?php echo e($f->action_plan); ?><?php endif; ?></div>
                        <?php if($f->status === 'closed'): ?><div class="small text-success"><i class="bi bi-check2-circle"></i> <?php echo e($f->closed_by_name); ?> &middot; <?php echo e($f->closed_at?->translatedFormat('d M Y')); ?>: <?php echo e($f->closed_note); ?></div><?php endif; ?>
                    </div>
                    <div><span class="badge <?php echo e($f->status === 'closed' ? 'bg-success-subtle text-success' : ($f->status === 'in_progress' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning')); ?>"><?php echo e(\App\Models\Finding::STATUS_LABELS[$f->status]); ?></span></div>
                </div>
                <?php if($f->isOpen() && $canUpdate): ?>
                    <form method="POST" action="<?php echo e(route('cms.findings.update', $f)); ?>" class="row g-2 mt-1 align-items-end">
                        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                        <div class="col-md-3"><input type="text" name="pic_name" value="<?php echo e($f->pic_name); ?>" class="form-control form-control-sm" placeholder="PIC perbaikan" maxlength="120"></div>
                        <div class="col-md-2"><input type="date" name="due_date" value="<?php echo e($f->due_date?->toDateString()); ?>" class="form-control form-control-sm" title="Tenggat"></div>
                        <div class="col-md-4"><input type="text" name="action_plan" value="<?php echo e($f->action_plan); ?>" class="form-control form-control-sm" placeholder="Rencana tindakan" maxlength="500"></div>
                        <div class="col-md-3 d-flex gap-1">
                            <?php if($canSeverity): ?><select name="severity" class="form-select form-select-sm"><?php $__currentLoopData = \App\Models\Finding::SEVERITY_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($f->severity === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><?php endif; ?>
                            <button class="btn btn-sm btn-outline-primary">Simpan</button>
                        </div>
                    </form>
                    <?php if($canClose): ?>
                        <form method="POST" action="<?php echo e(route('cms.findings.close', $f)); ?>" class="row g-2 mt-1">
                            <?php echo csrf_field(); ?>
                            <div class="col-md-9"><input type="text" name="note" class="form-control form-control-sm" placeholder="Bukti perbaikan / catatan penutupan" maxlength="500" required></div>
                            <div class="col-md-3"><button class="btn btn-sm btn-success w-100"><i class="bi bi-check2 me-1"></i>Tutup temuan</button></div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center text-muted py-5"><i class="bi bi-emoji-smile fs-1 d-block mb-2"></i>Tidak ada temuan yang cocok.</div>
        <?php endif; ?>
    </div>
    <?php if($items->hasPages()): ?><div class="card-footer bg-white"><?php echo e($items->links()); ?></div><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/findings.blade.php ENDPATH**/ ?>