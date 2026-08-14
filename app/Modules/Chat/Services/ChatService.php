<?php

namespace App\Modules\Chat\Services;

use App\Modules\Chat\Models\ChatConnection;
use App\Modules\Chat\Models\ChatMessage;
use App\Modules\Chat\Models\ChatUser;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CI Chatuser_model + admin/chat persist (staff panel).
 * user/Chat portal is deferred.
 */
class ChatService
{
    public function __construct(protected SchoolContext $school)
    {
    }

    public function currentStaffId(): int
    {
        $staff = Auth::guard('staff')->user();

        return $staff ? (int) $staff->id : 0;
    }

    public function deleteChatEnabled(): int
    {
        return (int) $this->school->get('staff_delete_chat', 0);
    }

    public function getMyId(int $id, string $userType = 'staff'): ?object
    {
        $query = ChatUser::query()->where('user_type', $userType);
        if ($userType === 'staff') {
            $query->where('staff_id', $id);
        } else {
            $query->where('student_id', $id);
        }
        $row = $query->first();

        return $row ? (object) $row->toArray() : null;
    }

    /**
     * @return list<object>
     */
    public function searchForUser(string $keyword, int $chatUserId, int $loginId): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        $connectedStaff = $this->connectedPartyIds($chatUserId, 'staff_id');
        $connectedStudents = $this->connectedPartyIds($chatUserId, 'student_id');

        $staffQuery = DB::table('staff')
            ->where('is_active', 1)
            ->where('id', '!=', $loginId)
            ->where('name', 'like', '%'.$keyword.'%')
            ->selectRaw('staff.id as staff_id, NULL as student_id, staff.name, staff.surname, NULL as first_name, NULL as middle_name, NULL as last_name, staff.image, staff.gender');
        if ($connectedStaff !== []) {
            $staffQuery->whereNotIn('staff.id', $connectedStaff);
        }

        $studentQuery = DB::table('students')
            ->where('is_active', 'yes')
            ->where(function ($q) use ($keyword) {
                $q->where('firstname', 'like', '%'.$keyword.'%')
                    ->orWhere('middlename', 'like', '%'.$keyword.'%')
                    ->orWhere('lastname', 'like', '%'.$keyword.'%');
            })
            ->selectRaw('NULL as staff_id, students.id as student_id, NULL as name, NULL as surname, students.firstname as first_name, students.middlename as middle_name, students.lastname as last_name, students.image, students.gender');
        if ($connectedStudents !== []) {
            $studentQuery->whereNotIn('students.id', $connectedStudents);
        }

        $rows = $staffQuery->get()->merge($studentQuery->get())->all();

        $viewer = Auth::guard('staff')->user();
        $viewerRoleId = 0;
        if ($viewer instanceof Staff && $viewer->primaryRole()) {
            $viewerRoleId = (int) $viewer->primaryRole()->id;
        }
        if ($viewerRoleId !== 7 && $this->school->superadminRestriction() === 'disabled') {
            $rows = array_values(array_filter($rows, function ($row) {
                if (empty($row->staff_id)) {
                    return true;
                }
                $roleId = (int) DB::table('staff_roles')->where('staff_id', $row->staff_id)->value('role_id');

                return $roleId !== 7;
            }));
        }

