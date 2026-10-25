<?php $__env->startSection('title', $c->number); ?>
<?php $__env->startSection('page-title', $c->kindLabel().' '.$c->number); ?>
<?php $__env->startSection('back-url', route('cms.index')); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('cms.index')); ?>">Daftar proses</a></li>
    <li class="breadcrumb-item active"><?php echo e($c->number); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('topbar-actions'); ?>
    <?php if($c->status === 'approved'): ?>
        <a href="<?php echo e(route('cms.certificate', $c)); ?>" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2"><i class="bi bi-printer"></i><span class="d-none d-sm-inline">Lembar laik operasi</span></a>
    <?php endif; ?>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .stepper { display: flex; flex-wrap: wrap; gap: .5rem; }
    .step { flex: 1 1 140px; border: 1px solid #e5eaf1; border-radius: 12px; padding: .6rem .8rem; background: #fff; font-size: .82rem; }
    .step .t { font-weight: 600; } .step .s { color: #64748b; font-size: .76rem; }
    .step.done { border-color: #16a34a; background: #f0fdf4; } .step.now { border-color: #1b4f86; background: #eff6ff; } .step.bad { border-color: #f59e0b; background: #fffbeb; }
    .item-row { border-bottom: 1px solid #eef1f6; padding: .7rem 0; }
    .item-row:last-child { border-bottom: 0; }
    .item-row.is-bad { background: #fff5f5; margin: 0 -1rem; padding-left: 1rem; padding-right: 1rem; }
    .crit { color: #dc2626; font-size: .72rem; font-weight: 700; }
    .btn-check:checked + .btn-outline-success { background: #16a34a; color: #fff; }
    .btn-check:checked + .btn-outline-danger { background: #dc2626; color: #fff; }
    .btn-check:checked + .btn-outline-secondary { background: #64748b; color: #fff; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $b = \App\Models\Commissioning::STATUS_BADGES[$c->status];
    $answered = $items->whereNotNull('result')->count();
    $bad = $items->where('result', 'bad')->count();
    $critBad = $items->where('result', 'bad')->where('is_critical', true)->count();
    $steps = [
        ['t' => 'Diisi pemeriksa', 's' => $c->requested_by_name, 'c' => in_array($c->status, ['draft', 'rejected'], true) ? ($c->status === 'rejected' ? 'bad' : 'now') : 'done'],
        ['t' => 'Verifikasi Safety', 's' => $c->safety_at ? $c->safety_by_name.' · '.$c->safety_at->translatedFormat('d M H:i') : 'menunggu', 'c' => $c->safety_at ? 'done' : ($c->status === 'submitted' ? 'now' : '')],
        ['t' => 'Verifikasi Workshop', 's' => $c->workshop_at ? $c->workshop_by_name.' · '.$c->workshop_at->translatedFormat('d M H:i') : 'menunggu', 'c' => $c->workshop_at ? 'done' : ($c->status === 'submitted' ? 'now' : '')],
        ['t' => 'Keputusan', 's' => $c->decided_at ? $c->decided_by_name.' · '.$c->decided_at->translatedFormat('d M H:i') : ($c->status === 'submitted' && $c->bothVerified() ? 'menunggu penyetuju' : '-'),
            'c' => $c->status === 'approved' ? 'done' : ($c->status === 'submitted' && $c->bothVerified() ? 'now' : '')],
    ];
?>


<div class="card shadow-sm mb-4"><div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
        <div>
            <div class="small text-muted"><?php echo e($c->kindLabel()); ?><?php if($c->reason): ?> &middot; <?php echo e($c->reasonLabel()); ?><?php endif; ?></div>
            <h2 class="h5 mb-1"><a href="<?php echo e(route('cms.units.show', $c->unit_code)); ?>" class="text-decoration-none"><?php echo e($c->unit_code); ?></a> <span class="fw-normal text-muted fs-6"><?php echo e($c->unit_name); ?></span></h2>
            <div class="small text-muted">Pemohon <?php echo e($c->requested_by_name); ?> &middot; dibuat <?php echo e($c->created_at->translatedFormat('d M Y H:i')); ?><?php if($c->area): ?> &middot; <?php echo e($c->area); ?><?php endif; ?>
                <?php if($c->meter_reading !== null): ?> &middot; meter <?php echo e(number_format($c->meter_reading, 0, ',', '.')); ?> <?php echo e($c->meter_unit); ?><?php endif; ?></div>
            <?php if($c->reason_note): ?><div class="small mt-1"><?php echo e($c->reason_note); ?></div><?php endif; ?>
        </div>
        <div class="text-end">
            <span class="badge fs-6 bg-<?php echo e($b); ?>-subtle text-<?php echo e($b === 'dark' ? 'dark' : $b); ?>"><?php echo e($c->statusLabel()); ?></span>
            <?php if($c->result): ?><div class="fw-bold mt-1 <?php echo e($c->result === 'laik' ? 'text-success' : 'text-warning'); ?>"><?php echo e(\App\Models\Commissioning::RESULT_LABELS[$c->result]); ?></div><?php endif; ?>
            <?php if($c->valid_until): ?><div class="small">Berlaku sampai <strong><?php echo e($c->valid_until->translatedFormat('d M Y')); ?></strong></div><?php endif; ?>
        </div>
    </div>
    <div class="stepper">
        <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div class="step <?php echo e($s['c']); ?>"><div class="t"><?php echo e($loop->iteration); ?>. <?php echo e($s['t']); ?></div><div class="s"><?php echo e($s['s']); ?></div></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php if($c->status === 'rejected' && $c->returned_reason): ?>
        <div class="alert alert-warning mt-3 mb-0"><strong>Dikembalikan untuk diperbaiki:</strong> <?php echo e($c->returned_reason); ?></div>
    <?php endif; ?>
    <?php if($c->status === 'cancelled' && $c->cancelled_reason): ?>
        <div class="alert alert-secondary mt-3 mb-0"><strong>Dibatalkan:</strong> <?php echo e($c->cancelled_reason); ?></div>
    <?php endif; ?>
    <?php if($c->decision_notes): ?><div class="alert alert-info mt-3 mb-0"><strong>Catatan keputusan:</strong> <?php echo e($c->decision_notes); ?></div><?php endif; ?>
    <?php if($c->safety_notes || $c->workshop_notes): ?>
        <div class="small text-muted mt-3">
            <?php if($c->safety_notes): ?><div><strong>Catatan Safety:</strong> <?php echo e($c->safety_notes); ?></div><?php endif; ?>
            <?php if($c->workshop_notes): ?><div><strong>Catatan Workshop:</strong> <?php echo e($c->workshop_notes); ?></div><?php endif; ?>
        </div>
    <?php endif; ?>
</div></div>


<?php if($can['edit']): ?>
<form method="POST" action="<?php echo e(route('cms.answers', $c)); ?>" enctype="multipart/form-data" id="answerForm">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-info-circle me-2"></i>Data pemeriksaan</h2></div>
        <div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label small mb-1">Rencana tanggal</label><input type="date" name="planned_date" value="<?php echo e(old('planned_date', $c->planned_date?->toDateString())); ?>" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="form-label small mb-1">Area / lokasi</label><input type="text" name="area" value="<?php echo e(old('area', $c->area)); ?>" class="form-control form-control-sm" maxlength="80"></div>
            <div class="col-md-3"><label class="form-label small mb-1">Pembacaan meter</label>
                <div class="input-group input-group-sm"><input type="number" name="meter_reading" value="<?php echo e(old('meter_reading', $c->meter_reading)); ?>" class="form-control" min="0">
                    <select name="meter_unit" class="form-select" style="max-width:80px"><option value="jam" <?php if(old('meter_unit', $c->meter_unit) === 'jam'): echo 'selected'; endif; ?>>jam</option><option value="km" <?php if(old('meter_unit', $c->meter_unit) === 'km'): echo 'selected'; endif; ?>>km</option></select></div></div>
            <div class="col-md-3"><label class="form-label small mb-1">Keterangan alasan</label><input type="text" name="reason_note" value="<?php echo e(old('reason_note', $c->reason_note)); ?>" class="form-control form-control-sm" maxlength="255"></div>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 mb-0"><i class="bi bi-ui-checks me-2"></i>Checklist (<?php echo e($items->count()); ?> item)</h2>
        <div class="small text-muted">
            <span id="progressText"><?php echo e($answered); ?> dari <?php echo e($items->count()); ?> terisi</span> &middot;
            <span class="text-danger"><span id="badCount"><?php echo e($bad); ?></span> tidak sesuai</span>
            <?php if($critBad > 0): ?><span class="badge bg-danger ms-1"><?php echo e($critBad); ?> kritis</span><?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupName => $groupItems): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <h3 class="h6 text-primary mt-3 mb-2"><?php echo e($groupName); ?></h3>
            <?php $__currentLoopData = $groupItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($res = old('result.'.$item->id, $item->result)); ?>
                <div class="item-row <?php echo e($res === 'bad' ? 'is-bad' : ''); ?>" data-item>
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="me-2">
                            <span class="fw-semibold"><?php echo e($item->label); ?></span><?php if($item->is_critical): ?> <span class="crit">KRITIS</span><?php endif; ?>
                            <?php if($item->reference): ?><div class="small text-muted">Acuan: <?php echo e($item->reference); ?></div><?php endif; ?>
                        </div>
                        <?php if($can['edit']): ?>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="result[<?php echo e($item->id); ?>]" id="r<?php echo e($item->id); ?>ok" value="ok" <?php if($res === 'ok'): echo 'checked'; endif; ?>>
                                <label class="btn btn-outline-success" for="r<?php echo e($item->id); ?>ok">Sesuai</label>
                                <input type="radio" class="btn-check" name="result[<?php echo e($item->id); ?>]" id="r<?php echo e($item->id); ?>bad" value="bad" <?php if($res === 'bad'): echo 'checked'; endif; ?>>
                                <label class="btn btn-outline-danger" for="r<?php echo e($item->id); ?>bad">Tidak sesuai</label>
                                <input type="radio" class="btn-check" name="result[<?php echo e($item->id); ?>]" id="r<?php echo e($item->id); ?>na" value="na" <?php if($res === 'na'): echo 'checked'; endif; ?>>
                                <label class="btn btn-outline-secondary" for="r<?php echo e($item->id); ?>na">N/A</label>
                            </div>
                        <?php else: ?>
                            <div>
                                <?php if($item->result === 'ok'): ?><span class="badge bg-success-subtle text-success">Sesuai</span>
                                <?php elseif($item->result === 'bad'): ?><span class="badge bg-danger-subtle text-danger">Tidak sesuai</span>
                                <?php elseif($item->result === 'na'): ?><span class="badge bg-secondary-subtle text-secondary">N/A</span>
                                <?php else: ?><span class="badge bg-light text-muted">Belum diisi</span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if($can['edit']): ?>
                        <div class="row g-2 mt-1">
                            <div class="col-md-8"><input type="text" name="note[<?php echo e($item->id); ?>]" value="<?php echo e(old('note.'.$item->id, $item->note)); ?>" class="form-control form-control-sm" maxlength="500" placeholder="Keterangan (wajib bila tidak sesuai atau N/A pada item kritis)"></div>
                            <div class="col-md-4 d-flex gap-2 align-items-center">
                                <input type="file" name="photos[<?php echo e($item->id); ?>]" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
                                <?php if($item->photo_path): ?><a href="<?php echo e(route('cms.photo', $item)); ?>" target="_blank" class="small text-nowrap">foto</a>
                                    <label class="small text-nowrap"><input type="checkbox" name="remove_photo[<?php echo e($item->id); ?>]" value="1"> hapus</label><?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if($item->note || $item->photo_path): ?>
                            <div class="small mt-1 text-muted"><?php echo e($item->note); ?><?php if($item->photo_path): ?> <a href="<?php echo e(route('cms.photo', $item)); ?>" target="_blank"><i class="bi bi-image ms-1"></i> foto</a><?php endif; ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php if($can['edit']): ?>
        <div class="card-footer bg-white d-flex flex-wrap gap-2">
            <button type="submit" name="action" value="save" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i>Simpan</button>
            <button type="submit" name="action" value="submit" class="btn btn-primary" onclick="return confirm('Ajukan sekarang? Setelah diajukan, isian tidak bisa diubah kecuali dikembalikan.')"><i class="bi bi-send me-1"></i>Simpan dan ajukan</button>
        </div>
    <?php endif; ?>
</div>
<?php if($can['edit']): ?></form><?php endif; ?>


<?php if($findings->isNotEmpty()): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><i class="bi bi-exclamation-diamond me-2"></i>Temuan (<?php echo e($openFindings->count()); ?> terbuka dari <?php echo e($findings->count()); ?>)</h2>
        <?php if($openBlocking->isNotEmpty()): ?><span class="badge bg-danger"><?php echo e($openBlocking->count()); ?> kritis/mayor menghalangi persetujuan</span><?php endif; ?>
    </div>
    <div class="list-group list-group-flush">
        <?php $__currentLoopData = $findings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="list-group-item">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <span class="badge bg-<?php echo e(\App\Models\Finding::SEVERITY_BADGES[$f->severity]); ?>-subtle text-<?php echo e(\App\Models\Finding::SEVERITY_BADGES[$f->severity]); ?>"><?php echo e(\App\Models\Finding::SEVERITY_LABELS[$f->severity]); ?></span>
                        <span class="fw-semibold ms-1"><?php echo e($f->title); ?></span>
                        <div class="small text-muted"><?php echo e($f->description); ?></div>
                        <div class="small text-muted"><?php echo e($f->pic_name ? 'PIC '.$f->pic_name.' · ' : ''); ?><?php echo e($f->due_date ? 'tenggat '.$f->due_date->translatedFormat('d M Y') : 'belum ada tenggat'); ?><?php if($f->isOverdue()): ?> <span class="text-danger">(terlambat)</span><?php endif; ?>
                            <?php if($f->action_plan): ?> &middot; <?php echo e($f->action_plan); ?><?php endif; ?></div>
                    </div>
                    <div><span class="badge <?php echo e($f->status === 'closed' ? 'bg-success-subtle text-success' : ($f->status === 'in_progress' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning')); ?>"><?php echo e(\App\Models\Finding::STATUS_LABELS[$f->status]); ?></span></div>
                </div>
                <?php if($f->status === 'closed' && $f->closed_note): ?><div class="small text-success mt-1"><i class="bi bi-check2-circle"></i> <?php echo e($f->closed_by_name); ?>: <?php echo e($f->closed_note); ?></div><?php endif; ?>
                <?php if($f->isOpen() && $can['findings']): ?>
                    <form method="POST" action="<?php echo e(route('cms.findings.update', $f)); ?>" class="row g-2 mt-1 align-items-end">
                        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                        <div class="col-md-3"><input type="text" name="pic_name" value="<?php echo e($f->pic_name); ?>" class="form-control form-control-sm" placeholder="PIC perbaikan" maxlength="120"></div>
                        <div class="col-md-2"><input type="date" name="due_date" value="<?php echo e($f->due_date?->toDateString()); ?>" class="form-control form-control-sm" title="Tenggat"></div>
                        <div class="col-md-4"><input type="text" name="action_plan" value="<?php echo e($f->action_plan); ?>" class="form-control form-control-sm" placeholder="Rencana tindakan" maxlength="500"></div>
                        <div class="col-md-3 d-flex gap-1">
                            <?php if($can['closeFinding']): ?><select name="severity" class="form-select form-select-sm" title="Tingkat"><?php $__currentLoopData = \App\Models\Finding::SEVERITY_LABELS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k); ?>" <?php if($f->severity === $k): echo 'selected'; endif; ?>><?php echo e($l); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><?php endif; ?>
                            <button class="btn btn-sm btn-outline-primary">Simpan</button>
                        </div>
                    </form>
                    <?php if($can['closeFinding']): ?>
                        <form method="POST" action="<?php echo e(route('cms.findings.close', $f)); ?>" class="row g-2 mt-1">
                            <?php echo csrf_field(); ?>
                            <div class="col-md-9"><input type="text" name="note" class="form-control form-control-sm" placeholder="Bukti perbaikan / catatan penutupan" maxlength="500" required></div>
                            <div class="col-md-3"><button class="btn btn-sm btn-success w-100"><i class="bi bi-check2 me-1"></i>Tutup temuan</button></div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php endif; ?>


<?php if($can['safety'] || $can['workshop'] || $can['approve'] || $can['sendBack'] || $can['cancel']): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-diagram-3 me-2"></i>Tindakan</h2></div>
    <div class="card-body">
        <div class="row g-4">
            <?php $__currentLoopData = ['safety' => 'Safety', 'workshop' => 'Workshop']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stage => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($can[$stage]): ?>
                    <div class="col-md-6">
                        <form method="POST" action="<?php echo e(route('cms.verify', [$c, $stage])); ?>">
                            <?php echo csrf_field(); ?>
                            <label class="form-label fw-semibold">Verifikasi <?php echo e($label); ?></label>
                            <input type="text" name="notes" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Catatan verifikasi (opsional)">
                            <button class="btn btn-success btn-sm"><i class="bi bi-shield-check me-1"></i>Verifikasi <?php echo e($label); ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($can['approve']): ?>
                <div class="col-12"><hr class="my-0"></div>
                <div class="col-12">
                    <?php if(! $c->bothVerified()): ?>
                        <div class="alert alert-secondary py-2 small mb-3">Keputusan menunggu verifikasi Safety dan Workshop lengkap.</div>
                    <?php elseif($openBlocking->isNotEmpty()): ?>
                        <div class="alert alert-danger py-2 small mb-3">Ada <?php echo e($openBlocking->count()); ?> temuan kritis/mayor yang masih terbuka. Tutup dulu, atau kembalikan untuk perbaikan.</div>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('cms.approve', $c)); ?>" class="row g-2">
                        <?php echo csrf_field(); ?>
                        <div class="col-12 fw-semibold">Keputusan akhir</div>
                        <div class="col-md-5">
                            <select name="result" class="form-select form-select-sm" required>
                                <option value="laik">Laik operasi</option>
                                <option value="laik_catatan" <?php if($openFindings->isNotEmpty() && $openBlocking->isEmpty()): echo 'selected'; endif; ?>>Laik dengan catatan (ada temuan minor)</option>
                            </select>
                        </div>
                        <div class="col-md-3"><input type="date" name="valid_until" class="form-control form-control-sm" max="<?php echo e($maxUntil->toDateString()); ?>" min="<?php echo e(today()->addDay()->toDateString()); ?>" title="Berlaku sampai (kosong = <?php echo e($maxUntil->translatedFormat('d M Y')); ?>)"></div>
                        <div class="col-md-4"><input type="text" name="notes" class="form-control form-control-sm" maxlength="500" placeholder="Catatan (wajib bila laik dengan catatan)"></div>
                        <div class="col-12 form-text">Masa berlaku kosong = sampai <?php echo e($maxUntil->translatedFormat('d M Y')); ?>. Pilih tanggal lebih awal bila perlu dipersingkat.</div>
                        <div class="col-12"><button class="btn btn-primary btn-sm" onclick="return confirm('Beri keputusan laik operasi untuk unit ini?')"><i class="bi bi-patch-check me-1"></i>Putuskan</button></div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if($can['sendBack']): ?>
                <div class="col-12"><hr class="my-0"></div>
                <div class="col-md-8">
                    <form method="POST" action="<?php echo e(route('cms.sendback', $c)); ?>">
                        <?php echo csrf_field(); ?>
                        <label class="form-label fw-semibold">Kembalikan untuk diperbaiki</label>
                        <div class="input-group input-group-sm"><input type="text" name="reason" class="form-control" maxlength="500" placeholder="Alasan pengembalian" required><button class="btn btn-outline-warning">Kembalikan</button></div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if($can['cancel']): ?>
                <div class="col-md-4">
                    <form method="POST" action="<?php echo e(route('cms.cancel', $c)); ?>" onsubmit="return confirm('Batalkan proses ini?')">
                        <?php echo csrf_field(); ?>
                        <label class="form-label fw-semibold">Batalkan proses</label>
                        <div class="input-group input-group-sm"><input type="text" name="reason" class="form-control" maxlength="255" placeholder="Alasan"><button class="btn btn-outline-danger">Batalkan</button></div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const form = document.getElementById('answerForm');
    if (!form) return;
    const rows = form.querySelectorAll('[data-item]');
    function refresh() {
        let answered = 0, bad = 0;
        rows.forEach(r => {
            const v = r.querySelector('input[type=radio]:checked');
            if (v) { answered++; }
            if (v && v.value === 'bad') { bad++; r.classList.add('is-bad'); } else { r.classList.remove('is-bad'); }
        });
        document.getElementById('progressText').textContent = answered + ' dari ' + rows.length + ' terisi';
        document.getElementById('badCount').textContent = bad;
    }
    form.addEventListener('change', refresh);
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/show.blade.php ENDPATH**/ ?>