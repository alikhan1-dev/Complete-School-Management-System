<?php

namespace App\Modules\Settings\Services;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Settings\Models\SchSetting;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;

/**
 * CI setting_model::getSetting / add for Schsettings general setting persist.
 * Deferred: logos, miscellaneous, theme, mobile app, fees flags, IDs, attendance, maintenance, WhatsApp, chat, Drive, SaaS quota.
 */
class SchoolGeneralSettingService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function current(): ?SchSetting
    {
        $row = SchSetting::query()->orderBy('id')->first();
        if ($row === null) {
            return null;
        }

        if ((string) $row->base_url === '') {
            $row->base_url = url('/');
        }
        if ((string) $row->folder_path === '') {
            $row->folder_path = base_path().DIRECTORY_SEPARATOR;
        }

        return $row;
    }

    /**
     * @return list<array{id:int,session:string}>
     */
    public function sessions(): array
    {
        return AcademicSession::query()
            ->orderBy('id')
            ->get(['id', 'session'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'session' => (string) $row->session])
            ->all();
    }

    /**
     * Columns written by CI Schsettings::generalsetting only.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveGeneral(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $row = SchSetting::query()->where('id', $id)->first()
            ?? SchSetting::query()->orderBy('id')->first();

        if ($row === null) {
            throw new \RuntimeException('School settings row was not found.');
        }

        $row->session_id = (int) $data['session_id'];
        $row->name = (string) $data['name'];
        $row->phone = (string) $data['phone'];
        $row->dise_code = (string) ($data['dise_code'] ?? '');
        $row->start_month = (string) $data['start_month'];
        $row->start_week = (string) $data['start_week'];
        $row->address = (string) $data['address'];
        $row->email = (string) $data['email'];
        $row->timezone = (string) $data['timezone'];
        $row->date_format = (string) $data['date_format'];
        $row->currency_format = (string) $data['currency_format'];
        $row->currency_place = (string) $data['currency_place'];
        $row->base_url = (string) $data['base_url'];
        $row->folder_path = (string) $data['folder_path'];
        $row->save();

        $this->school->clearCache();
    }

    /**
     * CI setting_model::getSetting (joined row + activelanguage2).
     */
    public function getSettingPayload(): ?object
    {
        $result = DB::table('sch_settings')
            ->select([
                'sch_settings.*',
                'sessions.session',
                'languages.language',
                'languages.short_code as language_code',
                'sch_settings.languages as activelanguage',
                'currencies.symbol as currency_symbol',
                'currencies.base_price',
                'currencies.short_name as currency',
                'currencies.id as currency_id',
            ])
            ->join('sessions', 'sessions.id', '=', 'sch_settings.session_id')
            ->join('languages', 'languages.id', '=', 'sch_settings.lang_id')
            ->join('currencies', 'currencies.id', '=', 'sch_settings.currency')
            ->orderBy('sch_settings.id')
            ->first();

        if ($result === null) {
            return null;
        }

        $language = [];
        $decoded = json_decode((string) $result->activelanguage, true);
        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                $lang = DB::table('languages')->where('id', $value)->first();
                if ($lang !== null) {
                    $language[$key] = $lang;
                }
            }
        }
        $result->activelanguage2 = $language;

        return $result;
    }
}
