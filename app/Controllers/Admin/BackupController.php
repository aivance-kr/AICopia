<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\BackupManager;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

final class BackupController extends BaseController
{
    public function index(): string
    {
        $manager = $this->manager();

        return $this->render('admin/backup/index', ['backups' => $manager->list(), 'jobStatus' => $manager->jobStatus()]);
    }

    public function create(): ResponseInterface
    {
        try {
            $manager = $this->manager();
            $manager->queue();

            try {
                $this->launchWorker();
            } catch (RuntimeException $exception) {
                $manager->failQueuedJob();

                throw $exception;
            }

            return redirect()->to('/admin/backup')->with('success', '백업 생성을 시작했습니다. 완료될 때까지 이 페이지에서 상태를 확인하세요.');
        } catch (RuntimeException $exception) {
            return redirect()->to('/admin/backup')->with('error', $exception->getMessage());
        }
    }

    public function download(string $filename): ResponseInterface
    {
        try {
            $path = $this->manager()->path($filename);

            return $this->response->download($path, null)->setFileName($filename);
        } catch (RuntimeException $exception) {
            return redirect()->to('/admin/backup')->with('error', $exception->getMessage());
        }
    }

    public function restore(): ResponseInterface
    {
        if ($this->request->getPost('confirmation') !== '복원') {
            return redirect()->to('/admin/backup')->with('error', '복원 확인 문구가 일치하지 않습니다.');
        }
        $file = $this->request->getFile('backup_zip');
        if (! $file || ! $file->isValid() || $file->hasMoved() || strtolower($file->getClientExtension()) !== 'zip') {
            return redirect()->to('/admin/backup')->with('error', '유효한 ZIP 백업 파일을 선택하세요.');
        }

        try {
            $this->manager()->restore($file->getTempName());

            return redirect()->to('/admin/backup')->with('success', '복원이 완료되었습니다. 복원 직전 백업도 서버에 보관했습니다.');
        } catch (RuntimeException $exception) {
            return redirect()->to('/admin/backup')->with('error', $exception->getMessage());
        }
    }

    public function restoreFromServer(): ResponseInterface
    {
        if ($this->request->getPost('confirmation') !== '복원') {
            return redirect()->to('/admin/backup')->with('error', '복원 확인 문구가 일치하지 않습니다.');
        }

        try {
            $manager = $this->manager();
            $path    = $manager->path((string) $this->request->getPost('filename'));
            $manager->restore($path);

            return redirect()->to('/admin/backup')->with('success', '복원이 완료되었습니다. 복원 직전 백업도 서버에 보관했습니다.');
        } catch (RuntimeException $exception) {
            return redirect()->to('/admin/backup')->with('error', $exception->getMessage());
        }
    }

    private function manager(): BackupManager
    {
        $config = config('Database');
        $group  = $config->defaultGroup;
        /** @var array<string, mixed> $databaseConfig */
        $databaseConfig = $config->{$group};

        return new BackupManager($databaseConfig);
    }

    private function launchWorker(): void
    {
        $logPath = WRITEPATH . 'logs/backup-create.log';
        $command = implode(' ', [
            escapeshellarg($this->resolvePhpBinary()),
            escapeshellarg(ROOTPATH . 'spark'),
            'backup:create',
            '>',
            escapeshellarg($logPath),
            '2>&1',
            '&',
        ]);
        exec($command, $output, $exitCode);
        if ($exitCode !== 0) {
            throw new RuntimeException('백업 작업을 시작할 수 없습니다. 서버 PHP CLI 설정을 확인하세요.');
        }
    }

    /**
     * 웹 요청 SAPI에 따라 PHP_BINARY가 비어 있거나 실행 불가한 값일 수 있다(SAPI가
     * executable_location을 채우지 않는 경우). .env 설정값 → PHP_BINARY → PATH상의
     * php 순으로 검증해 실행 가능한 CLI 경로를 찾고, 없으면 백그라운드 실행을
     * 시도하기 전에 실패시킨다.
     */
    private function resolvePhpBinary(): string
    {
        $configured = trim((string) env('backup.phpBinary', ''));
        if ($configured !== '' && is_executable($configured)) {
            return $configured;
        }

        if (is_executable(PHP_BINARY) && ! str_contains(PHP_BINARY, 'fpm')) {
            return PHP_BINARY;
        }

        foreach (['php', 'php8.5', 'php8.4', 'php8.3'] as $candidate) {
            $resolved = trim((string) shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null'));
            if ($resolved !== '' && is_executable($resolved)) {
                return $resolved;
            }
        }

        throw new RuntimeException('실행 가능한 PHP CLI 바이너리를 찾을 수 없습니다. .env에 backup.phpBinary를 설정하세요.');
    }
}
