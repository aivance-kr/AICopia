<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '백업 관리' ?>
<?= $this->section('content') ?>
<div style="max-width:860px">
    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4 d-flex flex-wrap gap-3 align-items-center justify-content-between"><div><h1 class="h5 mb-1">백업 생성</h1><p class="text-muted small mb-0">데이터베이스와 <code>public/uploads</code> 파일을 하나의 ZIP으로 보관합니다.</p></div><form method="post" action="/admin/backup/create"><?= csrf_field() ?><button type="submit" class="btn btn-primary"<?= in_array($jobStatus['status'] ?? '', ['queued', 'running'], true) ? ' disabled' : '' ?>><i class="bi bi-download me-1" aria-hidden="true"></i>지금 백업</button></form></div></div>
    <?php if (in_array($jobStatus['status'] ?? '', ['queued', 'running'], true)): ?>
        <meta http-equiv="refresh" content="5">
        <div class="alert alert-info small">백업 <?= $jobStatus['status'] === 'queued' ? '작업을 시작하는 중입니다.' : '파일을 생성하고 있습니다.' ?> 완료되면 목록을 자동으로 새로고침합니다.</div>
    <?php elseif (($jobStatus['status'] ?? '') === 'completed'): ?>
        <div class="alert alert-success small">최근 백업 생성이 완료되었습니다.</div>
    <?php elseif (($jobStatus['status'] ?? '') === 'failed'): ?>
        <div class="alert alert-danger small">최근 백업 생성에 실패했습니다. 서버 로그를 확인하세요.</div>
    <?php endif ?>
    <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4"><h2 class="h6 mb-3">서버 보관 백업</h2><?php if ($backups === []): ?><p class="text-muted small mb-0">아직 생성한 백업이 없습니다.</p><?php else: ?><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>파일</th><th>생성 시각</th><th>크기</th><th><span class="visually-hidden">다운로드</span></th></tr></thead><tbody><?php foreach ($backups as $backup): ?><tr><td><code><?= esc($backup['filename']) ?></code></td><td><?= esc(date('Y-m-d H:i', $backup['modified_at'])) ?></td><td><?= esc(number_format($backup['size'] / 1048576, 1)) ?> MB</td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="/admin/backup/<?= rawurlencode($backup['filename']) ?>">다운로드</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div>
    <div class="card border-danger shadow-sm"><div class="card-body p-4"><h2 class="h6 text-danger">백업 복원</h2><p class="small text-muted">AICopia에서 만든 ZIP만 복원할 수 있습니다. DB와 업로드 파일을 현재 백업 시점으로 교체하며, 시작 전에 자동 롤백용 백업을 하나 더 만듭니다.</p><form method="post" action="/admin/backup/restore" enctype="multipart/form-data"><?= csrf_field() ?><div class="mb-3"><label class="form-label small fw-semibold" for="backupZip">백업 ZIP 파일 업로드</label><input id="backupZip" class="form-control" type="file" name="backup_zip" accept=".zip" required></div><div class="mb-3"><label class="form-label small fw-semibold" for="restoreConfirmation">확인 문구</label><input id="restoreConfirmation" class="form-control" name="confirmation" autocomplete="off" placeholder="복원" required><div class="form-text">계속하려면 <code>복원</code>을 정확히 입력하세요.</div></div><button type="submit" class="btn btn-danger">업로드한 파일로 복원</button></form>
    <?php if ($backups !== []): ?>
        <hr class="my-4">
        <form method="post" action="/admin/backup/restore-server"><?= csrf_field() ?><div class="mb-3"><label class="form-label small fw-semibold" for="serverBackupFilename">서버 보관 백업에서 선택</label><select id="serverBackupFilename" class="form-select" name="filename" required><?php foreach ($backups as $backup): ?><option value="<?= esc($backup['filename'], 'attr') ?>"><?= esc($backup['filename']) ?> (<?= esc(date('Y-m-d H:i', $backup['modified_at'])) ?>, <?= esc(number_format($backup['size'] / 1048576, 1)) ?> MB)</option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label small fw-semibold" for="serverRestoreConfirmation">확인 문구</label><input id="serverRestoreConfirmation" class="form-control" name="confirmation" autocomplete="off" placeholder="복원" required><div class="form-text">계속하려면 <code>복원</code>을 정확히 입력하세요.</div></div><button type="submit" class="btn btn-danger">선택한 서버 파일로 복원</button></form>
    <?php endif; ?>
    </div></div>
</div>
<?= $this->endSection() ?>
