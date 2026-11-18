<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\BackupManager;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

/**
 * @internal
 */
final class BackupManagerJobTest extends CIUnitTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/aicopia-backup-job-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0750, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $path) {
            unlink($path);
        }
        @rmdir($this->directory);
        parent::tearDown();
    }

    public function testQueueWritesStatusAndPreventsConcurrentBackup(): void
    {
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');

        $manager->queue();

        $this->assertSame('queued', $manager->jobStatus()['status']);
        $this->expectException(RuntimeException::class);
        $manager->queue();
    }

    public function testFailedQueuedJobReleasesLockForRetry(): void
    {
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');
        $manager->queue();
        $manager->failQueuedJob();

        $this->assertSame('failed', $manager->jobStatus()['status']);
        $manager->queue();
        $this->assertSame('queued', $manager->jobStatus()['status']);
    }

    public function testDeadWorkerIsMarkedFailedAndCanBeQueuedAgain(): void
    {
        file_put_contents($this->directory . '/.backup-create.lock', json_encode(['queued_at' => date(DATE_ATOM)]));
        file_put_contents($this->directory . '/backup-create-status.json', json_encode([
            'status'     => 'running',
            'updated_at' => date(DATE_ATOM),
            'pid'        => 99999999,
        ]));
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');

        if (! function_exists('posix_kill')) {
            $this->markTestSkipped('POSIX 프로세스 확인을 지원하지 않는 환경입니다.');
        }

        $this->assertSame('failed', $manager->jobStatus()['status']);
        $manager->queue();
        $this->assertSame('queued', $manager->jobStatus()['status']);
    }

    public function testLiveWorkerIsNotRecovered(): void
    {
        file_put_contents($this->directory . '/.backup-create.lock', json_encode(['queued_at' => date(DATE_ATOM)]));
        file_put_contents($this->directory . '/backup-create-status.json', json_encode([
            'status'     => 'running',
            'updated_at' => date(DATE_ATOM),
            'pid'        => getmypid(),
        ]));
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');

        $this->assertSame('running', $manager->jobStatus()['status']);
        $this->assertFileExists($this->directory . '/.backup-create.lock');
    }

    public function testOverdueQueuedJobIsMarkedFailedAndCanBeQueuedAgain(): void
    {
        $overdueAt = date(DATE_ATOM, time() - 301);
        file_put_contents($this->directory . '/.backup-create.lock', json_encode(['queued_at' => $overdueAt]));
        file_put_contents($this->directory . '/backup-create-status.json', json_encode([
            'status'     => 'queued',
            'updated_at' => $overdueAt,
        ]));
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');

        $this->assertSame('failed', $manager->jobStatus()['status']);
        $manager->queue();
        $this->assertSame('queued', $manager->jobStatus()['status']);
    }

    public function testPathRejectsTraversalAndForeignNames(): void
    {
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');

        foreach (['../x.zip', 'other.zip', 'aicopia-backup-1.zip/../../x'] as $name) {
            try {
                $manager->path($name);
                $this->fail('잘못된 파일명이 허용됨: ' . $name);
            } catch (RuntimeException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testRestoreRejectsArchiveWithoutManifest(): void
    {
        $archive = $this->directory . '/bad.zip';
        $zip     = new \ZipArchive();
        $zip->open($archive, \ZipArchive::CREATE);
        $zip->addFromString('database.sql', 'SELECT 1;');
        $zip->close();
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');

        $this->expectException(RuntimeException::class);
        $manager->restore($archive);
    }

    public function testRestoreRejectsPathTraversalEntry(): void
    {
        $archive = $this->directory . '/evil.zip';
        $zip     = new \ZipArchive();
        $zip->open($archive, \ZipArchive::CREATE);
        $zip->addFromString('database.sql', 'SELECT 1;');
        $zip->addFromString('manifest.json', '{"format":"aicopia-backup","version":1}');
        $zip->addFromString('uploads/../evil.php', 'x');
        $zip->close();
        $manager = new BackupManager([], $this->directory, $this->directory . '/uploads');

        $this->expectException(RuntimeException::class);
        $manager->restore($archive);
    }
}
