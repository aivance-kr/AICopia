<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\PathAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PathAnalyzerTest extends TestCase
{
    /**
     * @return array{uri: string, ip_address: string, user_agent: string|null, created_at: string}
     */
    private function visit(string $ip, string $time, string $uri, string $ua = 'UA'): array
    {
        return ['uri' => $uri, 'ip_address' => $ip, 'user_agent' => $ua, 'created_at' => "2026-11-01 {$time}"];
    }

    public function testSplitsSessionsByInactivityGapAndCollapsesRefresh(): void
    {
        $sessions = PathAnalyzer::buildSessions([
            $this->visit('1.1.1.1', '10:03:00', '/b'),
            $this->visit('1.1.1.1', '10:00:00', '/a'),
            $this->visit('1.1.1.1', '10:03:30', '/b'), // 새로고침
            $this->visit('1.1.1.1', '11:00:00', '/c'), // 30분 넘게 비어 새 세션
            $this->visit('2.2.2.2', '10:00:00', '/a'),
            $this->visit('2.2.2.2', '10:00:10', '/x', 'OtherUA'), // 다른 UA 는 다른 방문자
        ]);

        $this->assertCount(4, $sessions);
        $this->assertContains(['/a', '/b'], $sessions);
        $this->assertContains(['/c'], $sessions);
    }

    public function testAnalyzeComputesEntriesExitsTransitionsAndBounce(): void
    {
        $r = PathAnalyzer::analyze([
            ['/', '/board/x/1', '/contact'],
            ['/', '/board/x/1'],
            ['/board/x/1'],
        ]);

        $this->assertSame(3, $r['sessions']);
        $this->assertSame(6, $r['pageviews']);
        $this->assertSame(2.0, $r['avgPages']);
        $this->assertSame(33.3, $r['bounceRate']);
        $this->assertSame(['label' => '/', 'count' => 2], $r['entries'][0]);
        $this->assertSame(['label' => '/board/x/1', 'count' => 2], $r['exits'][0]);
        $this->assertSame(['label' => '/ → /board/x/1', 'count' => 2], $r['transitions'][0]);
        $this->assertNull($r['focus']);
    }

    public function testFocusShowsPreviousNextAndExitRate(): void
    {
        $r = PathAnalyzer::analyze([
            ['/', '/p', '/contact'],
            ['/', '/p'],
            ['/p'],
        ], '/p');

        $focus = $r['focus'];
        $this->assertNotNull($focus);
        $this->assertSame(3, $focus['visits']);
        $this->assertSame(66.7, $focus['exitRate']);
        $this->assertSame(['label' => PathAnalyzer::EXIT, 'count' => 2], $focus['next'][0]);
        $this->assertSame(['label' => '/', 'count' => 2], $focus['previous'][0]);
    }

    public function testEmptyInput(): void
    {
        $r = PathAnalyzer::analyze([]);

        $this->assertSame(0, $r['sessions']);
        $this->assertSame(0.0, $r['bounceRate']);
        $this->assertSame([], $r['entries']);
    }
}
