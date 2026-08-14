<?php

namespace App\Modules\Chat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Chat\Services\ChatService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * CI user/Chat — student/parent persist + polling JSON.
 */
class UserChatController extends Controller
{
    public function __construct(
        protected ChatService $chat,
        protected SchoolContext $school,
    ) {
    }

    public function index(): View
    {
        $role = $this->portalRole();

        return view('shared::layouts.student_parent', [
            'title' => 'Chat System',
            'contentView' => 'chat::admin.chat',
            'pageTitle' => 'Chat System',
            'chatRoutePrefix' => 'user/chat',
            'delete_chat_enable' => $this->chat->portalDeleteChatEnabled($role),
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
        ]);
    }

    public function searchuser(Request $request): JsonResponse
    {
        $role = $this->portalRole();
        $studentId = $this->portalStudentId();
        $mine = $this->chat->getMyId($studentId, $role);
        $page = view('chat::admin._partialSearchUser', [
            'chat_user' => $this->chat->searchForUser(
                (string) $request->input('keyword', ''),
                $mine ? (int) $mine->id : 0,
                $studentId,
                $role,
            ),
            'useMiddle' => (string) $this->school->get('middlename', 'disabled') === 'enabled',
            'useLast' => (string) $this->school->get('lastname', 'enabled') !== 'disabled',
        ])->render();

        return response()->json(['status' => '1', 'error' => '', 'page' => $page]);
    }

    public function myuser(): JsonResponse
    {
        $role = $this->portalRole();
        $studentId = $this->portalStudentId();
        $mine = $this->chat->getMyId($studentId, $role);
        $userList = ['chat_users' => [], 'chat_user_notification' => []];
        if ($mine) {
            $userList = $this->chat->myUser($studentId, (int) $mine->id);
        }
        $page = view('chat::admin._partialmyuser', [
            'chat_user' => $mine,
            'userList' => $userList,
            'chat' => $this->chat,
        ])->render();

        return response()->json(['status' => '1', 'error' => '', 'page' => $page]);
    }

    public function getChatRecord(Request $request): JsonResponse
    {
        $role = $this->portalRole();
        $mine = $this->chat->getMyId($this->portalStudentId(), $role);
        abort_if($mine === null, 404);

        $connectionId = (int) $request->input('chat_connection_id');
        $connection = $this->chat->getChatConnectionById($connectionId);
        $chatToUser = 0;
        if ($connection) {
            $chatToUser = (int) $connection->chat_user_one === (int) $mine->id
                ? (int) $connection->chat_user_two
                : (int) $connection->chat_user_one;
        }
        $last = $this->chat->getLastMessages($connectionId);
        $page = view('chat::admin._partialChatRecord', [
            'chatList' => $this->chat->myChatAndUpdate($connectionId, (int) $mine->id),
            'chat_user' => $mine,
            'delete_chat_enable' => $this->chat->portalDeleteChatEnabled($role),
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
        ])->render();

        return response()->json([
            'status' => '1',
            'error' => '',
            'page' => $page,
            'chat_to_user' => $chatToUser,
            'chat_connection_id' => $connectionId,
            'user_last_chat' => $last ?: (object) ['id' => 0],
        ]);
    }

    public function newMessage(Request $request): JsonResponse
    {
        $id = $this->chat->addMessage([
            'chat_user_id' => (int) $request->input('chat_to_user'),
            'message' => trim((string) $request->input('message', '')),
            'chat_connection_id' => (int) $request->input('chat_connection_id'),
            'created_at' => $this->chat->parseMessageTime($request->input('time')),
        ]);

        return response()->json([
            'status' => '1',
            'last_insert_id' => $id,
            'error' => '',
            'message' => 'Inserted',
        ]);
    }

    public function chatUpdate(Request $request): JsonResponse
    {
        $mine = $this->chat->getMyId($this->portalStudentId(), $this->portalRole());
        abort_if($mine === null, 404);
        $connectionId = (int) $request->input('chat_connection_id');
        $chatToUser = (int) $request->input('chat_to_user');
        $page = view('chat::admin._chatupdate', [
            'updated_chat' => $this->chat->getUpdatedChat(
                $connectionId,
                (int) $request->input('last_chat_id'),
                (int) $mine->id,
            ),
            'chat_user_id' => $chatToUser,
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
        ])->render();

        return response()->json([
            'status' => '1',
            'error' => '',
            'page' => $page,
            'user_last_chat' => $this->chat->getLastMessages($connectionId) ?: (object) ['id' => 0],
        ]);
    }

