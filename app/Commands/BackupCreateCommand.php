<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\BackupManager;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

final class BackupCreateCommand extends BaseCommand
{
    protected $group       = 'Backup';
    protected $name        = 'backup:create';
    protected $description = '예약된 관리자 백업 생성 작업을 실행합니다.';

    public function run(array $params): void
    {
        try {
            $backup = $this->manager()->runQueuedJob();
            CLI::write("백업 파일 {$backup['filename']}을 만들었습니다.", 'green');
        } catch (Throwable $exception) {
            CLI::error($exception->getMessage());
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
}
