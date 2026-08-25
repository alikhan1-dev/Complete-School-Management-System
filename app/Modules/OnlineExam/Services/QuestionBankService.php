<?php

namespace App\Modules\OnlineExam\Services;

use App\Modules\OnlineExam\Models\Question;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Question_model / admin/Question — question bank CRUD.
 * Deferred: CSV import, bulk delete, CMS image browser, teacher-scoped visibility.
 */
class QuestionBankService
{
    public function __construct(
        protected SchoolContext $school,
    ) {
    }
    /**
     * @return array<string, string>
     */
    public function questionTypes(): array
    {
        return [
            'singlechoice' => 'Single Choice',
            'multichoice' => 'Multiple Choice',
            'true_false' => 'True/False',
            'descriptive' => 'Descriptive',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function questionLevels(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
        ];
    }

    /**
     * CI Customlib::getQuesOption.
     *
     * @return array<string, string>
     */
    public function optionKeys(): array
    {
        return [
            'opt_a' => 'A',
            'opt_b' => 'B',
            'opt_c' => 'C',
            'opt_d' => 'D',
            'opt_e' => 'E',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function trueFalseOptions(): array
    {
        return [
            'true' => 'True',
            'false' => 'False',
        ];
    }

    public function listQuestions(?int $createdBy = null, int $perPage = 25): LengthAwarePaginator
    {
        $query = DB::table('questions')
            ->leftJoin('subjects', 'subjects.id', '=', 'questions.subject_id')
            ->leftJoin('classes', 'classes.id', '=', 'questions.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'questions.section_id')
            ->leftJoin('staff', 'staff.id', '=', 'questions.staff_id')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->orderByDesc('questions.id')
            ->select([
                'questions.id',
                'questions.question',
                'questions.question_type',
                'questions.level',
                'questions.class_id',
                'questions.section_id',
                'questions.subject_id',
                'questions.staff_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'classes.class as class_name',
                'sections.section as section_name',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id',
                'staff_roles.role_id as created_role',
            ]);

        if ($createdBy !== null && $createdBy > 0) {
            $query->where('questions.staff_id', $createdBy);
        }

        $paginator = $query->paginate($perPage);

        return $paginator->through(fn ($row) => $this->decorateListRow($row));
    }

    /**
     * CI Question_model::getquestioncreatedstaff — staff who have created questions.
     *
     * @return Collection<int, object>
     */
    public function creatorsForFilter(): Collection
    {
        $query = DB::table('questions')
            ->join('staff', 'staff.id', '=', 'questions.staff_id')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->leftJoin('roles', 'roles.id', '=', 'staff_roles.role_id')
            ->select([
                'staff.id',
                'staff.name',
                'staff.surname',
                'staff.employee_id',
            ])
            ->groupBy('staff.id', 'staff.name', 'staff.surname', 'staff.employee_id')
            ->orderBy('staff.id');

        if ($this->shouldMaskCreator(7)) {
            $query->where(function ($q) {
                $q->whereNull('roles.id')->orWhere('roles.id', '!=', 7);
            });
        }

        return $query->get();
    }

    /**
     * CI Question::getDatatable created_by column — mask superadmin creator for non-superadmin viewers.
     */
    public function formatCreatorLabel(object $row): string
    {
        $createdRole = (int) ($row->created_role ?? 0);
        if ($this->shouldMaskCreator($createdRole)) {
            return '';
        }

        $name = trim(((string) ($row->staff_name ?? '')).' '.((string) ($row->staff_surname ?? '')));
        $employeeId = (string) ($row->employee_id ?? '');
        if ($name === '' && $employeeId === '') {
            return '';
        }

        return $employeeId !== '' ? $name.' ('.$employeeId.')' : $name;
    }

    protected function decorateListRow(object $row): object
    {
        $row->creator_label = $this->formatCreatorLabel($row);

        return $row;
    }

    protected function shouldMaskCreator(int $createdRoleId): bool
    {
        if ($createdRoleId !== 7) {
            return false;
        }

        if ($this->school->superadminRestriction() !== 'disabled') {
            return false;
        }

        return $this->viewerRoleId() !== 7;
    }

    protected function viewerRoleId(): int
    {
        /** @var Staff|null $staff */
        $staff = Auth::guard('staff')->user();
        if (! $staff) {
            return 0;
        }

        return (int) ($staff->roles()->value('roles.id') ?? 0);
    }

    public function find(int $id): Question
    {
        return Question::query()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Request $request, ?Question $existing = null): array
    {
        $type = (string) $request->input('question_type');
        $payload = [
            'subject_id' => (int) $request->input('subject_id'),
            'question' => (string) $request->input('question'),
            'question_type' => $type,
            'level' => (string) $request->input('question_level'),
            'class_id' => (int) $request->input('class_id'),
            'section_id' => $request->filled('section_id') ? (int) $request->input('section_id') : null,
            'class_section_id' => $existing?->class_section_id,
            'descriptive_word_limit' => $existing
                ? (int) $existing->descriptive_word_limit
                : 0,
            'opt_a' => '',
            'opt_b' => '',
            'opt_c' => '',
            'opt_d' => '',
            'opt_e' => '',
            'correct' => '',
        ];

        if ($existing === null) {
            $payload['staff_id'] = Auth::guard('staff')->id();
        }

        if ($type === 'singlechoice') {
            $payload['opt_a'] = (string) $request->input('opt_a', '');
            $payload['opt_b'] = (string) $request->input('opt_b', '');
            $payload['opt_c'] = (string) $request->input('opt_c', '');
            $payload['opt_d'] = (string) $request->input('opt_d', '');
            $payload['opt_e'] = (string) $request->input('opt_e', '');
            $payload['correct'] = (string) $request->input('correct', '');
        } elseif ($type === 'true_false') {
            $payload['correct'] = (string) $request->input('correct_true_false', '');
        } elseif ($type === 'multichoice') {
            $payload['opt_a'] = (string) $request->input('opt_a', '');
            $payload['opt_b'] = (string) $request->input('opt_b', '');
            $payload['opt_c'] = (string) $request->input('opt_c', '');
            $payload['opt_d'] = (string) $request->input('opt_d', '');
            $payload['opt_e'] = (string) $request->input('opt_e', '');
            $answers = array_values(array_filter((array) $request->input('ans', [])));
            if ($answers === []) {
                throw ValidationException::withMessages([
                    'ans' => 'At least one correct answer is required for multiple choice.',
                ]);
            }
            $payload['correct'] = json_encode($answers);
        }

        return $payload;
    }

    public function create(array $payload): Question
    {
        return Question::query()->create($payload);
    }

    public function update(Question $question, array $payload): Question
    {
        $question->fill($payload);
        $question->save();

        return $question;
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    /**
     * Decode multichoice correct answers for edit forms.
     *
     * @return list<string>
     */
    public function decodedMultichoiceAnswers(?string $correct): array
    {
        if ($correct === null || $correct === '') {
            return [];
        }

        $decoded = json_decode($correct, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map('strval', $decoded));
    }
}
