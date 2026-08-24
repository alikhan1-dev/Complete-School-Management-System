<?php

namespace App\Modules\Fees\Services;

use App\Modules\Fees\Models\FeesReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CI Feereminder_model + admin/Feereminder::setting batch update.
 * Cron live mail/SMS send remains deferred (Communication).
 */
class FeeReminderService
{
    public function tableReady(): bool
    {
        return Schema::hasTable('fees_reminder');
    }

    /**
     * CI Feereminder_model::get() — ordered by id.
     *
     * @return list<FeesReminder>
     */
    public function list(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        return FeesReminder::query()
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * CI Feereminder_model::get(null, 1) — active rules for cron.
     *
     * @return list<FeesReminder>
     */
    public function activeList(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        return FeesReminder::query()
            ->where('is_active', 1)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * CI Feereminder::setting POST + Feereminder_model::updatebatch.
     *
     * @param  list<int|string>  $ids
     * @param  array<string, mixed>  $input  days{id}, isactive_{id}
     */
    public function updateBatch(array $ids, array $input): void
    {
        if (! $this->tableReady() || $ids === []) {
            return;
        }

        $rows = [];
        foreach ($ids as $rawId) {
            $id = (int) $rawId;
            if ($id <= 0) {
                continue;
            }

            $dayKey = 'days'.$id;
            $activeKey = 'isactive_'.$id;
            $day = isset($input[$dayKey]) && $input[$dayKey] !== ''
                ? (int) $input[$dayKey]
                : 0;
            $isActive = ! empty($input[$activeKey]) && (string) $input[$activeKey] !== '0' ? 1 : 0;

            $rows[] = [
                'id' => $id,
                'day' => $day,
                'is_active' => $isActive,
                'updated_at' => now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                FeesReminder::query()
                    ->where('id', $row['id'])
                    ->update([
                        'day' => $row['day'],
                        'is_active' => $row['is_active'],
                        'updated_at' => $row['updated_at'],
                    ]);
            }
        });
    }
}
