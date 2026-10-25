<?php $__env->startSection('title', 'Checklist'); ?>
<?php $__env->startSection('page-title', 'Template Checklist'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Checklist</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="alert alert-info small">
    Template ini menjadi <strong>salinan</strong> saat proses commissioning dibuat. Mengubahnya hanya memengaruhi proses <strong>baru</strong>; proses lama tetap memakai checklist saat dibuat.
    Isi bawaan adalah usulan berdasarkan praktik umum pertambangan: <strong>sesuaikan dengan prosedur AKP dan manual pabrikan</strong> sebelum dipakai sebagai dasar keputusan laik operasi.
    <?php echo e($active); ?> dari <?php echo e($total); ?> item aktif.
</div>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="GET" action="<?php echo e(route('cms.checklist')); ?>" class="row g-2 align-items-end">
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Berlaku untuk</label>
            <select name="scope" class="form-select form-select-sm"><option value="">Semua</option>
                <option value="both" <?php if($scope === 'both'): echo 'selected'; endif; ?>>Keduanya</option><option value="commissioning" <?php if($scope === 'commissioning'): echo 'selected'; endif; ?>>Commissioning</option><option value="recommissioning" <?php if($scope === 'recommissioning'): echo 'selected'; endif; ?>>Recommissioning</option></select></div>
        <div class="col-6 col-md-3"><label class="form-label small mb-1">Kategori unit</label>
            <select name="kategori" class="form-select form-select-sm"><option value="">Semua</option><?php $__currentLoopData = config('commissioning.categories'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($code); ?>" <?php if($category === $code): echo 'selected'; endif; ?>><?php echo e($code); ?> &middot; <?php echo e($name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-sm btn-primary">Terapkan</button><a href="<?php echo e(route('cms.checklist')); ?>" class="btn btn-sm btn-outline-secondary">Atur ulang</a></div>
    </form>
</div></div>

<?php if($canEdit): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white"><button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#newItem"><i class="bi bi-plus-lg me-1"></i>Tambah item</button></div>
    <div class="collapse" id="newItem"><div class="card-body">
        <?php echo $__env->make('cms.partials.checklist-form', ['action' => route('cms.checklist.store'), 'item' => null, 'prefix' => 'new'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div></div>
</div>
<?php endif; ?>

<?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupName => $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white"><h2 class="h6 mb-0"><?php echo e($groupName); ?> <span class="text-muted fw-normal">(<?php echo e($list->count()); ?>)</span></h2></div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead><tr><th>Item</th><th>Berlaku</th><th>Kategori</th><th>Alasan</th><th></th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="<?php echo e($i->is_active ? '' : 'text-muted'); ?>">
                            <td><?php echo e($i->label); ?><?php if($i->is_critical): ?> <span class="badge bg-danger-subtle text-danger">Kritis</span><?php endif; ?> <?php if (! ($i->is_active)): ?><span class="badge bg-secondary-subtle text-secondary">nonaktif</span><?php endif; ?>
                                <?php if($i->reference): ?><div class="small text-muted">Acuan: <?php echo e($i->reference); ?></div><?php endif; ?></td>
                            <td class="small"><?php echo e(['both' => 'Keduanya', 'commissioning' => 'Commissioning', 'recommissioning' => 'Recommissioning'][$i->scope]); ?></td>
                            <td class="small"><?php echo e($i->categories === 'all' ? 'Semua' : $i->categories); ?></td>
                            <td class="small"><?php echo e($i->reasons ? collect($i->reasonList())->map(fn ($r) => config('commissioning.reasons.'.$r, $r))->implode(', ') : '-'); ?></td>
                            <td class="text-end text-nowrap">
                                <?php if($canEdit): ?>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit<?php echo e($i->id); ?>"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="<?php echo e(route('cms.checklist.toggle', $i)); ?>" class="d-inline"><?php echo csrf_field(); ?><button class="btn btn-sm btn-outline-<?php echo e($i->is_active ? 'danger' : 'success'); ?>"><?php echo e($i->is_active ? 'Nonaktifkan' : 'Aktifkan'); ?></button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if($canEdit): ?>
                            <tr class="collapse" id="edit<?php echo e($i->id); ?>"><td colspan="5" class="bg-light">
                                <?php echo $__env->make('cms.partials.checklist-form', ['action' => route('cms.checklist.update', $i), 'item' => $i, 'prefix' => 'e'.$i->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </td></tr>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="text-center text-muted py-5">Tidak ada item pada pilihan ini.</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/checklist.blade.php ENDPATH**/ ?>