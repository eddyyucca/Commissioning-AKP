<?php
    $cats = $item ? $item->categoryList() : [];
    $reasons = $item ? $item->reasonList() : [];
?>
<form method="POST" action="<?php echo e($action); ?>" class="row g-3">
    <?php echo csrf_field(); ?>
    <?php if($item): ?> <?php echo method_field('PUT'); ?> <?php endif; ?>
    <div class="col-md-3"><label class="form-label small mb-1">Kelompok</label><input type="text" name="group_name" value="<?php echo e($item?->group_name); ?>" class="form-control form-control-sm" maxlength="80" required list="groupList<?php echo e($prefix); ?>"></div>
    <div class="col-md-6"><label class="form-label small mb-1">Isi item</label><input type="text" name="label" value="<?php echo e($item?->label); ?>" class="form-control form-control-sm" maxlength="255" required></div>
    <div class="col-md-3"><label class="form-label small mb-1">Berlaku untuk</label>
        <select name="scope" class="form-select form-select-sm">
            <?php $__currentLoopData = ['both' => 'Commissioning dan recommissioning', 'commissioning' => 'Commissioning saja', 'recommissioning' => 'Recommissioning saja']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if(($item?->scope ?? 'both') === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select></div>
    <div class="col-md-6"><label class="form-label small mb-1">Kategori unit (kosong = semua)</label>
        <div class="d-flex flex-wrap gap-3"><?php $__currentLoopData = config('commissioning.categories'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo e($code); ?>" id="<?php echo e($prefix); ?>c<?php echo e($code); ?>" <?php if(in_array($code, $cats, true)): echo 'checked'; endif; ?>><label class="form-check-label small" for="<?php echo e($prefix); ?>c<?php echo e($code); ?>" title="<?php echo e($name); ?>"><?php echo e($code); ?></label></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></div>
    <div class="col-md-6"><label class="form-label small mb-1">Alasan recommissioning (kosong = semua alasan)</label>
        <div class="d-flex flex-wrap gap-3"><?php $__currentLoopData = config('commissioning.reasons'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="reasons[]" value="<?php echo e($key); ?>" id="<?php echo e($prefix); ?>r<?php echo e($key); ?>" <?php if(in_array($key, $reasons, true)): echo 'checked'; endif; ?>><label class="form-check-label small" for="<?php echo e($prefix); ?>r<?php echo e($key); ?>"><?php echo e($label); ?></label></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></div>
    <div class="col-md-6"><label class="form-label small mb-1">Acuan (prosedur / standar, opsional)</label><input type="text" name="reference" value="<?php echo e($item?->reference); ?>" class="form-control form-control-sm" maxlength="120"></div>
    <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_critical" value="1" id="<?php echo e($prefix); ?>crit" <?php if($item?->is_critical): echo 'checked'; endif; ?>><label class="form-check-label" for="<?php echo e($prefix); ?>crit">Item kritis (gagal = tidak boleh laik)</label></div></div>
    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-sm btn-primary w-100">Simpan</button></div>
</form>
<datalist id="groupList<?php echo e($prefix); ?>"><?php $__currentLoopData = \App\Models\ChecklistTemplateItem::query()->distinct()->orderBy('group_name')->pluck('group_name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($g); ?>"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></datalist>
<?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/partials/checklist-form.blade.php ENDPATH**/ ?>