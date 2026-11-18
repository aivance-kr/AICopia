<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Database\MySQLiTimezone\Connection as TimezoneAwareConnection;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * DB와 public/uploads를 하나의 이식 가능한 ZIP 백업으로 관리한다.
 */
final class BackupManager
{
    private const string DATABASE_FILE   = 'database.sql';
    private const string MANIFEST_FILE   = 'manifest.json';
    private const int MAX_ARCHIVE_SIZE   = 1073741824;
    private const int MAX_EXTRACTED_SIZE = 5368709120;
    private const int MAX_ENTRIES        = 10000;
    private const int QUEUED_JOB_TIMEOUT = 300;
    private const string JOB_LOCK_FILE   = '.backup-create.lock';
    private const string JOB_STATUS_FILE = 'backup-create-status.json';

    public function __construct(
        /**
         * @var array<string, mixed>
         */
        private readonly array $databaseConfig,
        private readonly string $backupDirectory = WRITEPATH . 'backups',
        private readonly string $uploadsDirectory = FCPATH . 'uploads',
    ) {
    }

    /**
     * @return array{filename: string, size: int, created_at: string}
     */
    public function create(): array
    {
        $this->ensureDirectory($this->backupDirectory);
        $temporary   = $this->temporaryDirectory();
        $sqlPath     = $temporary . '/' . self::DATABASE_FILE;
        $filename    = 'aicopia-backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.zip';
        $archivePath = $this->backupDirectory . '/' . $filename;

        try {
            $this->dumpDatabase($sqlPath);

            $zip = new ZipArchive();
            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('백업 ZIP 파일을 만들 수 없습니다.');
            }

            $zip->addFile($sqlPath, self::DATABASE_FILE);
            $this->addDirectory($zip, $this->uploadsDirectory, 'uploads');
            $manifest = ['format' => 'aicopia-backup', 'version' => 1, 'created_at' => date(DATE_ATOM)];
            $zip->addFromString(self::MANIFEST_FILE, json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $zip->close();

            return ['filename' => $filename, 'size' => filesize($archivePath) ?: 0, 'created_at' => $manifest['created_at']];
        } catch (Throwable $exception) {
            @unlink($archivePath);

            throw $exception;
        } finally {
            $this->deleteDirectory($temporary);
        }
    }

    /**
     * 백업 생성 작업을 하나만 예약한다.
     *
     * @throws RuntimeException
     */
    public function queue(): void
    {
        $this->ensureDirectory($this->backupDirectory);
        $this->recoverInterruptedJob();
        $lock = @fopen($this->jobLockPath(), 'xb');
        if ($lock === false) {
            throw new RuntimeException('백업 생성 작업이 이미 진행 중입니다.');
        }
        $queuedAt = date(DATE_ATOM);
        fwrite($lock, json_encode(['queued_at' => $queuedAt], JSON_THROW_ON_ERROR));
        fclose($lock);
        $this->writeJobStatus(['status' => 'queued', 'updated_at' => $queuedAt]);
    }

    /**
     * @return array{status: string, updated_at: string, backup?: array{filename: string, size: int, created_at: string}}|null
     */
    public function jobStatus(): ?array
    {
        $this->recoverInterruptedJob();

        return $this->readJobStatus();
    }

    /**
     * @return array{filename: string, size: int, created_at: string}
     */
    public function runQueuedJob(): array
    {
        $this->recoverInterruptedJob();
        if (! is_file($this->jobLockPath())) {
            throw new RuntimeException('예약된 백업 생성 작업이 없습니다.');
        }

        try {
            $this->writeJobStatus(['status' => 'running', 'updated_at' => date(DATE_ATOM), 'pid' => getmypid()]);
            $backup = $this->create();
            $this->writeJobStatus(['status' => 'completed', 'updated_at' => date(DATE_ATOM), 'backup' => $backup]);

            return $backup;
        } catch (Throwable $exception) {
            log_message('error', '비동기 백업 생성 실패: {message}', ['message' => $exception->getMessage()]);
            $this->writeJobStatus(['status' => 'failed', 'updated_at' => date(DATE_ATOM)]);

            throw $exception;
        } finally {
            @unlink($this->jobLockPath());
        }
    }

    public function failQueuedJob(): void
    {
        $this->writeJobStatus(['status' => 'failed', 'updated_at' => date(DATE_ATOM)]);
        @unlink($this->jobLockPath());
    }

    /**
     * @return list<array{filename: string, size: int, modified_at: int}>
     */
    public function list(): array
    {
        $this->ensureDirectory($this->backupDirectory);
        $backups = [];

        foreach (glob($this->backupDirectory . '/*.zip') ?: [] as $path) {
            $backups[] = ['filename' => basename($path), 'size' => filesize($path) ?: 0, 'modified_at' => filemtime($path) ?: 0];
        }
        usort($backups, static fn (array $left, array $right): int => $right['modified_at'] <=> $left['modified_at']);

        return $backups;
    }

