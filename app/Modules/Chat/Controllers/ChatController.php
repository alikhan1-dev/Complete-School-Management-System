<?php

namespace App\Modules\Chat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Services\ChatService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * CI admin/chat — staff chat persist + polling JSON.
 * user/Chat portal is deferred.
 */
class ChatController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ChatService $chat,
        protected SchoolContext $school,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Chat System',
            'contentView' => 'chat::admin.chat',
            'pageTitle' => 'Chat System',
            'delete_chat_enable' => $this->chat->deleteChatEnabled(),
            'dateFormat' => $this->school->dateFormat() ?: 'd/m/Y',
        ]);
    }

    public function searchuser(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
        $staffId = $this->chat->currentStaffId();
        $mine = $this->chat->getMyId($staffId, 'staff');
        $page = view('chat::admin._partialSearchUser', [
            'chat_user' => $this->chat->searchForUser(
                (string) $request->input('keyword', ''),
                $mine ? (int) $mine->id : 0,
                $staffId,
            ),
            'useMiddle' => (string) $this->school->get('middlename', 'disabled') === 'enabled',
            'useLast' => (string) $this->school->get('lastname', 'enabled') !== 'disabled',
        ])->render();

        return response()->json(['status' => '1', 'error' => '', 'page' => $page]);
    }

    public function myuser(): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
        $staffId = $this->chat->currentStaffId();
        $mine = $this->chat->getMyId($staffId, 'staff');
        $userList = ['chat_users' => [], 'chat_user_notification' => []];
        if ($mine) {
            $userList = $this->chat->myUser($staffId, (int) $mine->id);
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
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
        $mine = $this->chat->getMyId($this->chat->currentStaffId(), 'staff');
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
            'delete_chat_enable' => $this->chat->deleteChatEnabled(),
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
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
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
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
        $mine = $this->chat->getMyId($this->chat->currentStaffId(), 'staff');
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
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
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
        $staffId = $this->chat->currentStaffId();
        $insertData = ['user_type' => strtolower($userType), 'create_staff_id' => null];
        if (strcasecmp($userType, 'Student') === 0) {
            $insertData['student_id'] = $userId;
        } else {
            $insertData['staff_id'] = $userId;
        }

        $created = $this->chat->addNewUser(
            ['user_type' => 'staff', 'staff_id' => $staffId],
            $insertData,
            $staffId,
        );
        $newUser = $this->chat->getChatUserDetail($created['new_user_id']);
        abort_if($newUser === null, 404);
        $newUser = $this->chat->formatUserForAdd($newUser);
        $mine = $this->chat->getMyId($staffId, 'staff');
        $connectionId = $created['new_user_chat_connection_id'];
        $page = view('chat::admin._partialChatRecord', [
            'chatList' => $this->chat->myChatAndUpdate($connectionId, (int) $mine->id),
            'chat_user' => $mine,
            'delete_chat_enable' => $this->chat->deleteChatEnabled(),
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
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
        $mine = $this->chat->getMyId($this->chat->currentStaffId(), 'staff');
        $notifications = $mine ? $this->chat->getChatNotification((int) $mine->id) : [];

        return response()->json([
            'status' => '1',
            'message' => 'Record saved successfully.',
            'notifications' => $notifications,
        ]);
    }

    public function mynewuser(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
        $mine = $this->chat->getMyId($this->chat->currentStaffId(), 'staff');
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
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);
        $this->chat->deleteMessage((int) $request->input('msg_id'));

        return response('deleted');
    }

    public function get_active_chat_msg(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('chat', 'can_view'), 403);

        return response()->json([
            'status' => '1',
            'error' => '',
            'chatList' => $this->chat->getActiveChatMsg((int) $request->input('chat_connection_id')),
        ]);
    }

    public function get_chat_msg_count(): JsonResponse
    {
        $staffId = $this->chat->currentStaffId();
        if ($staffId <= 0) {
            return response()->json(['status' => 0, 'error' => 'Not logged in', 'count' => 0]);
        }

        return response()->json([
            'status' => '1',
            'error' => '',
            'count' => count($this->chat->unreadConnectionCount($staffId)),
        ]);
    }
}
