<?php $__env->startSection('title', 'Unit '.$unit->code); ?>
<?php $__env->startSection('page-title', 'Unit '.$unit->code); ?>
<?php $__env->startSection('back-url', route('cms.units')); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('cms.units')); ?>">Unit</a></li>
    <li class="breadcrumb-item active"><?php echo e($unit->code); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $left = $unit->daysLeft();
    $badge = \App\Models\Unit::STATUS_BADGES[$ops];
    $total = max((int) ($unit->last_commissioned_at && $unit->commissioned_until ? $unit->last_commissioned_at->startOfDay()->diffInDays($unit->commissioned_until) : 0), 1);
    $pct = $left === null ? 0 : max(0, min(100, round($left / $total * 100)));
    $barColor = $left === null ? '#94a3b8' : ($left < 0 ? '#dc2626' : ($left <= config('commissioning.expiring_days') ? '#f59e0b' : '#16a34a'));
?>

<?php if($unit->hold_since): ?>
    <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i class="bi bi-sign-stop-fill fs-4"></i>
        <div><strong>Unit ditahan (hold) sejak <?php echo e($unit->hold_since->translatedFormat('d M Y H:i')); ?>.</strong> Tidak boleh dioperasikan sampai recommissioning disetujui.
            <div class="small"><?php echo e($unit->hold_reason); ?></div></div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm h-100"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <div class="small text-muted"><?php echo e($unit->categoryName()); ?><?php if($unit->area): ?> &middot; <?php echo e($unit->area); ?><?php endif; ?></div>
                    <h2 class="h4 mb-1"><?php echo e($unit->code); ?> <span class="fw-normal text-muted fs-6"><?php echo e($unit->name); ?></span></h2>
                    <div class="small text-muted"><?php echo e(collect([$unit->brand, $unit->model, $unit->year])->filter()->implode(' · ')); ?><?php if($unit->serial_number): ?> &middot; SN <?php echo e($unit->serial_number); ?><?php endif; ?> <?php if($unit->plate): ?> &middot; <?php echo e($unit->plate); ?><?php endif; ?> &middot; Pemilik <?php echo e($unit->owner); ?></div>
                    <?php if (! ($unit->is_active)): ?><span class="badge bg-secondary-subtle text-secondary mt-1">Unit nonaktif</span><?php endif; ?>
                </div>
                <div class="text-end">
                    <span class="badge fs-6 bg-<?php echo e($badge); ?>-subtle text-<?php echo e($badge === 'dark' ? 'dark' : $badge); ?>"><?php echo e(\App\Models\Unit::STATUS_LABELS[$ops]); ?></span>
                    <?php if($unit->commissioned_until): ?>
                        <div class="small mt-1">Berlaku sampai <strong><?php echo e($unit->commissioned_until->translatedFormat('d M Y')); ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($unit->commissioned_until): ?>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Terakhir laik <?php echo e($unit->last_commissioned_at?->translatedFormat('d M Y')); ?></span>
                        <span><?php echo e($left < 0 ? 'Lewat '.abs($left).' hari' : $left.' hari lagi'); ?></span>
                    </div>
                    <div class="progress" style="height:10px"><div class="progress-bar" role="progressbar" style="width: <?php echo e($pct); ?>%; background: <?php echo e($barColor); ?>" aria-valuenow="<?php echo e($pct); ?>" aria-valuemin="0" aria-valuemax="100"></div></div>
                </div>
            <?php endif; ?>

            <?php if($unit->idle_since): ?>
                <div class="alert alert-warning py-2 mt-3 mb-0 small d-flex justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-pause-circle me-1"></i>Unit idle sejak <?php echo e($unit->idle_since->translatedFormat('d M Y')); ?> (<?php echo e($unit->idle_since->diffInDays(today())); ?> hari). Lakukan recommissioning sebelum dipakai lagi.</span>
                    <?php if($canDocs): ?><form method="POST" action="<?php echo e(route('cms.units.idle.clear', $unit)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-sm btn-outline-secondary">Hapus tanda idle</button></form><?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <?php if($activeProcess): ?>
                    <a href="<?php echo e(route('cms.show', $activeProcess)); ?>" class="btn btn-primary btn-sm"><i class="bi bi-journal-check me-1"></i>Lanjutkan <?php echo e($activeProcess->number); ?> (<?php echo e($activeProcess->statusLabel()); ?>)</a>
                <?php elseif($canStart && $unit->is_active): ?>
                    <a href="<?php echo e(route('cms.create', ['unit' => $unit->code])); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i><?php echo e($unit->last_commissioning_id ? 'Mulai recommissioning' : 'Mulai commissioning'); ?></a>
                <?php endif; ?>
                <?php if($canIncident): ?><a href="<?php echo e(route('cms.incidents', ['unit' => $unit->code])); ?>#form" class="btn btn-outline-danger btn-sm"><i class="bi bi-cone-striped me-1"></i>Lapor kejadian</a><?php endif; ?>
                <?php if($canEdit): ?><a href="<?php echo e(route('cms.units.edit', $unit)); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i>Ubah</a><?php endif; ?>
                <?php if($canDocs && ! $unit->idle_since): ?>
                    <button class="btn btn-outline-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#idleForm"><i class="bi bi-pause-circle me-1"></i>Tandai idle</button>
                <?php endif; ?>
            </div>
            <?php if($canDocs && ! $unit->idle_since): ?>
                <div class="collapse mt-2" id="idleForm">
                    <form method="POST" action="<?php echo e(route('cms.units.idle', $unit)); ?>" class="d-flex gap-2 align-items-end">
                        <?php echo csrf_field(); ?>
                        <div><label class="form-label small mb-1">Idle sejak</label><input type="date" name="idle_since" value="<?php echo e(today()->toDateString()); ?>" max="<?php echo e(today()->toDateString()); ?>" class="form-control form-control-sm" required></div>
                        <button class="btn btn-sm btn-warning">Simpan</button>
                    </form>
                    <div class="form-text">Setelah <?php echo e(config('commissioning.idle_days')); ?> hari idle, sistem mengirim alert agar unit di-recommissioning sebelum dioperasikan.</div>
                </div>
            <?php endif; ?>
        </div></div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-bell me-2"></i>Alert aktif (<?php echo e($alerts->count()); ?>)</h2></div>
            <div class="list-group list-group-flush">
                <?php $__empty_1 = true; $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-2"><span class="fw-semibold small"><?php echo e($a->title); ?></span>
                            <span class="badge bg-<?php echo e(\App\Models\Alert::SEVERITY_BADGES[$a->severity]); ?>-subtle text-<?php echo e(\App\Models\Alert::SEVERITY_BADGES[$a->severity]); ?> align-self-start"><?php echo e(\App\Models\Alert::SEVERITY_LABELS[$a->severity]); ?></span></div>
                        <div class="small text-muted"><?php echo e($a->message); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center text-muted py-4 small"><i class="bi bi-check2-circle fs-3 d-block text-success"></i>Tidak ada alert untuk unit ini.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat commissioning</h2></div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>No.</th><th>Jenis</th><th>Alasan</th><th>Status</th><th>Hasil</th><th>Berlaku sampai</th><th>Pemohon</th></tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><a href="<?php echo e(route('cms.show', $h)); ?>" class="text-decoration-none small"><?php echo e($h->number); ?></a><div class="small text-muted"><?php echo e($h->created_at->translatedFormat('d M Y')); ?></div></td>
                        <td class="small"><?php echo e($h->kindLabel()); ?></td>
                        <td class="small"><?php echo e($h->reasonLabel() ?? '-'); ?></td>
                        <td><span class="badge bg-<?php echo e(\App\Models\Commissioning::STATUS_BADGES[$h->status]); ?>-subtle text-<?php echo e(\App\Models\Commissioning::STATUS_BADGES[$h->status] === 'dark' ? 'dark' : \App\Models\Commissioning::STATUS_BADGES[$h->status]); ?>"><?php echo e($h->statusLabel()); ?></span></td>
                        <td class="small"><?php echo e($h->result ? \App\Models\Commissioning::RESULT_LABELS[$h->result] : '-'); ?></td>
                        <td class="small"><?php echo e($h->valid_until?->translatedFormat('d M Y') ?? '-'); ?></td>
                        <td class="small"><?php echo e($h->requested_by_name); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada commissioning untuk unit ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h6 mb-0"><i class="bi bi-exclamation-diamond me-2"></i>Temuan terbuka (<?php echo e($findings->count()); ?>)</h2><a href="<?php echo e(route('cms.findings', ['q' => $unit->code])); ?>" class="small text-decoration-none">Lihat</a></div>
            <div class="list-group list-group-flush">
                <?php $__empty_1 = true; $__currentLoopData = $findings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-2"><span class="fw-semibold small"><?php echo e($f->title); ?></span>
                            <span class="badge bg-<?php echo e(\App\Models\Finding::SEVERITY_BADGES[$f->severity]); ?>-subtle text-<?php echo e(\App\Models\Finding::SEVERITY_BADGES[$f->severity]); ?>"><?php echo e(\App\Models\Finding::SEVERITY_LABELS[$f->severity]); ?></span></div>
                        <div class="small text-muted"><?php echo e($f->pic_name ? 'PIC '.$f->pic_name.' · ' : ''); ?><?php echo e($f->due_date ? 'tenggat '.$f->due_date->translatedFormat('d M Y') : 'belum ada tenggat'); ?><?php if($f->isOverdue()): ?> <span class="text-danger">(terlambat)</span><?php endif; ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center text-muted py-4 small">Tidak ada temuan terbuka.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6" id="dokumen">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-file-earmark-text me-2"></i>Dokumen unit (<?php echo e($documents->count()); ?>)</h2>
                <?php if($canDocs): ?><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#docForm"><i class="bi bi-plus-lg"></i> Tambah</button><?php endif; ?>
            </div>
            <?php if($canDocs): ?>
                <div class="collapse border-bottom" id="docForm"><form method="POST" action="<?php echo e(route('cms.units.docs.store', $unit)); ?>" class="p-3 row g-2">
                    <?php echo csrf_field(); ?>
                    <div class="col-6"><label class="form-label small mb-1">Jenis</label>
                        <select name="type" class="form-select form-select-sm" required><?php $__currentLoopData = config('commissioning.document_types'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($key); ?>"><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                    <div class="col-6"><label class="form-label small mb-1">Nomor</label><input type="text" name="number" class="form-control form-control-sm" maxlength="80"></div>
                    <div class="col-6"><label class="form-label small mb-1">Terbit</label><input type="date" name="issued_at" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small mb-1">Berakhir</label><input type="date" name="expires_at" class="form-control form-control-sm"></div>
                    <div class="col-12"><label class="form-label small mb-1">Catatan</label><input type="text" name="notes" class="form-control form-control-sm" maxlength="255"></div>
                    <div class="col-12"><button class="btn btn-sm btn-primary">Simpan dokumen</button></div>
                </form></div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php ($state = $d->state()); ?>
                            <tr>
                                <td><div class="fw-semibold small"><?php echo e($d->typeLabel()); ?></div><div class="small text-muted"><?php echo e($d->number ?: '-'); ?></div></td>
                                <td class="small text-nowrap">
                                    <?php if($d->expires_at): ?><?php echo e($d->expires_at->translatedFormat('d M Y')); ?>

                                        <div class="<?php echo e($state === 'expired' ? 'text-danger' : ($state === 'expiring' ? 'text-warning' : 'text-muted')); ?>"><?php echo e($d->daysLeft() < 0 ? 'lewat '.abs($d->daysLeft()).' hari' : $d->daysLeft().' hari lagi'); ?></div>
                                    <?php else: ?> <span class="text-muted">tanpa tanggal berakhir</span><?php endif; ?>
                                </td>
                                <td class="text-end"><?php if($canDocs): ?><form method="POST" action="<?php echo e(route('cms.units.docs.destroy', [$unit, $d])); ?>" onsubmit="return confirm('Hapus dokumen ini?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form><?php endif; ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td class="text-center text-muted py-4 small">Belum ada dokumen. Catat sertifikat dan masa berlakunya agar dipantau otomatis.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if($incidents->isNotEmpty()): ?>
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-cone-striped me-2"></i>Kejadian pada unit</h2></div>
    <div class="table-responsive"><table class="table table-modern align-middle mb-0">
        <thead><tr><th>Waktu</th><th>Jenis</th><th>Tingkat</th><th>Uraian</th><th>Status</th></tr></thead>
        <tbody><?php $__currentLoopData = $incidents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td class="small text-nowrap"><?php echo e($i->occurred_at->translatedFormat('d M Y H:i')); ?></td><td class="small"><?php echo e($i->kindLabel()); ?></td><td class="small"><?php echo e($i->severityLabel()); ?></td>
                <td class="small"><?php echo e($i->description); ?></td>
                <td class="small"><?php if($i->requires_recommissioning): ?><span class="badge <?php echo e($i->status === 'open' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'); ?>"><?php echo e($i->status === 'open' ? 'Wajib recommissioning' : 'Sudah recommissioning'); ?></span><?php else: ?><span class="text-muted">Dicatat</span><?php endif; ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody>
    </table></div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/units/show.blade.php ENDPATH**/ ?>