    public function path(string $filename): string
    {
        if (! preg_match('/\Aaicopia-backup-[a-zA-Z0-9-]+\.zip\z/', $filename)) {
            throw new RuntimeException('잘못된 백업 파일명입니다.');
        }
        $path = $this->backupDirectory . '/' . $filename;
        if (! is_file($path)) {
            throw new RuntimeException('백업 파일을 찾을 수 없습니다.');
        }

        return $path;
    }

    public function restore(string $archivePath): void
    {
        $this->validateArchive($archivePath);
        // 실패 시 되돌릴 기준점을 먼저 남긴다. DB와 파일을 한 트랜잭션으로 묶을 수 없기 때문이다.
        $this->create();
        $temporary = $this->temporaryDirectory();

        try {
            $zip = new ZipArchive();
            $zip->open($archivePath);
            $zip->extractTo($temporary);
            $zip->close();

            $this->importDatabase($temporary . '/' . self::DATABASE_FILE);
            $stagedUploads = $temporary . '/uploads';
            if (! is_dir($stagedUploads)) {
                mkdir($stagedUploads, 0755, true);
            }
            $previousUploads = $this->uploadsDirectory . '.restore-' . bin2hex(random_bytes(4));
            if (is_dir($this->uploadsDirectory) && ! rename($this->uploadsDirectory, $previousUploads)) {
                throw new RuntimeException('기존 업로드 파일을 교체 준비할 수 없습니다.');
            }
            if (! rename($stagedUploads, $this->uploadsDirectory)) {
                if (is_dir($previousUploads)) {
                    rename($previousUploads, $this->uploadsDirectory);
                }

                throw new RuntimeException('복원한 업로드 파일을 적용할 수 없습니다.');
            }
            $this->deleteDirectory($previousUploads);
        } finally {
            $this->deleteDirectory($temporary);
        }
    }

    private function dumpDatabase(string $destination): void
    {
        $this->run(['mysqldump', '--single-transaction', '--routines', '--triggers', '--add-drop-table', ...$this->databaseArguments()], $destination, null);
    }

    private function importDatabase(string $source): void
    {
        $this->run(['mysql', ...$this->databaseArguments()], null, $source);
    }

    /**
     * @return list<string>
     */
    private function databaseArguments(): array
    {
        $config = $this->databaseConfig;
        if (! in_array($config['DBDriver'] ?? '', ['MySQLi', TimezoneAwareConnection::DRIVER], true)) {
            throw new RuntimeException('MySQLi 데이터베이스에서만 백업 복원을 지원합니다.');
        }

        return ['--host=' . (string) $config['hostname'], '--port=' . (string) $config['port'], '--user=' . (string) $config['username'], (string) $config['database']];
    }

