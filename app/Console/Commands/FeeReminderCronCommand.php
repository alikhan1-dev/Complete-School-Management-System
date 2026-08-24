<?php

namespace App\Console\Commands;

use App\Modules\Fees\Services\FeeReminderCronService;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * CI Cron::feereminder/{key} — fee due reminders (persist only; live send deferred).
 */
class FeeReminderCronCommand extends Command
{
    protected $signature = 'school:fee-reminder {key : sch_settings.cron_secret_key} {--date= : Override Y-m-d "today" for tests}';

    protected $description = 'Queue fees_reminder notifications for before/after due-date rules (live send deferred)';

    public function handle(FeeReminderCronService $cron): int
    {
        $key = (string) $this->argument('key');
        $date = $this->option('date');
        $date = is_string($date) && $date !== '' ? $date : null;

        try {
            $result = $cron->run($key, $date);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Fee reminder cron: rules=%d candidates=%d queued=%d accepted=%d (live send deferred)',
            $result['reminder_rules'],
            $result['candidates'],
            $result['queued'],
            $result['accepted']
        ));

        return self::SUCCESS;
    }
}
