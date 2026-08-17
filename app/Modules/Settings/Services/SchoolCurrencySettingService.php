<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\Currency;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CI admin/Currency + Currency_model.
 */
class SchoolCurrencySettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function schoolSetting(): ?object
    {
        return DB::table('sch_settings')
            ->select([
                'sch_settings.id',
                'sch_settings.currency',
                'sch_settings.currency_format',
            ])
            ->orderBy('sch_settings.id')
            ->first();
    }

    /**
     * CI Currency_model::get() — left join so only the school default row has currency_id.
     *
     * @return list<object>
     */
    public function listCurrencies(): array
    {
        return DB::table('currencies')
            ->select([
                'currencies.*',
                DB::raw('IFNULL(sch_settings.currency, 0) as currency_id'),
            ])
            ->leftJoin('sch_settings', 'currencies.id', '=', 'sch_settings.currency')
            ->orderBy('currencies.id')
            ->get()
            ->all();
    }

    public function find(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('currencies')
            ->select([
                'currencies.*',
                DB::raw('IFNULL(sch_settings.currency, 0) as currency_id'),
            ])
            ->leftJoin('sch_settings', 'currencies.id', '=', 'sch_settings.currency')
            ->where('currencies.id', $id)
            ->first();
    }

    /**
     * CI Currency_model::add — update when id > 0, otherwise insert.
     *
     * @param  array<string, mixed>  $data
     */
    public function add(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        unset($data['id']);

        if ($id > 0) {
            Currency::query()->where('id', $id)->update($data);

            return;
        }

        Currency::query()->insert($data);
    }

    /**
     * CI Currency_model::update_currency.
     *
     * @param  array<string, mixed>  $settingData
     */
    public function updateSchoolCurrency(array $settingData): void
    {
        $id = (int) ($settingData['id'] ?? 0);
        unset($settingData['id']);

        SchSetting::query()->where('id', $id)->update($settingData);
        $this->school->clearCache();
    }

    public function updateStaffCurrency(int $staffId, int $currencyId): void
    {
        DB::table('staff')->where('id', $staffId)->update([
            'currency_id' => $currencyId,
        ]);
    }

    /**
     * CI amountFormat() for admin/currency/getAmountFormat.
     */
    public function formatPostedAmount(mixed $amount): ?string
    {
        $admin = session('admin', []);
        $format = (string) ($admin['currency_format'] ?? '');
        $price = $admin['currency_base_price'] ?? null;

        if ($format === '') {
            $format = (string) (DB::table('sch_settings')->orderBy('id')->value('currency_format') ?? '');
        }

        if ($price === null || $price === '') {
            $price = $this->staffOrSchoolBasePrice();
        }

        return $this->amountFormat($amount, $format, $price);
    }

    protected function staffOrSchoolBasePrice(): mixed
    {
        $staff = Auth::guard('staff')->user();
        $currencyId = (int) ($staff->currency_id ?? 0);
        if ($currencyId <= 0) {
            $currencyId = (int) (DB::table('sch_settings')->orderBy('id')->value('currency') ?? 0);
        }

        $price = DB::table('currencies')->where('id', $currencyId)->value('base_price');

        return $price === null || $price === '' ? 1 : $price;
    }

    /**
     * Preserve CI custom_helper::amountFormat formatting, including unmatched-format null.
     */
    public function amountFormat(mixed $amount, string $currencyFormat, mixed $currencyPrice): ?string
    {
        $calculatedAmount = ($amount * $currencyPrice);

        if ($currencyFormat === '#,###.##') {
            return number_format($calculatedAmount, 2, '.', ',');
        }
        if ($currencyFormat === '#.###,##') {
            return number_format($calculatedAmount, 2, ',', '.');
        }
        if ($currencyFormat === '# ###.##') {
            return number_format($calculatedAmount, 2, '.', ' ');
        }
        if ($currencyFormat === '#.###.##') {
            return number_format($calculatedAmount, 2, '.', '.');
        }
        if ($currencyFormat === '#,###.###') {
            return number_format($calculatedAmount, 3, '.', ',');
        }
        if ($currencyFormat === '####.##') {
            return number_format($calculatedAmount, 2, '.', '');
        }
        if ($currencyFormat === '#,##,###.##') {
            return $this->indianMoneyFormat($calculatedAmount);
        }

        return null;
    }

    /**
     * CI custom_helper::indian_money_format.
     */
    protected function indianMoneyFormat(mixed $num): string
    {
        $explrestunits = '';
        $num = preg_replace('/,+/', '', (string) $num);
        $words = explode('.', (string) $num);
        $des = '00';
        if (count($words) <= 2) {
            $num = $words[0];
            if (count($words) >= 2) {
                $des = $words[1];
            }
            if (strlen($des) < 2) {
                $des = "$des";
            } else {
                $des = substr($des, 0, 2);
            }
        }
        if (strlen((string) $num) > 3) {
            $lastthree = substr((string) $num, strlen((string) $num) - 3, 3);
            $restunits = substr((string) $num, 0, strlen((string) $num) - 3);
            $restunits = (strlen($restunits) % 2 === 1) ? '0'.$restunits : $restunits;
            $expunit = str_split($restunits, 2);
            for ($i = 0; $i < count($expunit); $i++) {
                if ($i === 0) {
                    $explrestunits .= (int) $expunit[$i].',';
                } else {
                    $explrestunits .= $expunit[$i].',';
                }
            }
            $thecash = $explrestunits.$lastthree;
        } else {
            $thecash = $num;
        }

        return "$thecash.$des";
    }
}
