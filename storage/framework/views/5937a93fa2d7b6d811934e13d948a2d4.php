<?php $__env->startSection('title', 'Laporan'); ?>
<?php $__env->startSection('page-title', 'Laporan dan Ekspor'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Laporan</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3 mb-4">
    <?php $__currentLoopData = $summary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="erp-card h-100"><div class="erp-card-body">
                <div class="kpi-label"><?php echo e($s['label']); ?></div>
                <div class="d-flex align-items-center gap-2"><span class="dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background: <?php echo e(\App\Models\Unit::STATUS_COLORS[$s['key']]); ?>"></span><span class="kpi-value fs-4 fw-bold"><?php echo e($s['n']); ?></span></div>
            </div></div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-download me-2"></i>Unduh data (CSV, terbuka rapi di Excel)</h2></div>
    <div class="list-group list-group-flush">
        <?php $__currentLoopData = [
            ['cms.report.units', 'Daftar unit dan status', 'Status operasional, masa berlaku, sisa hari, alasan hold, idle.', $counts['units']],
            ['cms.report.commissionings', 'Semua proses commissioning', 'Jenis, alasan, status, hasil, pemohon, verifikator, penyetuju, masa berlaku.', $counts['commissionings']],
            ['cms.report.findings', 'Temuan', 'Tingkat, status, PIC, tenggat, dan catatan penutupan.', $counts['findings']],
            ['cms.report.alerts', 'Alert', 'Alert aktif dan riwayat, siapa yang melihat dan kapan.', $counts['alerts'].' aktif'],
            ['cms.report.incidents', 'Insiden dan kejadian unit', 'Jenis, tingkat, dan apakah menahan unit.', $counts['incidents']],
            ['cms.report.documents', 'Dokumen unit', 'Sertifikat, masa berlaku, dan sisa hari.', $counts['documents']],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$route, $title, $desc, $n]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route($route)); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3">
                <span><span class="fw-semibold"><?php echo e($title); ?></span><span class="small text-muted d-block"><?php echo e($desc); ?></span></span>
                <span class="text-nowrap small text-muted"><?php echo e(is_numeric($n) ? number_format($n).' baris' : $n); ?> <i class="bi bi-download ms-2"></i></span>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\akp_master\commissioning\resources\views/cms/report.blade.php ENDPATH**/ ?>