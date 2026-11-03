<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class AccessLogModel extends Model
{
    protected $table         = 'access_logs';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['ip', 'page', 'url', 'user_id', 'user_agent', 'referer', 'created_at'];

    /** @return array{pv: int, uv: int} */
    public function getTodayStats(): array
    {
        $today = date('Y-m-d');
        $pv    = $this->where('DATE(created_at)', $today)->countAllResults();
        $uv    = (int) $this->db->query(
            'SELECT COUNT(DISTINCT ip) cnt FROM access_logs WHERE DATE(created_at) = ?',
            [$today]
        )->getRow()->cnt;

        return ['pv' => $pv, 'uv' => $uv];
    }

    /**
     * 이동 경로 분석용 방문 행 ([시작, 끝) 기간, 오래된 순). PathAnalyzer 입력 형식(uri, ip_address)으로 별칭을 맞춘다.
     *
     * @return array{rows: list<array{uri: string, ip_address: string, user_agent: string|null, created_at: string}>, truncated: bool}
     */
    public function visitsForPaths(Time $start, Time $end, int $limit = 50000): array
    {
        /** @var list<array{uri: string, ip_address: string, user_agent: string|null, created_at: string}> $rows */
        $rows = $this->db->table($this->table)
            ->select('page AS uri, ip AS ip_address, user_agent, created_at')
            ->where('created_at >=', $start->toDateTimeString())
            ->where('created_at <', $end->toDateTimeString())
            ->orderBy('id', 'ASC')
            ->limit($limit + 1)
            ->get()
            ->getResultArray();

        $truncated = count($rows) > $limit;

        return ['rows' => $truncated ? array_slice($rows, 0, $limit) : $rows, 'truncated' => $truncated];
    }

    /** 보존 기간(일) 이전 로그를 집계 테이블에 저장 후 삭제 */
    public function purgeOlderThan(int $days = 90): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // 삭제 대상 로그를 일별·페이지별로 집계해 access_log_summaries에 누적
        $this->db->query('
            INSERT INTO access_log_summaries (log_date, page, pv, uv)
            SELECT DATE(created_at), page, COUNT(*) AS pv, COUNT(DISTINCT ip) AS uv
            FROM access_logs
            WHERE created_at < ?
            GROUP BY DATE(created_at), page
            ON DUPLICATE KEY UPDATE
                pv = pv + VALUES(pv),
                uv = uv + VALUES(uv)
        ', [$cutoff]);

        $this->where('created_at <', $cutoff)->delete();
        return $this->db->affectedRows();
    }
}
