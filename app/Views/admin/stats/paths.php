<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '이동 경로 통계' ?>

<?php
/** 라벨·건수 목록 카드. $link 가 true 면 라벨(주소)을 클릭해 그 페이지 기준으로 본다. */
$renderList = static function (string $title, array $rows, int $days, bool $link = false, string $empty = '기록이 없습니다'): void {
    ?>
    <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white"><strong><?= esc($title) ?></strong></div>
        <div class="list-group list-group-flush">
            <?php if (empty($rows)): ?>
            <div class="list-group-item text-muted small text-center py-3"><?= esc($empty) ?></div>
            <?php endif; ?>
            <?php foreach ($rows as $row): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <?php if ($link && $row['label'][0] === '/'): ?>
                <a class="text-truncate small" href="<?= base_url('admin/stats/paths') ?>?days=<?= $days ?>&amp;page=<?= esc(rawurlencode($row['label']), 'attr') ?>"><?= esc($row['label']) ?></a>
                <?php else: ?>
                <span class="text-truncate small text-dark"><?= esc($row['label']) ?></span>
                <?php endif; ?>
                <span class="badge bg-light text-dark border flex-shrink-0 ms-2"><?= number_format($row['count']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
};
?>

<?= $this->section('content') ?>

<h2 class="h5 mb-3">이동 경로 통계</h2>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="<?= base_url('admin/stats') ?>" class="btn btn-sm btn-outline-secondary">접속 통계</a>
    <div class="btn-group btn-group-sm" role="group" aria-label="기간 선택">
        <?php foreach ($dayOptions as $option): ?>
        <a href="<?= base_url('admin/stats/paths') ?>?days=<?= $option ?><?= $page !== null ? '&amp;page=' . esc(rawurlencode($page), 'attr') : '' ?>"
           class="btn btn-outline-primary <?= $days === $option ? 'active' : '' ?>"
           <?= $days === $option ? 'aria-current="true"' : '' ?>>최근 <?= $option ?>일</a>
        <?php endforeach; ?>
    </div>
    <span class="small text-muted">방문자는 IP+User-Agent 로 구분하고, 30분 이상 활동이 없으면 새 방문(세션)으로 봅니다. 봇·오류 응답은 기록 단계에서 제외, 10분 단위 갱신.</span>
</div>

<?php if ($truncated): ?>
<div class="alert alert-warning small">방문 기록이 많아 기간 앞쪽 일부만 분석했습니다. 더 짧은 기간을 선택해 주세요.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <?php foreach ([
        ['방문(세션)', number_format($result['sessions']), 'bi-people'],
        ['페이지뷰', number_format($result['pageviews']), 'bi-eye'],
        ['방문당 페이지', $result['avgPages'], 'bi-layers'],
        ['이탈률(1페이지만 보고 떠남)', $result['bounceRate'] . '%', 'bi-box-arrow-right'],
    ] as [$label, $value, $icon]): ?>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="small text-muted"><i class="bi <?= $icon ?>"></i> <?= esc($label) ?></div>
            <div class="fs-4 fw-bold"><?= esc((string) $value) ?></div>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($page !== null && $result['focus'] !== null): ?>
<div class="card border-primary shadow-sm mb-3">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><strong><?= esc($page) ?></strong> <span class="small text-muted">· <?= number_format($result['focus']['visits']) ?>회 조회 · 이 페이지에서 떠난 비율 <?= $result['focus']['exitRate'] ?>%</span></span>
        <a href="<?= base_url('admin/stats/paths') ?>?days=<?= $days ?>" class="btn btn-sm btn-outline-secondary">전체 보기</a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><?php $renderList('어디서 왔나 (직전 페이지)', $result['focus']['previous'], $days, true); ?></div>
            <div class="col-md-6"><?php $renderList('어디로 갔나 (다음 페이지)', $result['focus']['next'], $days, true); ?></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-6"><?php $renderList('진입 페이지 (첫 페이지)', $result['entries'], $days, true); ?></div>
    <div class="col-lg-6"><?php $renderList('이탈 페이지 (마지막 페이지)', $result['exits'], $days, true); ?></div>
    <div class="col-lg-6"><?php $renderList('자주 이동하는 구간', $result['transitions'], $days, false); ?></div>
    <div class="col-lg-6"><?php $renderList('자주 걷는 경로 (2페이지 이상)', $result['paths'], $days, false); ?></div>
    <div class="col-lg-6"><?php $renderList('방문당 본 페이지 수', $result['depth'], $days, false); ?></div>
</div>

<?= $this->endSection() ?>