    /**
     * @param list<string> $command
     */
    private function run(array $command, ?string $outputPath, ?string $inputPath): void
    {
        $config  = $this->databaseConfig;
        $pipes   = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, null, [...getenv(), 'MYSQL_PWD' => (string) $config['password']]);
        if (! is_resource($process)) {
            throw new RuntimeException('MySQL 백업 도구를 실행할 수 없습니다.');
        }
        if ($inputPath !== null) {
            stream_copy_to_stream(fopen($inputPath, 'rb'), $pipes[0]);
        }
        fclose($pipes[0]);
        if ($outputPath !== null) {
            stream_copy_to_stream($pipes[1], fopen($outputPath, 'wb'));
        }
        fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new RuntimeException('MySQL 작업에 실패했습니다: ' . trim($error));
        }
    }

    private function validateArchive(string $archivePath): void
    {
        if (! is_file($archivePath) || (filesize($archivePath) ?: 0) > self::MAX_ARCHIVE_SIZE) {
            throw new RuntimeException('허용되지 않는 백업 파일 크기입니다.');
        }
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true || $zip->numFiles > self::MAX_ENTRIES) {
            throw new RuntimeException('유효하지 않은 백업 ZIP 파일입니다.');
        }
        $total       = 0;
        $hasDatabase = false;
        $hasManifest = false;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = (string) ($stat['name'] ?? '');
            $mode = ((int) ($stat['external_attributes'] ?? 0) >> 16) & 0170000;
            if ($mode === 0120000) {
                $zip->close();

                throw new RuntimeException('심볼릭 링크가 포함된 백업 파일은 복원할 수 없습니다.');
            }
            if ($name === self::DATABASE_FILE) {
                $hasDatabase = true;
            } elseif ($name === self::MANIFEST_FILE) {
                $hasManifest = true;
            } elseif (! str_starts_with($name, 'uploads/')) {
                $zip->close();

                throw new RuntimeException('허용되지 않는 백업 파일 구성이 포함되어 있습니다.');
            }
            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\')) {
                $zip->close();

                throw new RuntimeException('위험한 경로가 포함된 백업 파일입니다.');
            }
            $total += (int) ($stat['size'] ?? 0);
            if ($total > self::MAX_EXTRACTED_SIZE) {
                $zip->close();

                throw new RuntimeException('압축 해제 크기 제한을 초과했습니다.');
            }
        }
        $zip->close();
        if (! $hasDatabase || ! $hasManifest) {
            throw new RuntimeException('AICopia 백업 형식이 아닙니다.');
        }
        $zip = new ZipArchive();
        $zip->open($archivePath);
        $manifest = json_decode((string) $zip->getFromName(self::MANIFEST_FILE), true);
        $zip->close();
        if (! is_array($manifest) || ($manifest['format'] ?? null) !== 'aicopia-backup' || ($manifest['version'] ?? null) !== 1) {
            throw new RuntimeException('AICopia 백업 형식이 아닙니다.');
        }
    }

    private function addDirectory(ZipArchive $zip, string $directory, string $prefix): void
    {
        if (! is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), $prefix . '/' . substr($file->getPathname(), strlen($directory) + 1));
            }
        }
    }

    private function temporaryDirectory(): string
    {
        $directory = WRITEPATH . 'backups-tmp/' . bin2hex(random_bytes(12));
        $this->ensureDirectory($directory);

        return $directory;
    }

    private function jobLockPath(): string
    {
        return $this->backupDirectory . '/' . self::JOB_LOCK_FILE;
    }

    private function jobStatusPath(): string
    {
        return $this->backupDirectory . '/' . self::JOB_STATUS_FILE;
    }

    /**
     * 서버 또는 worker가 중단된 뒤 남은 작업 잠금을 해제한다.
     */
    private function recoverInterruptedJob(): void
    {
        if (! is_file($this->jobLockPath())) {
            return;
        }

        $status = $this->readJobStatus();
        $state  = $status['status'] ?? null;

        if (in_array($state, ['completed', 'failed'], true)) {
            @unlink($this->jobLockPath());

            return;
        }

        if ($state === 'running' && isset($status['pid']) && is_int($status['pid'])) {
            $alive = $this->isProcessAlive($status['pid']);
            if ($alive === false) {
                $this->failInterruptedJob();
            }

            return;
        }

        if ($this->isJobOverdue($status)) {
            $this->failInterruptedJob();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJobStatus(): ?array
    {
        $path = $this->jobStatusPath();
        if (! is_file($path)) {
            return null;
        }

        $status = json_decode((string) file_get_contents($path), true);
        if (! is_array($status) || ! isset($status['status'], $status['updated_at']) || ! is_string($status['status']) || ! is_string($status['updated_at'])) {
            return null;
        }

        return $status;
    }

    /**
     * @param array<string, mixed>|null $status
     */
    private function isJobOverdue(?array $status): bool
    {
        $updatedAt = is_array($status) ? ($status['updated_at'] ?? null) : null;
        $timestamp = is_string($updatedAt) ? strtotime($updatedAt) : false;
        if ($timestamp === false) {
            $timestamp = $this->lockQueuedAt();
        }
        if ($timestamp === false) {
            $timestamp = filemtime($this->jobLockPath());
        }

        return $timestamp !== false && $timestamp <= time() - self::QUEUED_JOB_TIMEOUT;
    }

    private function lockQueuedAt(): false|int
    {
        $lock = json_decode((string) file_get_contents($this->jobLockPath()), true);
        if (! is_array($lock) || ! isset($lock['queued_at']) || ! is_string($lock['queued_at'])) {
            return false;
        }

        return strtotime($lock['queued_at']);
    }

    private function isProcessAlive(int $pid): ?bool
    {
        if ($pid <= 0 || ! function_exists('posix_kill')) {
            return null;
        }

        return posix_kill($pid, 0);
    }

    private function failInterruptedJob(): void
    {
        $this->writeJobStatus(['status' => 'failed', 'updated_at' => date(DATE_ATOM)]);
        @unlink($this->jobLockPath());
    }

    /**
     * @param array<string, mixed> $status
     */
    private function writeJobStatus(array $status): void
    {
        $this->ensureDirectory($this->backupDirectory);
        $temporary = $this->jobStatusPath() . '.' . bin2hex(random_bytes(4));
        file_put_contents($temporary, json_encode($status, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), LOCK_EX);
        rename($temporary, $this->jobStatusPath());
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException('백업 저장소를 만들 수 없습니다.');
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
