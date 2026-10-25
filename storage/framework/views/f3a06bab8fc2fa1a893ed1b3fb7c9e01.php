<?php $__env->startSection('title', 'Mulai Commissioning'); ?>
<?php $__env->startSection('page-title', 'Mulai Commissioning'); ?>
<?php $__env->startSection('back-url', route('cms.index')); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('cms.index')); ?>">Daftar proses</a></li>
    <li class="breadcrumb-item active">Baru</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center"><div class="col-12 col-xl-8">
<div class="card shadow-sm"><div class="card-body p-4">
    <p class="small text-muted">
        Pilih unit. Sistem menentukan jenis proses: unit yang belum pernah dinyatakan laik memakai <strong>commissioning</strong>;
        unit yang sudah pernah laik memakai <strong>recommissioning</strong> dengan alasan tertentu, dan checklist menyesuaikan kategori serta alasannya.
    </p>
    <form method="POST" action="<?php echo e(route('cms.store')); ?>" class="row g-3" id="startForm">
        <?php echo csrf_field(); ?>
        <div class="col-md-6">
            <label class="form-label">Unit <span class="text-danger">*</span></label>
            <input type="text" name="unit" value="<?php echo e(old('unit', $unit?->code)); ?>" class="form-control <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" list="unitList" autocomplete="off" required placeholder="Ketik kode unit" id="unitInput">
            <datalist id="unitList">
                <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($u->code); ?>"><?php echo e($u->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </datalist>
            <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <div class="form-text" id="unitHint">
                <?php if($unit): ?>
                    <?php echo e($unit->name); ?> &middot; <?php echo e($unit->statusLabel()); ?>

                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6" id="reasonBox">
            <label class="form-label">Alasan recommissioning</label>
            <select name="reason" class="form-select <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="reasonSelect">
                <option value="">Pilih alasan (untuk unit yang pernah commissioning)</option>
                <?php $__currentLoopData = config('commissioning.reasons'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>" <?php if(old('reason', $suggestedReason) === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <div class="form-text">Wajib untuk recommissioning. Diabaikan untuk commissioning pertama.</div>
        </div>
        <div class="col-12"><label class="form-label">Keterangan alasan</label><input type="text" name="reason_note" value="<?php echo e(old('reason_note')); ?>" class="form-control" maxlength="255" placeholder="Contoh: setelah penggantian transmisi di workshop"></div>

        <div class="col-md-4"><label class="form-label">Rencana tanggal</label><input type="date" name="planned_date" value="<?php echo e(old('planned_date', today()->toDateString())); ?>" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Area / lokasi</label><input type="text" name="area" value="<?php echo e(old('area', $unit?->area)); ?>" class="form-control" maxlength="80"></div>
        <div class="col-md-4"><label class="form-label">Pembacaan meter</label>
            <div class="input-group"><input type="number" name="meter_reading" value="<?php echo e(old('meter_reading')); ?>" class="form-control" min="0">
                <select name="meter_unit" class="form-select" style="max-width:90px"><option value="jam" <?php if(old('meter_unit') === 'jam'): echo 'selected'; endif; ?>>jam</option><option value="km" <?php if(old('meter_unit') === 'km'): echo 'selected'; endif; ?>>km</option></select></div>
            <div class="form-text">Hour meter atau odometer saat diperiksa.</div></div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right-circle me-1"></i>Buat dan isi checklist</button>
            <a href="<?php echo e(route('cms.index')); ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div></div>
</div></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php
    $unitMeta = $units->mapWithKeys(function ($u) {
        return [$u->code => ['name' => $u->name, 'again' => (bool) $u->last_commissioning_id, 'hold' => (bool) $u->hold_since, 'busy' => (bool) $u->has_process]];
    });
?>
<script>
(function () {
    const units = <?php echo json_encode($unitMeta, 15, 512) ?>;
    const input = document.getElementById('unitInput');
    const hint = document.getElementById('unitHint');
    const box = document.getElementById('reasonBox');
    function sync() {
        const u = units[(input.value || '').trim().toUpperCase()];
        box.style.opacity = u && !u.again ? .5 : 1;
        if (!u) { hint.textContent = ''; return; }
        hint.textContent = u.name + ' · ' + (u.busy ? 'sudah ada proses berjalan' : (u.again ? 'recommissioning' : 'commissioning pertama')) + (u.hold ? ' · DITAHAN (pilih alasan insiden/perbaikan)' : '');
    }
    input.addEventListener('input', sync);
    sync();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/create.blade.php ENDPATH**/ ?>