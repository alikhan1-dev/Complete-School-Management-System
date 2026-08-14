<?php

namespace App\Modules\Communication\Services;

use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Communication\Models\Message;
use App\Modules\Roles\Models\Role;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * CI Messages_model + admin/mailsms group email persist.
 * Live mailer/SMS send is deferred (same as notice board).
 */
class MailSmsService
{
    public function __construct(
        protected CurrentSessionResolver $session,
        protected SchoolContext $school,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listLog(): array
    {
        return Message::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Message $row) => $row->toArray())
            ->all();
    }

    /**
     * CI: delete where is_schedule != 1 AND sent = 1.
     */
    public function deleteSentLog(): int
    {
        return Message::query()
            ->where('is_schedule', '!=', 1)
            ->where('sent', 1)
            ->delete();
    }

    /**
     * CI messages_model->schedule('', 'schedule').
     *
     * @return list<array<string, mixed>>
     */
    public function listSchedule(): array
    {
        return Message::query()
            ->where('is_schedule', 1)
            ->orderBy('schedule_date_time')
            ->get()
            ->map(fn (Message $row) => $row->toArray())
            ->all();
    }

    public function findSchedule(int $id): ?Message
    {
        return Message::query()
            ->where('id', $id)
            ->where('is_schedule', 1)
            ->first();
    }

    /**
     * Update scheduled message fields only. Does not send.
     *
     * @param  array<string, mixed>  $input
     */
    public function updateSchedule(Message $row, array $input): Message
    {
        $row->title = (string) ($input['title'] ?? $row->title);
        $row->message = (string) ($input['message'] ?? $row->message);
        $row->sent = 0;
        $row->is_schedule = 1;
        $row->schedule_date_time = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        $row->save();

        return $row;
    }

    /**
     * CI update_group_schedule. Recipients from student_model->get() (same as group SMS). Does not send.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $users
     * @param  list<UploadedFile>  $files
     */
    public function updateGroupEmailSchedule(Message $row, array $input, array $users, array $files = []): Message
    {
        $templateId = $input['template_id'] ?? null;
        $templateId = ($templateId === '' || $templateId === null) ? null : (int) $templateId;
        $row->title = (string) ($input['group_title'] ?? '');
        $row->message = (string) ($input['group_message'] ?? '');
        $row->email_template_id = $templateId;
        $row->group_list = json_encode(array_values($users));
        $row->user_list = json_encode($this->emailRecipientsFromGet($users));
        $row->sent = 0;
        $row->is_schedule = 1;
        $row->schedule_date_time = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        $row->save();
        $this->storeAttachments((int) $row->id, $files);

        return $row;
    }

    /**
     * CI update_individual_schedule. Does not send.
     *
     * @param  array<string, mixed>  $input
     * @param  list<array<string, mixed>>  $userArray
     * @param  list<UploadedFile>  $files
     */
    public function updateIndividualEmailSchedule(Message $row, array $input, array $userArray, array $files = []): Message
    {
        $templateId = $input['template_id'] ?? null;
        $templateId = ($templateId === '' || $templateId === null) ? null : (int) $templateId;
        $row->title = (string) ($input['individual_title'] ?? '');
        $row->message = (string) ($input['individual_message'] ?? '');
        $row->email_template_id = $templateId;
        $row->user_list = json_encode($userArray);
        $row->sent = 0;
        $row->is_schedule = 1;
        $row->schedule_date_time = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        $row->save();
        $this->storeAttachments((int) $row->id, $files);

        return $row;
    }

    /**
     * CI update_class_schedule. Does not send.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $sections
     * @param  list<string>  $sendTo
     * @param  list<UploadedFile>  $files
     */
    public function updateClassEmailSchedule(Message $row, array $input, array $sections, array $sendTo, array $files = []): Message
    {
        $templateId = $input['template_id'] ?? null;
        $templateId = ($templateId === '' || $templateId === null) ? null : (int) $templateId;
        $classId = (int) ($input['class_id'] ?? 0);
        $row->title = (string) ($input['class_title'] ?? '');
        $row->message = (string) ($input['class_message'] ?? '');
        $row->email_template_id = $templateId;
        $row->schedule_class = $classId;
        $row->schedule_section = json_encode(array_values($sections));
        $row->send_to = json_encode(array_values($sendTo));
        $row->user_list = json_encode($this->resolveClassRecipients($classId, $sections, $sendTo, false));
        $row->sent = 0;
        $row->is_schedule = 1;
        $row->schedule_date_time = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        $row->save();
        $this->storeAttachments((int) $row->id, $files);

        return $row;
    }

    /**
     * CI update_group_sms_schedule. Does not send.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $users
     * @param  list<string>  $sendBy
     */
    public function updateGroupSmsSchedule(Message $row, array $input, array $users, array $sendBy): Message
    {
        $smsTemplateId = $input['template_id'] ?? null;
        $smsTemplateId = ($smsTemplateId === '' || $smsTemplateId === null) ? null : (int) $smsTemplateId;
        $row->title = (string) ($input['group_title'] ?? '');
        $row->message = (string) ($input['group_message'] ?? '');
        $row->send_through = json_encode(array_values($sendBy));
        $row->sms_template_id = $smsTemplateId;
        $row->template_id = (string) ($input['group_template_id'] ?? '');
        $row->group_list = json_encode(array_values($users));
        $row->user_list = json_encode($this->resolveSmsRecipients($users));
        $row->sent = 0;
        $row->is_schedule = 1;
        $row->schedule_date_time = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        $row->save();

        return $row;
    }

    /**
     * CI update_individual_sms_schedule. Does not send.
     *
     * @param  array<string, mixed>  $input
     * @param  list<array<string, mixed>>  $userArray
     * @param  list<string>  $sendBy
     */
    public function updateIndividualSmsSchedule(Message $row, array $input, array $userArray, array $sendBy): Message
    {
        $smsTemplateId = $input['template_id'] ?? null;
        $smsTemplateId = ($smsTemplateId === '' || $smsTemplateId === null) ? null : (int) $smsTemplateId;
        $row->title = (string) ($input['individual_title'] ?? '');
        $row->message = (string) ($input['individual_message'] ?? '');
        $row->send_through = json_encode(array_values($sendBy));
        $row->sms_template_id = $smsTemplateId;
        $row->template_id = (string) ($input['individual_template_id'] ?? '');
        $row->group_list = json_encode($input['user'] ?? null);
        $row->user_list = json_encode($userArray);
        $row->sent = 0;
        $row->is_schedule = 1;
        $row->schedule_date_time = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        $row->save();

        return $row;
    }

    /**
     * CI update_class_sms_schedule. Does not send.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $sections
     * @param  list<string>  $sendTo
     * @param  list<string>  $sendBy
     */
    public function updateClassSmsSchedule(Message $row, array $input, array $sections, array $sendTo, array $sendBy): Message
    {
        $smsTemplateId = $input['template_id'] ?? null;
        $smsTemplateId = ($smsTemplateId === '' || $smsTemplateId === null) ? null : (int) $smsTemplateId;
        $classId = (int) ($input['class_id'] ?? 0);
        $row->title = (string) ($input['class_title'] ?? '');
        $row->message = (string) ($input['class_message'] ?? '');
        $row->send_through = json_encode(array_values($sendBy));
        $row->sms_template_id = $smsTemplateId;
        $row->template_id = (string) ($input['class_template_id'] ?? '');
        $row->group_list = json_encode(array_values($sections));
        $row->schedule_class = $classId;
        $row->schedule_section = json_encode(array_values($sections));
        $row->send_to = json_encode(array_values($sendTo));
        $row->user_list = json_encode($this->resolveClassRecipients($classId, $sections, $sendTo, true));
        $row->sent = 0;
        $row->is_schedule = 1;
        $row->schedule_date_time = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        $row->save();

        return $row;
    }

    /**
     * @return list<string>
     */
    public function decodeStringList(mixed $json): array
    {
        if (is_array($json)) {
            return array_map('strval', array_values($json));
        }
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? array_map('strval', array_values($decoded)) : [];
    }

    public function scheduleKind(Message $row): string
    {
        $mail = (int) $row->send_mail === 1;
        if ($mail && (int) $row->is_individual === 1) {
            return 'email_individual';
        }
        if ($mail && (int) $row->is_class === 1) {
            return 'email_class';
        }
        if ($mail && (int) $row->is_group === 1) {
            return 'email_group';
        }
        if ((int) $row->is_individual === 1) {
            return 'sms_individual';
        }
        if ((int) $row->is_class === 1) {
            return 'sms_class';
        }
        if ((int) $row->is_group === 1) {
            return 'sms_group';
        }

        return 'generic';
    }

    /**
     * Rebuild CI individual user_list map for the schedule editor.
     */
    public function individualUserListFormJson(?string $stored): string
    {
        $decoded = json_decode((string) $stored, true);
        if (! is_array($decoded) || $decoded === []) {
            return '';
        }
        $first = reset($decoded);
        if (is_array($first) && isset($first[0]) && is_array($first[0])) {
            return (string) json_encode($decoded);
        }
        $map = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cat = (string) ($row['category'] ?? $row['role'] ?? '');
            $id = (int) ($row['user_id'] ?? $row['record_id'] ?? 0);
            $key = $cat.'-'.$id;
            $map[$key] = [[
                'category' => $cat,
                'record_id' => (string) $id,
                'email' => (string) ($row['email'] ?? ''),
                'guardianEmail' => (string) ($row['guardianEmail'] ?? ''),
                'mobileno' => (string) ($row['mobileno'] ?? ''),
                'app_key' => (string) ($row['app_key'] ?? ''),
            ]];
        }

        return $map === [] ? '' : (string) json_encode($map);
    }

    /**
     * @return list<object{section_id: int, section: string}>
     */
    public function sectionsForClass(int $classId): array
    {
        if ($classId <= 0) {
            return [];
        }

        return ClassSection::query()
            ->with('section')
            ->where('class_id', $classId)
            ->get()
            ->map(fn (ClassSection $row) => (object) [
                'section_id' => (int) $row->section_id,
                'section' => $row->section?->section ?? '',
            ])
            ->all();
    }

    /**
     * @param  list<string>  $users
     * @return list<array{user_id: int, email: string, mobileno: string, role: string}>
     */
    protected function emailRecipientsFromGet(array $users): array
    {
        $out = [];
        foreach ($this->resolveSmsRecipients($users) as $row) {
            $out[] = [
                'user_id' => (int) $row['user_id'],
                'email' => (string) ($row['email'] ?? ''),
                'mobileno' => (string) ($row['mobileno'] ?? ''),
                'role' => (string) ($row['role'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * CI delete_schedule: attachments then messages row.
     */
    public function deleteSchedule(int $id): bool
    {
        $row = $this->findSchedule($id);
        if ($row === null) {
            return false;
        }

        $attachments = DB::table('email_attachments')->where('message_id', $id)->get();
        foreach ($attachments as $attachment) {
            $dir = (string) ($attachment->directory ?? 'uploads/communicate/email_attachments/');
            $name = (string) ($attachment->attachment ?? '');
            if ($name !== '') {
                $path = public_path(rtrim($dir, '/\\').DIRECTORY_SEPARATOR.$name);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
        }
        DB::table('email_attachments')->where('message_id', $id)->delete();
        $row->delete();

        return true;
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    public function emailTemplates(): array
    {
        return DB::table('email_template')
            ->orderBy('id')
            ->get(['id', 'title'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => (string) $row->title])
            ->all();
    }

    /**
     * @return list<object{id: int, name: string}>
     */
    public function rolesForForm(): array
    {
        $query = Role::query()->orderBy('id');
        $viewerRoleId = 0;
        $staff = Auth::guard('staff')->user();
        if ($staff && $staff->primaryRole()) {
            $viewerRoleId = (int) $staff->primaryRole()->id;
        }
        if ($this->school->superadminRestriction() === 'disabled' && $viewerRoleId !== 7) {
            $query->where('name', '!=', 'Super Admin');
        }

        return $query->get(['id', 'name'])->all();
    }

    public function showGuardian(): bool
    {
        $value = (string) $this->school->get('guardian_name', 'enabled');

        return $value !== 'disabled' && $value !== '0' && $value !== '';
    }

    /**
     * @return list<object{id: int, class: string}>
     */
    public function classList(): array
    {
        return SchoolClass::query()->orderBy('id')->get(['id', 'class'])->all();
    }

    /**
     * CI compose() birthDaysList for today's DOB (month-day).
     *
     * @return array{students?: list<array{name: string, email: string, admission_no: string}>, staff?: list<array{name: string, email: string, employee_id: string}>}
     */
    public function birthdayList(): array
    {
        $today = Carbon::now($this->school->timezone() ?: config('app.timezone'))->format('Y-m-d');
        $out = [];

        $students = $this->birthdayStudents($today);
        if ($students !== []) {
            $out['students'] = $students;
        }

        $staff = $this->birthdayStaff($today);
        if ($staff !== []) {
            $out['staff'] = $staff;
        }

        return $out;
    }

    /**
     * Persist birthday email. Does not call mailer.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $users
     * @param  list<UploadedFile>  $files
     */
    public function sendBirthdayEmail(array $input, array $users, array $files = []): Message
    {
        $sendBy = (string) ($input['birthday_send_by'] ?? 'email');
        $templateId = $input['template_id'] ?? null;
        $templateId = ($templateId === '' || $templateId === null) ? null : (string) $templateId;

        $userArray = [];
        foreach ($users as $value) {
            $userArray[] = [
                'email' => (string) $value,
                'mobileno' => (string) $value,
            ];
        }

        $payload = [
            'is_group' => 1,
            'is_individual' => 0,
            'is_class' => 0,
            'title' => (string) ($input['birthday_title'] ?? ''),
            'message' => (string) ($input['birthday_message'] ?? ''),
            'send_mail' => $sendBy === 'sms' ? 0 : 1,
            'send_sms' => $sendBy === 'sms' ? 1 : 0,
            'template_id' => $templateId,
            'group_list' => json_encode([]),
            'user_list' => json_encode($userArray),
            'is_schedule' => 0,
        ];

        return DB::transaction(function () use ($payload, $files) {
            $row = Message::query()->create($payload);
            $this->storeAttachments($row->id, $files);

            return $row;
        });
    }

    /**
     * Persist birthday SMS. CI send_birthday_sms never called messages_model->add;
     * we still write the log (same as other compose slices). Does not send SMS/push.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $users
     * @param  list<string>  $sendBy
     */
    public function sendBirthdaySms(array $input, array $users, array $sendBy): Message
    {
        $smsTemplateId = $input['template_id'] ?? null;
        $smsTemplateId = ($smsTemplateId === '' || $smsTemplateId === null) ? null : (int) $smsTemplateId;

        $userArray = [];
        foreach ($users as $value) {
            $userArray[] = [
                'mobileno' => (string) $value,
            ];
        }

        return Message::query()->create([
            'is_group' => 1,
            'is_individual' => 0,
            'is_class' => 0,
            'title' => (string) ($input['birthday_title'] ?? ''),
            'message' => (string) ($input['birthday_message'] ?? ''),
            'send_mail' => 0,
            'send_sms' => 1,
            'send_through' => json_encode(array_values($sendBy)),
            'sms_template_id' => $smsTemplateId,
            'template_id' => (string) ($input['birthday_template_id'] ?? ''),
            'group_list' => json_encode([]),
            'user_list' => json_encode($userArray),
            'is_schedule' => 0,
        ]);
    }

    /**
     * @return list<array{name: string, email: string, admission_no: string}>
     */
    protected function birthdayStudents(string $today): array
    {
        $sessionId = $this->session->id();
        if ($sessionId <= 0) {
            return [];
        }

        $useMiddle = $this->namePartEnabled('middlename');
        $useLast = $this->namePartEnabled('lastname');

        $rows = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->leftJoin('users', function ($join) {
                $join->on('users.user_id', '=', 'students.id')
                    ->where('users.role', '=', 'student');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('users.role', 'student')
            ->whereRaw("DATE_FORMAT(students.dob, '%m-%d') = DATE_FORMAT(?, '%m-%d')", [$today])
            ->orderBy('students.id')
            ->select([
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.email',
                'students.admission_no',
                'students.mobileno',
                'students.app_key',
            ])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $full = trim($row->firstname.' '.($useMiddle ? $row->middlename.' ' : '').($useLast ? $row->lastname : ''));
            $full = preg_replace('/\s+/', ' ', $full) ?? $full;
            $out[] = [
                'name' => $full,
                'email' => (string) ($row->email ?? ''),
                'admission_no' => (string) ($row->admission_no ?? ''),
                'contact_no' => (string) ($row->mobileno ?? ''),
                'app_key' => (string) ($row->app_key ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{name: string, email: string, employee_id: string}>
     */
    protected function birthdayStaff(string $today): array
    {
        return DB::table('staff')
            ->where('staff.is_active', 1)
            ->whereRaw("DATE_FORMAT(staff.dob, '%m-%d') = DATE_FORMAT(?, '%m-%d')", [$today])
            ->orderBy('staff.id')
            ->select(['staff.name', 'staff.email', 'staff.employee_id', 'staff.contact_no'])
            ->get()
            ->map(fn ($row) => [
                'name' => (string) ($row->name ?? ''),
                'email' => (string) ($row->email ?? ''),
                'employee_id' => (string) ($row->employee_id ?? ''),
                'contact_no' => (string) ($row->contact_no ?? ''),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, title: string}>
     */
    public function smsTemplates(): array
    {
        return DB::table('sms_template')
            ->orderBy('id')
            ->get(['id', 'title'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'title' => (string) $row->title])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function sendThroughList(): array
    {
        return [
            'sms' => 'SMS',
            'push' => 'Mobile App',
        ];
    }

    /**
     * Persist group SMS. Does not call smsgateway/pushnotification.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $users
     * @param  list<string>  $sendBy
     */
    public function sendGroupSms(array $input, array $users, array $sendBy): Message
    {
        $sendType = (string) ($input['send_type'] ?? 'send_now');
        $smsTemplateId = $input['template_id'] ?? null;
        $smsTemplateId = ($smsTemplateId === '' || $smsTemplateId === null) ? null : (int) $smsTemplateId;

        $payload = [
            'is_group' => 1,
            'is_individual' => 0,
            'is_class' => 0,
            'title' => (string) ($input['group_title'] ?? ''),
            'message' => (string) ($input['group_message'] ?? ''),
            'send_mail' => 0,
            'send_sms' => 1,
            'send_through' => json_encode(array_values($sendBy)),
            'sms_template_id' => $smsTemplateId,
            'template_id' => (string) ($input['group_template_id'] ?? ''),
            'group_list' => json_encode(array_values($users)),
            'user_list' => json_encode($this->resolveSmsRecipients($users)),
            'is_schedule' => $sendType === 'schedule' ? 1 : 0,
            'sent' => $sendType === 'schedule' ? 0 : null,
            'schedule_date_time' => null,
        ];

        if ($sendType === 'schedule') {
            $payload['schedule_date_time'] = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        }

        return Message::query()->create($payload);
    }

    /**
     * CI send_group_sms uses student_model->get() (includes app_key).
     *
     * @param  list<string>  $users
     * @return list<array{user_id: int, email: string, mobileno: string, app_key: string, role: string}>
     */
    public function resolveSmsRecipients(array $users): array
    {
        $out = [];
        $students = null;

        foreach ($users as $value) {
            if ($value === 'student' || $value === 'parent') {
                $students ??= $this->studentsForSms();
            }
            if ($value === 'student') {
                foreach ($students as $student) {
                    $out[] = [
                        'user_id' => (int) $student['id'],
                        'email' => (string) ($student['email'] ?? ''),
                        'mobileno' => (string) ($student['mobileno'] ?? ''),
                        'app_key' => (string) ($student['app_key'] ?? ''),
                        'role' => 'student',
                    ];
                }
            } elseif ($value === 'parent') {
                foreach ($students as $student) {
                    $out[] = [
                        'user_id' => (int) $student['id'],
                        'email' => (string) ($student['guardian_email'] ?? ''),
                        'mobileno' => (string) ($student['guardian_phone'] ?? ''),
                        'app_key' => (string) ($student['parent_app_key'] ?? ''),
                        'role' => 'parent',
                    ];
                }
            } elseif (is_numeric($value)) {
                $staffRows = DB::table('staff')
                    ->join('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
                    ->where('staff.is_active', 1)
                    ->where('staff_roles.role_id', (int) $value)
                    ->select('staff.id', 'staff.email', 'staff.contact_no')
                    ->get();
                foreach ($staffRows as $staff) {
                    $out[] = [
                        'user_id' => (int) $staff->id,
                        'email' => (string) ($staff->email ?? ''),
                        'mobileno' => (string) ($staff->contact_no ?? ''),
                        'app_key' => '',
                        'role' => 'staff',
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function studentsForSms(): array
    {
        $sessionId = $this->session->id();
        if ($sessionId <= 0) {
            return [];
        }

        $minSession = DB::table('student_session')
            ->selectRaw('MIN(id) as id, student_id')
            ->where('session_id', $sessionId)
            ->groupBy('student_id');

        return DB::table('students')
            ->joinSub($minSession, 'ss', 'ss.student_id', '=', 'students.id')
            ->join('student_session', 'student_session.id', '=', 'ss.id')
            ->leftJoin('users', function ($join) {
                $join->on('users.user_id', '=', 'students.id')
                    ->where('users.role', '=', 'student');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('users.role', 'student')
            ->orderByDesc('students.id')
            ->select([
                'students.id',
                'students.email',
                'students.mobileno',
                'students.guardian_email',
                'students.guardian_phone',
                'students.app_key',
                'students.parent_app_key',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Persist group email. Does not call mailer.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $users
     * @param  list<UploadedFile>  $files
     */
    public function sendGroupEmail(array $input, array $users, array $files = []): Message
    {
        $sendType = (string) ($input['send_type'] ?? 'send_now');
        $templateId = $input['template_id'] ?? null;
        $templateId = ($templateId === '' || $templateId === null) ? null : (int) $templateId;

        $payload = [
            'is_group' => 1,
            'is_individual' => 0,
            'is_class' => 0,
            'title' => (string) ($input['group_title'] ?? ''),
            'message' => (string) ($input['group_message'] ?? ''),
            'send_mail' => 1,
            'send_sms' => 0,
            'email_template_id' => $templateId,
            'group_list' => json_encode(array_values($users)),
            'user_list' => json_encode($this->resolveRecipients($users)),
            'is_schedule' => $sendType === 'schedule' ? 1 : 0,
            'sent' => $sendType === 'schedule' ? 0 : null,
            'schedule_date_time' => null,
        ];

        if ($sendType === 'schedule') {
            $payload['schedule_date_time'] = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        }

        return DB::transaction(function () use ($payload, $files) {
            $row = Message::query()->create($payload);
            $this->storeAttachments($row->id, $files);

            return $row;
        });
    }

    /**
     * Persist individual email. Does not call mailer.
     *
     * @param  array<string, mixed>  $input
     * @param  list<array<string, mixed>>  $userArray
     * @param  list<UploadedFile>  $files
     */
    public function sendIndividualEmail(array $input, array $userArray, array $files = []): Message
    {
        $sendType = (string) ($input['individual_send_type'] ?? 'send_now');
        $sendBy = (string) ($input['individual_send_by'] ?? 'email');
        $templateId = $input['template_id'] ?? null;
        $templateId = ($templateId === '' || $templateId === null) ? null : (int) $templateId;

        $payload = [
            'is_group' => 0,
            'is_individual' => 1,
            'is_class' => 0,
            'title' => (string) ($input['individual_title'] ?? ''),
            'message' => (string) ($input['individual_message'] ?? ''),
            'send_mail' => $sendBy === 'sms' ? 0 : 1,
            'send_sms' => $sendBy === 'sms' ? 1 : 0,
            'email_template_id' => $templateId,
            'user_list' => json_encode($userArray),
            'is_schedule' => $sendType === 'schedule' ? 1 : 0,
            'sent' => $sendType === 'schedule' ? 0 : null,
            'schedule_date_time' => null,
        ];

        if ($sendType === 'schedule') {
            $payload['schedule_date_time'] = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        }

        return DB::transaction(function () use ($payload, $files) {
            $row = Message::query()->create($payload);
            $this->storeAttachments($row->id, $files);

            return $row;
        });
    }

    /**
     * Persist individual SMS. Does not call smsgateway/pushnotification.
     *
     * @param  array<string, mixed>  $input
     * @param  list<array<string, mixed>>  $userArray
     * @param  list<string>  $sendBy
     */
    public function sendIndividualSms(array $input, array $userArray, array $sendBy): Message
    {
        $sendType = (string) ($input['individual_send_type'] ?? 'send_now');
        $smsTemplateId = $input['template_id'] ?? null;
        $smsTemplateId = ($smsTemplateId === '' || $smsTemplateId === null) ? null : (int) $smsTemplateId;

        $payload = [
            'is_group' => 0,
            'is_individual' => 1,
            'is_class' => 0,
            'title' => (string) ($input['individual_title'] ?? ''),
            'message' => (string) ($input['individual_message'] ?? ''),
            'send_mail' => 0,
            'send_sms' => 1,
            'send_through' => json_encode(array_values($sendBy)),
            'sms_template_id' => $smsTemplateId,
            'template_id' => (string) ($input['individual_template_id'] ?? ''),
            'group_list' => json_encode($input['user'] ?? null),
            'user_list' => json_encode($userArray),
            'is_schedule' => $sendType === 'schedule' ? 1 : 0,
            'sent' => $sendType === 'schedule' ? 0 : null,
            'schedule_date_time' => null,
        ];

        if ($sendType === 'schedule') {
            $payload['schedule_date_time'] = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        }

        return Message::query()->create($payload);
    }

    /**
     * Persist class email. Does not call mailer.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $sections
     * @param  list<string>  $sendTo
     * @param  list<UploadedFile>  $files
     */
    public function sendClassEmail(array $input, array $sections, array $sendTo, array $files = []): Message
    {
        $sendType = (string) ($input['class_send_type'] ?? 'send_now');
        $sendBy = (string) ($input['class_send_by'] ?? 'email');
        $templateId = $input['template_id'] ?? null;
        $templateId = ($templateId === '' || $templateId === null) ? null : (int) $templateId;
        $classId = (int) ($input['class_id'] ?? 0);

        $payload = [
            'is_group' => 0,
            'is_individual' => 0,
            'is_class' => 1,
            'title' => (string) ($input['class_title'] ?? ''),
            'message' => (string) ($input['class_message'] ?? ''),
            'send_mail' => $sendBy === 'sms' ? 0 : 1,
            'send_sms' => $sendBy === 'sms' ? 1 : 0,
            'email_template_id' => $templateId,
            'schedule_class' => $classId,
            'schedule_section' => json_encode(array_values($sections)),
            'user_list' => json_encode($this->resolveClassRecipients($classId, $sections, $sendTo, false)),
            'send_to' => json_encode(array_values($sendTo)),
            'is_schedule' => $sendType === 'schedule' ? 1 : 0,
            'sent' => $sendType === 'schedule' ? 0 : null,
            'schedule_date_time' => null,
        ];

        if ($sendType === 'schedule') {
            $payload['schedule_date_time'] = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        }

        return DB::transaction(function () use ($payload, $files) {
            $row = Message::query()->create($payload);
            $this->storeAttachments($row->id, $files);

            return $row;
        });
    }

    /**
     * Persist class SMS. Does not call smsgateway/pushnotification.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $sections
     * @param  list<string>  $sendTo
     * @param  list<string>  $sendBy
     */
    public function sendClassSms(array $input, array $sections, array $sendTo, array $sendBy): Message
    {
        $sendType = (string) ($input['class_send_type'] ?? 'send_now');
        $smsTemplateId = $input['template_id'] ?? null;
        $smsTemplateId = ($smsTemplateId === '' || $smsTemplateId === null) ? null : (int) $smsTemplateId;
        $classId = (int) ($input['class_id'] ?? 0);

        $payload = [
            'is_group' => 0,
            'is_individual' => 0,
            'is_class' => 1,
            'title' => (string) ($input['class_title'] ?? ''),
            'message' => (string) ($input['class_message'] ?? ''),
            'send_mail' => 0,
            'send_sms' => 1,
            'send_through' => json_encode(array_values($sendBy)),
            'sms_template_id' => $smsTemplateId,
            'template_id' => (string) ($input['class_template_id'] ?? ''),
            'group_list' => json_encode(array_values($sections)),
            'schedule_class' => $classId,
            'schedule_section' => json_encode(array_values($sections)),
            'user_list' => json_encode($this->resolveClassRecipients($classId, $sections, $sendTo, true)),
            'send_to' => json_encode(array_values($sendTo)),
            'is_schedule' => $sendType === 'schedule' ? 1 : 0,
            'sent' => $sendType === 'schedule' ? 0 : null,
            'schedule_date_time' => null,
        ];

        if ($sendType === 'schedule') {
            $payload['schedule_date_time'] = $this->parseSchedule((string) ($input['schedule_date_time'] ?? ''));
        }

        return Message::query()->create($payload);
    }

    /**
     * CI student_model->searchByClassSection (email/SMS fields only).
     *
     * @param  list<string>  $sections
     * @param  list<string>  $sendTo
     * @return list<array<string, mixed>>
     */
    public function resolveClassRecipients(int $classId, array $sections, array $sendTo, bool $withAppKey = false): array
    {
        $out = [];
        foreach ($sendTo as $target) {
            if ($target !== 'student' && $target !== 'parent') {
                continue;
            }
            foreach ($sections as $sectionId) {
                foreach ($this->studentsByClassSection($classId, (int) $sectionId) as $student) {
                    $row = [
                        'user_id' => (int) $student['id'],
                        'email' => $target === 'parent'
                            ? (string) ($student['guardian_email'] ?? '')
                            : (string) ($student['email'] ?? ''),
                        'mobileno' => $target === 'parent'
                            ? (string) ($student['guardian_phone'] ?? '')
                            : (string) ($student['mobileno'] ?? ''),
                        'role' => $target,
                    ];
                    if ($withAppKey) {
                        $row['app_key'] = $target === 'parent'
                            ? (string) ($student['parent_app_key'] ?? '')
                            : (string) ($student['app_key'] ?? '');
                    }
                    $out[] = $row;
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function studentsByClassSection(int $classId, int $sectionId): array
    {
        $sessionId = $this->session->id();
        if ($sessionId <= 0 || $classId <= 0 || $sectionId <= 0) {
            return [];
        }

        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('student_session.class_id', $classId)
            ->where('student_session.section_id', $sectionId)
            ->orderBy('students.admission_no')
            ->select([
                'students.id',
                'students.email',
                'students.mobileno',
                'students.guardian_email',
                'students.guardian_phone',
                'students.app_key',
                'students.parent_app_key',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * CI send_individual user_list JSON (map of category-id => [{...}]).
     *
     * @return list<array{category: string, user_id: int, email: string, guardianEmail: string, mobileno: string, role: string, app_key?: string}>
     */
    public function parseIndividualUserList(string $json, bool $withAppKey = false): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded) || $decoded === []) {
            return [];
        }

        $out = [];
        foreach ($decoded as $entry) {
            $row = $entry;
            if (isset($entry[0]) && is_array($entry[0])) {
                $row = $entry[0];
            }
            $item = [
                'category' => (string) ($row['category'] ?? ''),
                'user_id' => (int) ($row['record_id'] ?? $row['user_id'] ?? 0),
                'email' => (string) ($row['email'] ?? ''),
                'guardianEmail' => (string) ($row['guardianEmail'] ?? ''),
                'mobileno' => (string) ($row['mobileno'] ?? ''),
                'role' => (string) ($row['category'] ?? $row['role'] ?? ''),
            ];
            if ($withAppKey) {
                $item['app_key'] = (string) ($row['app_key'] ?? '');
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * CI admin/mailsms/search.
     *
     * @return list<array<string, mixed>>
     */
    public function searchRecipients(string $keyword, string $category): array
    {
        $keyword = trim($keyword);
        if ($keyword === '' || $category === '') {
            return [];
        }

        if ($category === 'student' || $category === 'student_guardian') {
            return $this->searchStudents($keyword, $category);
        }
        if ($category === 'parent') {
            return $this->searchGuardians($keyword);
        }
        if ($category === 'staff') {
            return $this->searchStaff($keyword);
        }

        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchStudents(string $keyword, string $category): array
    {
        $sessionId = $this->session->id();
        if ($sessionId <= 0) {
            return [];
        }

        $useMiddle = $this->namePartEnabled('middlename');
        $useLast = $this->namePartEnabled('lastname');

        $rows = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where(function ($q) use ($keyword) {
                $q->where('students.firstname', 'like', '%'.$keyword.'%')
                    ->orWhere('students.lastname', 'like', '%'.$keyword.'%')
                    ->orWhere('students.guardian_name', 'like', '%'.$keyword.'%');
            })
            ->orderBy('students.id')
            ->limit(15)
            ->select([
                'students.id',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.admission_no',
                'students.email',
                'students.mobileno',
                'students.guardian_name',
                'students.guardian_email',
                'students.app_key',
                'students.parent_app_key',
            ])
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $full = trim($row->firstname.' '.($useMiddle ? $row->middlename.' ' : '').($useLast ? $row->lastname : ''));
            $full = preg_replace('/\s+/', ' ', $full) ?? $full;
            $item = (array) $row;
            if ($category === 'student_guardian') {
                $item['fullname'] = $full.' ('.$row->admission_no.') ('.$row->guardian_name.')';
            } else {
                $item['fullname'] = $full;
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchGuardians(string $keyword): array
    {
        $sessionId = $this->session->id();
        if ($sessionId <= 0) {
            return [];
        }

        $rows = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('users', 'users.id', '=', 'students.parent_id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('users.role', 'parent')
            ->where('students.guardian_name', 'like', '%'.$keyword.'%')
            ->orderBy('students.id')
            ->limit(40)
            ->select([
                'students.id',
                'students.parent_id',
                'students.guardian_name',
                'students.guardian_email',
                'students.guardian_phone',
                'students.parent_app_key',
            ])
            ->get();

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $parentId = (int) $row->parent_id;
            if (isset($seen[$parentId])) {
                continue;
            }
            $seen[$parentId] = true;
            $out[] = (array) $row;
            if (count($out) >= 15) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function searchStaff(string $keyword): array
    {
        $query = DB::table('staff')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->where('staff.is_active', 1)
            ->where('staff.name', 'like', '%'.$keyword.'%')
            ->orderBy('staff.id')
            ->limit(40)
            ->select('staff.*');

        $staff = Auth::guard('staff')->user();
        $roleId = $staff && $staff->primaryRole() ? (int) $staff->primaryRole()->id : 0;
        if ($this->school->superadminRestriction() === 'disabled' && $roleId !== 7) {
            $query->where('roles.id', '!=', 7);
        }

        $out = [];
        $seen = [];
        foreach ($query->get() as $row) {
            $id = (int) $row->id;
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $out[] = (array) $row;
            if (count($out) >= 15) {
                break;
            }
        }

        return $out;
    }

    protected function namePartEnabled(string $key): bool
    {
        $value = (string) $this->school->get($key, 'enabled');

        return $value !== 'disabled' && $value !== '0' && $value !== '';
    }

    /**
     * @param  list<string>  $users
     * @return list<array{user_id: int, email: string, mobileno: string, role: string}>
     */
    public function resolveRecipients(array $users): array
    {
        $out = [];
        $students = null;

        foreach ($users as $value) {
            if ($value === 'student' || $value === 'parent') {
                $students ??= $this->currentStudents();
            }
            if ($value === 'student') {
                foreach ($students as $student) {
                    $out[] = [
                        'user_id' => (int) $student['id'],
                        'email' => (string) ($student['email'] ?? ''),
                        'mobileno' => (string) ($student['mobileno'] ?? ''),
                        'role' => 'student',
                    ];
                }
            } elseif ($value === 'parent') {
                foreach ($students as $student) {
                    $out[] = [
                        'user_id' => (int) $student['id'],
                        'email' => (string) ($student['guardian_email'] ?? ''),
                        'mobileno' => (string) ($student['guardian_phone'] ?? ''),
                        'role' => 'parent',
                    ];
                }
            } elseif (is_numeric($value)) {
                $staffRows = DB::table('staff')
                    ->join('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
                    ->where('staff.is_active', 1)
                    ->where('staff_roles.role_id', (int) $value)
                    ->select('staff.id', 'staff.email', 'staff.contact_no')
                    ->get();
                foreach ($staffRows as $staff) {
                    $out[] = [
                        'user_id' => (int) $staff->id,
                        'email' => (string) ($staff->email ?? ''),
                        'mobileno' => (string) ($staff->contact_no ?? ''),
                        'role' => 'staff',
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function currentStudents(): array
    {
        $sessionId = $this->session->id();
        if ($sessionId <= 0) {
            return [];
        }

        return DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->leftJoin('users', function ($join) {
                $join->on('users.user_id', '=', 'students.id')
                    ->where('users.role', '=', 'student');
            })
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('users.role', 'student')
            ->orderBy('students.id')
            ->select([
                'students.id',
                'students.email',
                'students.mobileno',
                'students.guardian_email',
                'students.guardian_phone',
            ])
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    protected function parseSchedule(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
        $format = $this->school->dateFormat() ?: 'd/m/Y';

        return Carbon::createFromFormat($format.' H:i', $value)->format('Y-m-d H:i:s');
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    protected function storeAttachments(int $messageId, array $files): void
    {
        $dirRel = 'uploads/communicate/email_attachments';
        $dir = public_path($dirRel);
        File::ensureDirectoryExists($dir);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $original = basename((string) $file->getClientOriginalName());
            $saved = time().'-'.uniqid((string) random_int(1000, 9999), false).'!'.$original;
            $file->move($dir, $saved);
            DB::table('email_attachments')->insert([
                'message_id' => $messageId,
                'directory' => $dirRel.'/',
                'attachment' => $saved,
                'attachment_name' => $saved,
            ]);
        }
    }
}
