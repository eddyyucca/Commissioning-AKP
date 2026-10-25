<?php ($editing = $unit->exists); ?>
<?php $__env->startSection('title', $editing ? 'Ubah Unit' : 'Tambah Unit'); ?>
<?php $__env->startSection('page-title', $editing ? 'Ubah Unit '.$unit->code : 'Tambah Unit'); ?>
<?php $__env->startSection('back-url', $editing ? route('cms.units.show', $unit) : route('cms.units')); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('cms.units')); ?>">Unit</a></li>
    <li class="breadcrumb-item active"><?php echo e($editing ? 'Ubah' : 'Tambah'); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center"><div class="col-12 col-xl-9">
<div class="card shadow-sm"><div class="card-body p-4">
    <form method="POST" action="<?php echo e($editing ? route('cms.units.update', $unit) : route('cms.units.store')); ?>" class="row g-3">
        <?php echo csrf_field(); ?>
        <?php if($editing): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>

        <div class="col-md-4">
            <label class="form-label">Kode unit <span class="text-danger">*</span></label>
            <input type="text" name="code" value="<?php echo e(old('code', $unit->code)); ?>" class="form-control <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" maxlength="30" <?php echo e($editing ? 'readonly' : 'required'); ?>>
            <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <?php if($editing): ?><div class="form-text">Kode tidak bisa diubah karena tersimpan di riwayat.</div><?php endif; ?>
        </div>
        <div class="col-md-8">
            <label class="form-label">Nama / jenis unit <span class="text-danger">*</span></label>
            <input type="text" name="name" value="<?php echo e(old('name', $unit->name)); ?>" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" maxlength="120" required>
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="col-md-6">
            <label class="form-label">Kategori <span class="text-danger">*</span></label>
            <select name="category" class="form-select <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                <?php $__currentLoopData = config('commissioning.categories'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($code); ?>" <?php if(old('category', $unit->category) === $code): echo 'selected'; endif; ?>><?php echo e($code); ?> &middot; <?php echo e($name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <div class="form-text">Menentukan item checklist yang dipakai.</div>
            <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">Area / lokasi</label>
            <input type="text" name="area" value="<?php echo e(old('area', $unit->area)); ?>" class="form-control" maxlength="80" list="areaList">
            <datalist id="areaList"><?php $__currentLoopData = $areas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($a); ?>"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></datalist>
        </div>

        <div class="col-md-4"><label class="form-label">Merek</label><input type="text" name="brand" value="<?php echo e(old('brand', $unit->brand)); ?>" class="form-control" maxlength="60"></div>
        <div class="col-md-4"><label class="form-label">Model / tipe</label><input type="text" name="model" value="<?php echo e(old('model', $unit->model)); ?>" class="form-control" maxlength="60"></div>
        <div class="col-md-4"><label class="form-label">Tahun pembuatan</label>
            <input type="number" name="year" value="<?php echo e(old('year', $unit->year)); ?>" class="form-control <?php $__errorArgs = ['year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" min="1950" max="<?php echo e(date('Y') + 1); ?>">
            <?php $__errorArgs = ['year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>

        <div class="col-md-4"><label class="form-label">Nomor serial / rangka</label><input type="text" name="serial_number" value="<?php echo e(old('serial_number', $unit->serial_number)); ?>" class="form-control" maxlength="60"></div>
        <div class="col-md-4"><label class="form-label">Plat nomor</label><input type="text" name="plate" value="<?php echo e(old('plate', $unit->plate)); ?>" class="form-control" maxlength="30"></div>
        <div class="col-md-4"><label class="form-label">Pemilik</label><input type="text" name="owner" value="<?php echo e(old('owner', $unit->owner)); ?>" class="form-control" maxlength="80"><div class="form-text">AKP atau nama kontraktor.</div></div>

        <div class="col-12"><label class="form-label">Catatan</label><input type="text" name="notes" value="<?php echo e(old('notes', $unit->notes)); ?>" class="form-control" maxlength="255"></div>

        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Simpan</button>
            <a href="<?php echo e($editing ? route('cms.units.show', $unit) : route('cms.units')); ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div></div>
<?php if($editing): ?>
    <form method="POST" action="<?php echo e(route('cms.units.toggle', $unit)); ?>" class="mt-3" onsubmit="return confirm('<?php echo e($unit->is_active ? 'Nonaktifkan unit ini? Unit nonaktif tidak muncul di dashboard dan alert.' : 'Aktifkan kembali unit ini?'); ?>')">
        <?php echo csrf_field(); ?>
        <button class="btn btn-sm btn-outline-<?php echo e($unit->is_active ? 'danger' : 'success'); ?>"><?php echo e($unit->is_active ? 'Nonaktifkan unit' : 'Aktifkan unit'); ?></button>
    </form>
<?php endif; ?>
</div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/units/form.blade.php ENDPATH**/ ?>