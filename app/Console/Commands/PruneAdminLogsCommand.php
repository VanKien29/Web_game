<?php

namespace App\Console\Commands;

use App\Models\AdminActionLog;
use Illuminate\Console\Command;

class PruneAdminLogsCommand extends Command
{
    protected $signature = 'admin-logs:prune
                            {--days= : Xóa log hoàn toàn sau số ngày, mặc định theo ADMIN_LOG_RETENTION_DAYS}
                            {--strip-days= : Xóa payload chi tiết sau số ngày, mặc định theo ADMIN_LOG_DETAIL_RETENTION_DAYS}
                            {--chunk= : Số bản ghi xử lý mỗi lượt}
                            {--dry-run : Chỉ thống kê, không thay đổi dữ liệu}';

    protected $description = 'Thu gọn payload và xóa admin action logs quá hạn';

    public function handle(): int
    {
        $retentionDays = max(1, (int) ($this->option('days') ?: config('admin_logs.retention_days', 90)));
        $stripDays = max(1, (int) ($this->option('strip-days') ?: config('admin_logs.detail_retention_days', 30)));
        $chunk = max(100, (int) ($this->option('chunk') ?: config('admin_logs.prune_chunk', 1000)));

        if ($stripDays > $retentionDays) {
            $this->error('--strip-days phải nhỏ hơn hoặc bằng --days.');

            return self::INVALID;
        }

        $stripBefore = now()->subDays($stripDays);
        $deleteBefore = now()->subDays($retentionDays);
        $stripQuery = AdminActionLog::query()
            ->where('created_at', '<', $stripBefore)
            ->where(function ($query) {
                $query->whereNotNull('before_state')
                    ->orWhereNotNull('after_state')
                    ->orWhereNotNull('meta');
            });
        $deleteQuery = AdminActionLog::query()->where('created_at', '<', $deleteBefore);

        $stripCount = (clone $stripQuery)->count();
        $deleteCount = (clone $deleteQuery)->count();
        $this->info("Payload cần thu gọn: {$stripCount}; log cần xóa: {$deleteCount}.");

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $stripped = $this->processInChunks($stripQuery, $chunk, function (array $ids): int {
            return AdminActionLog::query()
                ->whereIn('id', $ids)
                ->update([
                    'before_state' => null,
                    'after_state' => null,
                    'meta' => null,
                ]);
        });

        $deleted = $this->processInChunks($deleteQuery, $chunk, function (array $ids): int {
            return AdminActionLog::query()->whereIn('id', $ids)->delete();
        });

        $this->info("Đã thu gọn {$stripped} log và xóa {$deleted} log.");

        return self::SUCCESS;
    }

    private function processInChunks($query, int $chunk, callable $processor): int
    {
        $processed = 0;

        while (true) {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();

            if (!$ids) {
                break;
            }

            $processed += $processor($ids);
        }

        return $processed;
    }
}