    public function adduser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required'],
            'user_type' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'error' => ['user_id' => $validator->errors()->first('user_id')],
                'msg' => 'Something went wrong',
            ]);
        }

        $userType = (string) $request->input('user_type');
        $userId = (int) $request->input('user_id');
        $studentId = $this->portalStudentId();
        $role = $this->portalRole();
        $insertData = ['user_type' => strtolower($userType), 'create_student_id' => null];
        if (strcasecmp($userType, 'Student') === 0) {
            $insertData['student_id'] = $userId;
        } elseif (strcasecmp($userType, 'Staff') === 0) {
            $insertData['staff_id'] = $userId;
        }

        $created = $this->chat->addNewUserForStudent(
            ['user_type' => $role, 'student_id' => $studentId],
            $insertData,
            $studentId,
        );
        $newUser = $this->chat->getChatUserDetail($created['new_user_id']);
        abort_if($newUser === null, 404);
        $newUser = $this->chat->formatUserForPortalAdd($newUser);
        $mine = $this->chat->getMyId($studentId, $role);
        abort_if($mine === null, 404);
        $connectionId = $created['new_user_chat_connection_id'];
        $page = view('chat::admin._partialChatRecord', [
            'chatList' => $this->chat->myChatAndUpdate($connectionId, (int) $mine->id),
            'chat_user' => $mine,
            'delete_chat_enable' => $this->chat->portalDeleteChatEnabled($role),
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
        ])->render();

        return response()->json([
            'status' => '1',
            'error' => '',
            'message' => 'Record saved successfully.',
            'new_user' => $newUser,
            'chat_connection_id' => $connectionId,
            'chat_records' => $page,
            'user_last_chat' => $this->chat->getLastMessages($connectionId) ?: (object) ['id' => 0],
        ]);
    }

    public function mychatnotification(): JsonResponse
    {
        // CI user/Chat::mychatnotification hardcodes user_type student.
        $mine = $this->chat->getMyId($this->portalStudentId(), 'student');
        $notifications = $mine ? $this->chat->getChatNotification((int) $mine->id) : [];

        return response()->json([
            'status' => '1',
            'message' => 'Record saved successfully.',
            'notifications' => $notifications,
        ]);
    }

    public function mynewuser(Request $request): JsonResponse
    {
        $mine = $this->chat->getMyId($this->portalStudentId(), $this->portalRole());
        $list = ['chat_users' => [], 'chat_user_notification' => []];
        if ($mine) {
            $list = $this->chat->myNewUser((int) $mine->id, (array) $request->input('users', []));
        }
        $html = view('chat::admin._partialmynewuser', [
            'chat_user' => $mine,
            'userList' => $list,
            'chat' => $this->chat,
        ])->render();

        return response()->json([
            'status' => '1',
            'error' => '',
            'message' => 'Record saved successfully.',
            'new_user_list' => $html,
        ]);
    }

    public function delete_msg(Request $request): Response
    {
        $this->chat->deleteMessage((int) $request->input('msg_id'));

        return response('');
    }

    public function get_active_chat_msg(Request $request): JsonResponse
    {
        return response()->json([
            'status' => '1',
            'error' => '',
            'chatList' => $this->chat->getActiveChatMsg((int) $request->input('chat_connection_id')),
        ]);
    }

    public function get_student_parent_chat_msg_count(): JsonResponse
    {
        $role = $this->portalRole();
        $studentId = $this->portalStudentId();
        if ($studentId <= 0) {
            return response()->json(['status' => 0, 'error' => 'Not logged in', 'count' => 0]);
        }

        return response()->json([
            'status' => '1',
            'error' => '',
            'count' => count($this->chat->unreadPortalConnectionCount($studentId, $role)),
        ]);
    }

    protected function portalRole(): string
    {
        $user = Auth::guard('student_parent')->user();

        return $user && (string) ($user->role ?? '') === 'parent' ? 'parent' : 'student';
    }

    protected function portalStudentId(): int
    {
        $studentSessionId = (int) (session('current_class.student_session_id') ?? 0);
        if ($studentSessionId > 0) {
            $studentId = (int) DB::table('student_session')->where('id', $studentSessionId)->value('student_id');
            if ($studentId > 0) {
                return $studentId;
            }
        }

        $user = Auth::guard('student_parent')->user();
        if ($user instanceof PortalUser) {
            return (int) $user->user_id;
        }

        return 0;
    }
}
