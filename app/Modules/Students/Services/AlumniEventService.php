<?php

namespace App\Modules\Students\Services;

use App\Modules\Students\Models\AlumniEvent;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CI Alumni_model event CRUD (add_event / getevents / delete_event).
 * Deferred: FullCalendar AJAX, mail/SMS fan-out, SaaS storage quota.
 */
class AlumniEventService
{
    /**
     * @return Collection<int, object>
     */
    public function listEvents(): Collection
    {
        $rows = AlumniEvent::query()
            ->orderByDesc('from_date')
            ->get();

        return $rows->map(function (AlumniEvent $event) {
            $className = '';
            $sessionName = '';
            $sectionLabels = [];

            if ($event->event_for === 'class' && $event->class_id) {
                $className = (string) DB::table('classes')->where('id', $event->class_id)->value('class');
                $sessionName = (string) DB::table('sessions')->where('id', $event->session_id)->value('session');
                $sectionIds = json_decode((string) $event->section, true);
                if (is_array($sectionIds) && $sectionIds !== []) {
                    $sectionLabels = DB::table('sections')
                        ->whereIn('id', $sectionIds)
                        ->orderBy('section')
                        ->pluck('section')
                        ->all();
                }
            }

            return (object) [
                'id' => $event->id,
                'title' => $event->title,
                'event_for' => $event->event_for,
                'session_id' => $event->session_id,
                'class_id' => $event->class_id,
                'section' => $event->section,
                'from_date' => $event->from_date,
                'to_date' => $event->to_date,
                'note' => $event->note,
                'photo' => $event->photo,
                'event_notification_message' => $event->event_notification_message,
                'class_name' => $className,
                'session_name' => $sessionName,
                'section_labels' => $sectionLabels,
            ];
        });
    }

    public function find(int $id): AlumniEvent
    {
        return AlumniEvent::query()->findOrFail($id);
    }

    /**
     * @param  array{
     *   event_title:string,
     *   event_for:string,
     *   session_id?:mixed,
     *   class_id?:mixed,
     *   user?:list<int|string>|null,
     *   from_date:string,
     *   to_date:string,
     *   note?:string|null,
     *   event_notification_message?:string|null
     * }  $data
     */
    public function save(?AlumniEvent $existing, array $data, ?UploadedFile $photo = null): AlumniEvent
    {
        $eventFor = (string) $data['event_for'];
        $sessionId = null;
        $classId = null;
        $sections = [];

        if ($eventFor === 'class') {
            $sessionId = (int) ($data['session_id'] ?? 0) ?: null;
            $classId = (int) ($data['class_id'] ?? 0) ?: null;
            $sections = array_values(array_map('intval', $data['user'] ?? []));
        }

        $from = Carbon::parse((string) $data['from_date'])->startOfDay()->format('Y-m-d H:i:s');
        $to = Carbon::parse((string) $data['to_date'])->setTime(23, 59, 0)->format('Y-m-d H:i:s');

        $photoName = $existing?->photo ?? '';
        if ($photo !== null) {
            $photoName = $this->storePhoto($photo, $existing?->photo);
        }

        $payload = [
            'title' => (string) $data['event_title'],
            'event_for' => $eventFor,
            'session_id' => $sessionId,
            'class_id' => $classId,
            'section' => json_encode($sections),
            'from_date' => $from,
            'to_date' => $to,
            'note' => (string) ($data['note'] ?? ''),
            'photo' => (string) $photoName,
            'is_active' => 0,
            'event_notification_message' => (string) ($data['event_notification_message'] ?? ''),
            'show_onwebsite' => (int) ($existing->show_onwebsite ?? 0),
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing;
        }

        return AlumniEvent::query()->create($payload);
    }

    public function delete(AlumniEvent $event): void
    {
        $this->deletePhotoFile((string) $event->photo);
        $event->delete();
    }

    public function formatDate(mixed $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse((string) $value)->format('d/m/Y');
    }

    /**
     * @return list<object{section_id:int,section:string}>
     */
    public function sectionsForClass(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        return DB::table('class_sections')
            ->join('sections', 'sections.id', '=', 'class_sections.section_id')
            ->where('class_sections.class_id', $classId)
            ->orderBy('sections.section')
            ->select(['sections.id as section_id', 'sections.section'])
            ->get()
            ->all();
    }

    protected function storePhoto(UploadedFile $file, ?string $previous): string
    {
        $dir = public_path('uploads/alumni_event_images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = 'event_'.uniqid('', true).'.'.$ext;
        $file->move($dir, $name);

        if ($previous) {
            $this->deletePhotoFile($previous);
        }

        return $name;
    }

    protected function deletePhotoFile(string $name): void
    {
        $safe = basename($name);
        if ($safe === '' || $safe !== $name) {
            return;
        }
        $path = public_path('uploads/alumni_event_images/'.$safe);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
