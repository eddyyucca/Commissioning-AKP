<?php $__env->startSection('title', 'Insiden'); ?>
<?php $__env->startSection('page-title', 'Insiden dan Kejadian Unit'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Insiden</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php if($canReport): ?>
<div class="card shadow-sm mb-4" id="form">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-cone-striped me-2 text-danger"></i>Lapor kejadian pada unit</h2></div>
    <div class="card-body">
        <p class="small text-muted mb-3">Kecelakaan, kerusakan besar, overhaul, dan modifikasi <strong>otomatis menahan unit (hold)</strong> sampai recommissioning disetujui. Near miss, pindah area, dan lainnya hanya dicatat.</p>
        <form method="POST" action="<?php echo e(route('cms.incidents.store')); ?>" class="row g-3">
            <?php echo csrf_field(); ?>
            <div class="col-md-3"><label class="form-label small mb-1">Unit <span class="text-danger">*</span></label>
                <input type="text" name="unit" value="<?php echo e(old('unit', $preset)); ?>" list="incUnits" class="form-control form-control-sm <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required autocomplete="off">
                <datalist id="incUnits"><?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($u->code); ?>"><?php echo e($u->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></datalist>
                <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <div class="col-md-3"><label class="form-label small mb-1">Jenis <span class="text-danger">*</span></label>
                <select name="kind" class="form-select form-select-sm" required><?php $__currentLoopData = config('commissioning.incident_kinds'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label, $hold]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if(old('kind') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?><?php echo e($hold ? ' (hold otomatis)' : ''); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="col-md-2"><label class="form-label small mb-1">Tingkat</label>
                <select name="severity" class="form-select form-select-sm"><?php $__currentLoopData = config('commissioning.incident_severities'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if(old('severity') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="col-md-4"><label class="form-label small mb-1">Waktu kejadian <span class="text-danger">*</span></label>
                <input type="datetime-local" name="occurred_at" value="<?php echo e(old('occurred_at', now()->format('Y-m-d\TH:i'))); ?>" max="<?php echo e(now()->format('Y-m-d\TH:i')); ?>" class="form-control form-control-sm <?php $__errorArgs = ['occurred_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                <?php $__errorArgs = ['occurred_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <div class="col-12"><label class="form-label small mb-1">Uraian <span class="text-danger">*</span></label>
                <textarea name="description" rows="2" class="form-control form-control-sm <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" maxlength="1000" required><?php echo e(old('description')); ?></textarea>
                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="requires_recommissioning" value="1" id="reqRe" <?php if(old('requires_recommissioning')): echo 'checked'; endif; ?>><label class="form-check-label small" for="reqRe">Tahan unit (wajib recommissioning) walau jenisnya tidak otomatis hold</label></div>
                <button class="btn btn-danger btn-sm"><i class="bi bi-send me-1"></i>Catat kejadian</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="<?php echo e(route('cms.incidents')); ?>" class="row g-2 align-items-end">
        <div class="col-6 col-md-4"><label class="form-label small mb-1">Jenis</label>
            <select name="jenis" class="form-select form-select-sm"><option value="">Semua</option><?php $__currentLoopData = config('commissioning.incident_kinds'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if($kind === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm"><option value="">Semua</option><option value="open" <?php if($status === 'open'): echo 'selected'; endif; ?>>Menunggu recommissioning</option><option value="resolved" <?php if($status === 'resolved'): echo 'selected'; endif; ?>>Selesai</option></select></div>
        <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="<?php echo e(route('cms.incidents')); ?>" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><?php echo e(number_format($items->total())); ?> kejadian</h2>
        <a href="<?php echo e(route('cms.report.incidents')); ?>" class="small text-decoration-none"><i class="bi bi-download me-1"></i>Unduh CSV</a>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Waktu</th><th>Unit</th><th>Jenis</th><th>Tingkat</th><th>Uraian</th><th>Status</th><th>Pelapor</th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="small text-nowrap"><?php echo e($i->occurred_at->translatedFormat('d M Y H:i')); ?></td>
                        <td><a href="<?php echo e(route('cms.units.show', $i->unit)); ?>" class="fw-semibold text-decoration-none"><?php echo e($i->unit->code); ?></a></td>
                        <td class="small"><?php echo e($i->kindLabel()); ?></td>
                        <td class="small"><?php echo e($i->severityLabel()); ?></td>
                        <td class="small"><?php echo e($i->description); ?></td>
                        <td class="small"><?php if($i->requires_recommissioning): ?><span class="badge <?php echo e($i->status === 'open' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'); ?>"><?php echo e($i->status === 'open' ? 'Hold: wajib recommissioning' : 'Sudah recommissioning'); ?></span><?php else: ?><span class="text-muted">Dicatat</span><?php endif; ?></td>
                        <td class="small"><?php echo e($i->reported_by_name); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>Tidak ada kejadian tercatat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($items->hasPages()): ?><div class="card-footer bg-white"><?php echo e($items->links()); ?></div><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/incidents.blade.php ENDPATH**/ ?>