        return $rows;
    }

    /**
     * @return array{chat_users: list<object>, chat_user_notification: list<object>}
     */
    public function myUser(int $staffId, int $myChatUserId): array
    {
        $connections = ChatConnection::query()
            ->where('chat_user_one', $myChatUserId)
            ->orWhere('chat_user_two', $myChatUserId)
            ->orderByDesc('id')
            ->get();

        $chatUsers = [];
        foreach ($connections as $connection) {
            $item = (object) $connection->toArray();
            $item->messages = $this->getLastMessages((int) $connection->id);
            $otherId = (int) $connection->chat_user_one === $myChatUserId
                ? (int) $connection->chat_user_two
                : (int) $connection->chat_user_one;
            $item->user_details = $this->getChatUserDetail($otherId);
            $chatUsers[] = $item;
        }

        return [
            'chat_users' => $chatUsers,
            'chat_user_notification' => $this->getChatNotification($myChatUserId),
        ];
    }

    public function getLastMessages(int $connectionId): ?object
    {
        $maxId = ChatMessage::query()->where('chat_connection_id', $connectionId)->max('id');
        if (! $maxId) {
            return null;
        }
        $row = ChatMessage::query()->find($maxId);

        return $row ? (object) $row->toArray() : null;
    }

    public function getChatUserDetail(int $chatUserId): ?object
    {
        $row = DB::table('chat_users')
            ->leftJoin('students', 'students.id', '=', 'chat_users.student_id')
            ->leftJoin('staff', 'staff.id', '=', 'chat_users.staff_id')
            ->where('chat_users.id', $chatUserId)
            ->selectRaw("chat_users.id as chat_user_id, chat_users.user_type, chat_users.student_id, chat_users.staff_id,
                students.guardian_pic, students.guardian_name, students.father_name, students.firstname, students.middlename, students.lastname,
                staff.name, staff.surname,
                CASE WHEN chat_users.staff_id IS NULL THEN students.gender ELSE staff.gender END as gender,
                CASE WHEN chat_users.staff_id IS NULL THEN students.image ELSE staff.image END as image")
            ->first();

        return $row ?: null;
    }

    /**
     * @return list<object>
     */
    public function myChatAndUpdate(int $connectionId, int $chatUserId): array
    {
        ChatMessage::query()
            ->where('chat_connection_id', $connectionId)
            ->where('chat_user_id', $chatUserId)
            ->update(['is_read' => 1]);

        return ChatMessage::query()
            ->where('chat_connection_id', $connectionId)
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $row) => (object) $row->toArray())
            ->all();
    }

    public function getChatConnectionById(int $id): ?object
    {
        $row = ChatConnection::query()->find($id);

        return $row ? (object) $row->toArray() : null;
    }

    public function addMessage(array $insert): int
    {
        $row = ChatMessage::query()->create([
            'message' => (string) ($insert['message'] ?? ''),
            'chat_user_id' => (int) ($insert['chat_user_id'] ?? 0),
            'ip' => (string) ($insert['ip'] ?? request()->ip() ?? ''),
            'time' => (int) ($insert['time'] ?? time()),
            'is_first' => (int) ($insert['is_first'] ?? 0),
            'is_read' => (int) ($insert['is_read'] ?? 0),
            'chat_connection_id' => (int) ($insert['chat_connection_id'] ?? 0),
            'created_at' => $insert['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $row->id;
    }

    public function parseMessageTime(?string $time): string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return date('Y-m-d H:i:s');
        }
        try {
            $format = $this->school->dateFormat() ?: 'd/m/Y';
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $time) === 1) {
                return Carbon::parse($time)->format('Y-m-d H:i:s');
            }

            return Carbon::createFromFormat($format.' H:i:s', $time)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            try {
                return Carbon::parse($time)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return date('Y-m-d H:i:s');
            }
        }
    }

    /**
     * @return list<object>
     */
    public function getUpdatedChat(int $connectionId, int $lastChatId, int $myChatUserId): array
    {
        ChatMessage::query()
            ->where('chat_connection_id', $connectionId)
            ->where('chat_user_id', $myChatUserId)
            ->update(['is_read' => 1]);

        return ChatMessage::query()
            ->where('chat_connection_id', $connectionId)
            ->where('id', '>', $lastChatId)
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $row) => (object) $row->toArray())
            ->all();
    }

    /**
     * @return array{new_user_id: int, new_user_chat_connection_id: int}
     */
    public function addNewUser(array $firstEntry, array $insertData, int $staffId): array
    {
        $mine = ChatUser::query()
            ->where('staff_id', $firstEntry['staff_id'])
            ->where('user_type', $firstEntry['user_type'])
            ->first();

        $otherQuery = ChatUser::query()->where('user_type', $insertData['user_type']);
        if (($insertData['user_type'] ?? '') === 'staff') {
            $otherQuery->where('staff_id', $insertData['staff_id'] ?? 0);
        } else {
            $otherQuery->where('student_id', $insertData['student_id'] ?? 0);
        }
        $other = $otherQuery->first();

        if ($mine && $other) {
            $one = (int) $mine->id;
            $two = (int) $other->id;
        } elseif (! $mine && $other) {
            $mine = ChatUser::query()->create($firstEntry);
            $one = (int) $mine->id;
            $two = (int) $other->id;
        } elseif ($mine && ! $other) {
            $insertData['create_staff_id'] = $staffId;
            $other = ChatUser::query()->create($insertData);
            $one = (int) $mine->id;
            $two = (int) $other->id;
        } else {
            $mine = ChatUser::query()->create($firstEntry);
            $insertData['create_staff_id'] = $staffId;
            $other = ChatUser::query()->create($insertData);
            $one = (int) $mine->id;
            $two = (int) $other->id;
        }

        $connection = ChatConnection::query()->create([
            'chat_user_one' => $one,
            'chat_user_two' => $two,
            'ip' => (string) (request()->ip() ?? ''),
            'time' => time(),
        ]);
        $this->addMessage([
            'message' => 'you are now connected on chat',
            'chat_user_id' => $two,
            'is_first' => 1,
            'chat_connection_id' => (int) $connection->id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'new_user_id' => $two,
            'new_user_chat_connection_id' => (int) $connection->id,
        ];
    }

    /**
     * @param  list<int|string>  $existingConnectionIds
     * @return array{chat_users: list<object>, chat_user_notification: list<object>}
     */
    public function myNewUser(int $myChatUserId, array $existingConnectionIds): array
    {
        $query = ChatConnection::query()
            ->where(function ($q) use ($myChatUserId) {
                $q->where('chat_user_one', $myChatUserId)->orWhere('chat_user_two', $myChatUserId);
            })
            ->orderBy('id');
        if ($existingConnectionIds !== []) {
            $query->whereNotIn('id', array_map('intval', $existingConnectionIds));
        }
        $chatUsers = [];
        foreach ($query->get() as $connection) {
            $item = (object) $connection->toArray();
            $item->messages = $this->getLastMessages((int) $connection->id);
            $otherId = (int) $connection->chat_user_one === $myChatUserId
                ? (int) $connection->chat_user_two
                : (int) $connection->chat_user_one;
            $item->user_details = $this->getChatUserDetail($otherId);
            $chatUsers[] = $item;
        }

        return [
            'chat_users' => $chatUsers,
            'chat_user_notification' => $this->getChatNotification($myChatUserId),
        ];
    }

    public function deleteMessage(int $id): void
    {
        ChatMessage::query()->where('id', $id)->delete();
    }

    /**
     * @return list<object>
     */
    public function getActiveChatMsg(int $connectionId): array
    {
        return ChatMessage::query()
            ->where('chat_connection_id', $connectionId)
            ->get(['id'])
            ->map(fn (ChatMessage $row) => (object) ['id' => (int) $row->id])
            ->all();
    }

    /**
     * @return list<object>
     */
    public function getChatNotification(int $chatUserId): array
    {
        return DB::table('chat_messages')
            ->selectRaw('COUNT(*) as no_of_notification, chat_connection_id')
            ->whereIn('chat_connection_id', function ($q) use ($chatUserId) {
                $q->select('id')->from('chat_connections')
                    ->where('chat_user_one', $chatUserId)
                    ->orWhere('chat_user_two', $chatUserId);
            })
            ->where('chat_user_id', $chatUserId)
            ->where('is_read', 0)
            ->groupBy('chat_connection_id')
            ->orderBy('chat_connection_id')
            ->get()
            ->all();
    }

    /**
     * @return list<object>
     */
    public function unreadConnectionCount(int $staffId): array
    {
        $mine = $this->getMyId($staffId, 'staff');
        if ($mine === null) {
            return [];
        }

        return DB::table('chat_messages')
            ->where('chat_user_id', $mine->id)
            ->where('is_read', 0)
            ->groupBy('chat_connection_id')
            ->get(['id'])
            ->all();
    }

    public function formatUserForAdd(object $detail): object
    {
        $user = clone $detail;
        if (empty($user->image)) {
            $user->image = (($user->gender ?? '') === 'Female')
                ? 'uploads/staff_images/default_female.jpg'
                : 'uploads/staff_images/default_male.jpg';
        } elseif (($user->user_type ?? '') === 'staff') {
            $user->image = './uploads/staff_images/'.$user->image.'?'.time();
        }
        $user->name = ! empty($user->student_id)
            ? trim(($user->firstname ?? '').' '.($user->middlename ?? '').' '.($user->lastname ?? ''))
            : trim(($user->name ?? '').' '.($user->surname ?? ''));

        return $user;
    }

    public function displayName(?object $details): string
    {
        if ($details === null) {
            return '';
        }
        if (($details->user_type ?? '') === 'staff') {
            $name = trim((string) ($details->name ?? ''));
            $surname = trim((string) ($details->surname ?? ''));

            return $surname === '' ? $name : $name.' '.$surname;
        }
        if (($details->user_type ?? '') === 'parent') {
            return (string) ($details->guardian_name ?? '');
        }

        return trim(($details->firstname ?? '').' '.($details->middlename ?? '').' '.($details->lastname ?? ''));
    }

    public function imageUrl(?object $details): string
    {
        $fallback = asset('uploads/staff_images/no_image.png');
        if ($details === null) {
            return $fallback;
        }
        $type = (string) ($details->user_type ?? '');
        if ($type === 'staff' && ! empty($details->image)) {
            return asset('uploads/staff_images/'.$details->image);
        }
        if ($type === 'student' && ! empty($details->image)) {
            return asset($details->image);
        }
        if ($type === 'parent' && ! empty($details->guardian_pic)) {
            return asset($details->guardian_pic);
        }

        return $fallback;
    }

    /**
     * @return list<int>
     */
    protected function connectedPartyIds(int $chatUserId, string $column): array
    {
        if ($chatUserId <= 0) {
            return [];
        }

        $otherIds = ChatConnection::query()
            ->where('chat_user_one', $chatUserId)
            ->orWhere('chat_user_two', $chatUserId)
            ->get()
            ->map(function (ChatConnection $row) use ($chatUserId) {
                return (int) $row->chat_user_one === $chatUserId
                    ? (int) $row->chat_user_two
                    : (int) $row->chat_user_one;
            })
            ->all();
        if ($otherIds === []) {
            return [];
        }

        return ChatUser::query()
            ->whereIn('id', $otherIds)
            ->whereNotNull($column)
            ->pluck($column)
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
