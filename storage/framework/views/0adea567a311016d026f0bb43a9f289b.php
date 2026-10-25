<?php $__env->startSection('title', 'Impor Unit'); ?>
<?php $__env->startSection('page-title', 'Impor Unit'); ?>
<?php $__env->startSection('back-url', route('cms.units')); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('cms.units')); ?>">Unit</a></li>
    <li class="breadcrumb-item active">Impor</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php ($result = session('import_result')); ?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <?php if($result): ?>
            <div class="alert alert-<?php echo e($result['errors'] ? 'warning' : 'success'); ?>">
                <div class="fw-semibold"><?php echo e($result['created']); ?> unit ditambahkan.</div>
                <?php if($result['skipped']): ?><div class="small">Dilewati karena kode sudah ada: <?php echo e(implode(', ', $result['skipped'])); ?>.</div><?php endif; ?>
                <?php if($result['errors']): ?><ul class="small mb-0 mt-2 ps-3"><?php $__currentLoopData = $result['errors']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm"><div class="card-body p-4">
            <p class="small text-muted mb-3">
                Berkas CSV dengan kolom <strong>kode, nama, kategori, area, merek, model, serial, tahun, plat, pemilik</strong>
                (pemisah koma atau titik koma; baris pertama boleh judul). Yang wajib hanya kode dan nama; kategori kosong dianggap LV.
                Kategori: <strong><?php echo e(implode(', ', array_keys(config('commissioning.categories')))); ?></strong>. Maksimum 500 baris.
                <a href="<?php echo e(route('cms.units.import.template')); ?>">Unduh contoh</a>.
            </p>
            <form method="POST" action="<?php echo e(route('cms.units.import.store')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="file" name="file" accept=".csv,text/csv,text/plain" class="form-control mb-3 <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block mb-2"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Impor</button>
                    <a href="<?php echo e(route('cms.units')); ?>" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div></div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/units/import.blade.php ENDPATH**/ ?>