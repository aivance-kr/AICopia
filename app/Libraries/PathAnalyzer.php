<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * 방문 로그로 "접속 후 이동 경로"를 분석한다.
 *
 * 프레임워크·DB 에 의존하지 않는 순수 클래스다 — 방문 행 배열을 넣으면 집계 배열을 돌려준다.
 * 다른 프로젝트(aicopia 등)로 옮길 때 이 파일은 네임스페이스만 바꿔 그대로 복사하면 된다.
 *
 * 쿠키·JS 없이 동작하도록 방문자를 `IP + User-Agent` 로 구분하고,
 * 같은 방문자의 요청 사이가 $gapSeconds 이상 벌어지면 새 세션으로 본다(기본 30분).
 * 새로고침(연속 같은 주소)은 한 번으로 합친다.
 *
 * @phpstan-type Visit   array{uri: string, ip_address: string, user_agent: string|null, created_at: string}
 * @phpstan-type Counted list<array{label: string, count: int}>
 */
final class PathAnalyzer
{
    public const ENTRY               = '(진입)';
    public const EXIT                = '(이탈)';
    public const DEFAULT_GAP_SECONDS = 1800;

    /**
     * 경로 단위로 보여줄 최대 단계 수
     */
    private const PATH_STEPS = 5;

    /**
     * 방문 행 → 세션별 주소 목록 (세션 시작 시각순).
     *
     * @param list<Visit> $visits
     *
     * @return list<list<string>>
     */
    public static function buildSessions(array $visits, int $gapSeconds = self::DEFAULT_GAP_SECONDS): array
    {
        $byVisitor = [];

        foreach ($visits as $visit) {
            $byVisitor[$visit['ip_address'] . "\0" . ($visit['user_agent'] ?? '')][] = [
                strtotime($visit['created_at']) ?: 0,
                $visit['uri'],
            ];
        }

        $sessions = [];

        foreach ($byVisitor as $hits) {
            usort($hits, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

            $current = [];
            $lastAt  = null;

            foreach ($hits as [$at, $uri]) {
                if ($lastAt !== null && $at - $lastAt >= $gapSeconds) {
                    $sessions[] = $current;
                    $current    = [];
                }

                if ($current === [] || end($current) !== $uri) {
                    $current[] = $uri;
                }
                $lastAt = $at;
            }

            $sessions[] = $current; // $hits 가 비어 있지 않으므로 항상 1건 이상
        }

        return $sessions;
    }

    /**
     * @param list<list<string>> $sessions buildSessions() 결과
     * @param string|null        $focusUri 주면 그 페이지의 앞뒤 이동(`focus`)도 계산한다
     *
     * @return array{
     *     sessions: int, pageviews: int, avgPages: float, bounceRate: float,
     *     entries: Counted, exits: Counted, transitions: Counted, paths: Counted, depth: Counted,
     *     focus: array{uri: string, visits: int, next: Counted, previous: Counted, exitRate: float}|null
     * }
     */
    public static function analyze(array $sessions, ?string $focusUri = null, int $limit = 10): array
    {
        $pageviews = 0;
        $bounces   = 0;
        $entries   = [];
        $exits     = [];
        $trans     = [];
        $paths     = [];
        $depth     = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5+' => 0];

        $focusVisits = 0;
        $focusNext   = [];
        $focusPrev   = [];

        foreach ($sessions as $session) {
            $n = count($session);
            $pageviews += $n;
            $bounces += $n === 1 ? 1 : 0;

            $entries[$session[0]]    = ($entries[$session[0]] ?? 0) + 1;
            $exits[$session[$n - 1]] = ($exits[$session[$n - 1]] ?? 0) + 1;
            $depth[$n >= 5 ? '5+' : (string) $n]++;

            for ($i = 0; $i < $n - 1; $i++) {
                $key         = $session[$i] . "\0" . $session[$i + 1];
                $trans[$key] = ($trans[$key] ?? 0) + 1;
            }

            if ($n > 1) {
                $label         = implode(' → ', array_slice($session, 0, self::PATH_STEPS)) . ($n > self::PATH_STEPS ? ' → …' : '');
                $paths[$label] = ($paths[$label] ?? 0) + 1;
            }

            if ($focusUri === null) {
                continue;
            }

            foreach ($session as $i => $uri) {
                if ($uri !== $focusUri) {
                    continue;
                }
                $focusVisits++;
                $next = $session[$i + 1] ?? self::EXIT;
                $prev = $session[$i - 1] ?? self::ENTRY;

                $focusNext[$next] = ($focusNext[$next] ?? 0) + 1;
                $focusPrev[$prev] = ($focusPrev[$prev] ?? 0) + 1;
            }
        }

        $count = count($sessions);

        return [
            'sessions'    => $count,
            'pageviews'   => $pageviews,
            'avgPages'    => $count === 0 ? 0.0 : round($pageviews / $count, 2),
            'bounceRate'  => $count === 0 ? 0.0 : round($bounces / $count * 100, 1),
            'entries'     => self::top($entries, $limit),
            'exits'       => self::top($exits, $limit),
            'transitions' => self::top(
                array_combine(
                    array_map(static fn (string $k): string => str_replace("\0", ' → ', $k), array_keys($trans)),
                    $trans,
                ) ?: [],
                $limit,
            ),
            'paths' => self::top($paths, $limit),
            'depth' => array_map(
                static fn (string $label, int $c): array => ['label' => $label, 'count' => $c],
                array_keys($depth),
                $depth,
            ),
            'focus' => $focusUri === null ? null : [
                'uri'      => $focusUri,
                'visits'   => $focusVisits,
                'next'     => self::top($focusNext, $limit),
                'previous' => self::top($focusPrev, $limit),
                'exitRate' => $focusVisits === 0 ? 0.0 : round(($focusNext[self::EXIT] ?? 0) / $focusVisits * 100, 1),
            ],
        ];
    }

    /**
     * @param array<string, int> $counts
     *
     * @return Counted
     */
    private static function top(array $counts, int $limit): array
    {
        arsort($counts);

        $result = [];

        foreach (array_slice($counts, 0, $limit, true) as $label => $count) {
            $result[] = ['label' => (string) $label, 'count' => $count];
        }

        return $result;
    }
}
