<?php

namespace App\Modules\Communication\Services;

use App\Modules\Communication\Models\SmsConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI Smsconfig_model — upsert by gateway type; enabling one disables the others.
 * Runtime send (Smsgateway / test_sms) deferred to Mailsms.
 */
class SmsConfigService
{
    /**
     * @return array<string, string>
     */
    public function statusList(): array
    {
        return [
            '' => 'Select',
            'enabled' => 'Enabled',
            'disabled' => 'Disabled',
        ];
    }

    /**
     * Gateway types and CI field → column mapping.
     *
     * @return array<string, array{label: string, fields: list<array{name: string, label: string, column: string, input?: string, required: bool, options?: array<string, string>}>}>
     */
    public function gateways(): array
    {
        $status = $this->statusList();

        return [
            'clickatell' => [
                'label' => 'Clickatell SMS Gateway',
                'action' => 'clickatell',
                'fields' => [
                    ['name' => 'clickatell_user', 'label' => 'Username', 'column' => 'username', 'required' => true],
                    ['name' => 'clickatell_password', 'label' => 'Password', 'column' => 'password', 'input' => 'password', 'required' => true],
                    ['name' => 'clickatell_api_id', 'label' => 'API Key', 'column' => 'api_id', 'required' => true],
                    ['name' => 'clickatell_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'twilio' => [
                'label' => 'Twilio SMS Gateway',
                'action' => 'twilio',
                'fields' => [
                    ['name' => 'twilio_account_sid', 'label' => 'Twilio Account SID', 'column' => 'api_id', 'required' => true],
                    ['name' => 'twilio_auth_token', 'label' => 'Authentication Token', 'column' => 'password', 'input' => 'password', 'required' => true],
                    ['name' => 'twilio_sender_phone_number', 'label' => 'Registered Phone Number', 'column' => 'contact', 'required' => true],
                    ['name' => 'twilio_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'msg_nineone' => [
                'label' => 'MSG91',
                'action' => 'msgnineone',
                'fields' => [
                    ['name' => 'authkey', 'label' => 'Auth Key', 'column' => 'authkey', 'required' => true],
                    ['name' => 'senderid', 'label' => 'Sender ID', 'column' => 'senderid', 'required' => true],
                    ['name' => 'msg_nineone_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'text_local' => [
                'label' => 'Text Local',
                'action' => 'textlocal',
                'fields' => [
                    ['name' => 'text_local', 'label' => 'Username', 'column' => 'username', 'required' => true],
                    ['name' => 'text_localpassword', 'label' => 'Password', 'column' => 'password', 'input' => 'password', 'required' => true],
                    ['name' => 'text_localsenderid', 'label' => 'Sender ID', 'column' => 'senderid', 'required' => true],
                    ['name' => 'text_local_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'smscountry' => [
                'label' => 'SMS Country',
                'action' => 'smscountry',
                'fields' => [
                    ['name' => 'smscountry', 'label' => 'Username', 'column' => 'username', 'required' => true],
                    ['name' => 'smscountryauthKey', 'label' => 'Auth Key', 'column' => 'authkey', 'required' => true],
                    ['name' => 'smscountryauthtoken', 'label' => 'Authentication Token', 'column' => 'api_id', 'required' => true],
                    ['name' => 'smscountrysenderid', 'label' => 'Sender ID', 'column' => 'senderid', 'required' => true],
                    ['name' => 'smscountrypassword', 'label' => 'Password', 'column' => 'password', 'input' => 'password', 'required' => true],
                    ['name' => 'smscountry_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'bulk_sms' => [
                'label' => 'Bulk SMS',
                'action' => 'bulk_sms',
                'fields' => [
                    ['name' => 'bulk_sms_user_name', 'label' => 'Username', 'column' => 'username', 'required' => true],
                    ['name' => 'bulk_sms_user_password', 'label' => 'Password', 'column' => 'password', 'input' => 'password', 'required' => true],
                    ['name' => 'bulk_sms_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'mobireach' => [
                'label' => 'MobiReach',
                'action' => 'mobireach',
                'fields' => [
                    ['name' => 'mobireach_auth_key', 'label' => 'Auth Key', 'column' => 'authkey', 'required' => true],
                    ['name' => 'mobireach_sender_id', 'label' => 'Sender ID', 'column' => 'senderid', 'required' => true],
                    ['name' => 'mobireach_route_id', 'label' => 'Route ID', 'column' => 'api_id', 'required' => true],
                    ['name' => 'mobireach_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'nexmo' => [
                'label' => 'Nexmo',
                'action' => 'nexmo',
                'fields' => [
                    ['name' => 'nexmo_api_key', 'label' => 'Nexmo API Key', 'column' => 'api_id', 'required' => true],
                    ['name' => 'nexmo_api_secret', 'label' => 'Nexmo API Secret', 'column' => 'authkey', 'required' => true],
                    ['name' => 'registered_from_number', 'label' => 'Registered From Number', 'column' => 'senderid', 'required' => true],
                    ['name' => 'nexmo_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'africastalking' => [
                'label' => 'AfricasTalking',
                'action' => 'africastalking',
                'fields' => [
                    ['name' => 'africastalking_username', 'label' => 'Username', 'column' => 'username', 'required' => true],
                    ['name' => 'africastalking_apikey', 'label' => 'API Key', 'column' => 'api_id', 'required' => true],
                    ['name' => 'africastalking_short_code', 'label' => 'Short Code', 'column' => 'senderid', 'required' => false],
                    ['name' => 'africastalking_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'smseg' => [
                'label' => 'SMS Egypt',
                'action' => 'smseg',
                'fields' => [
                    ['name' => 'smseg_username', 'label' => 'Username', 'column' => 'username', 'required' => true],
                    ['name' => 'smseg_password', 'label' => 'Password', 'column' => 'password', 'input' => 'password', 'required' => true],
                    ['name' => 'smseg_sender_id', 'label' => 'Sender ID', 'column' => 'senderid', 'required' => true],
                    ['name' => 'smseg_type', 'label' => 'Type', 'column' => 'url', 'input' => 'select', 'required' => true, 'options' => [
                        '' => 'Select',
                        'https://smssmartegypt.com/sms/api/?' => 'Local SMS',
                        'https://smssmartegypt.com/sms/api/InterAPI?' => 'International SMS',
                    ]],
                    ['name' => 'smseg_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
            'smsgatewayhub' => [
                'label' => 'SMS Gateway Hub',
                'action' => 'smsgatewayhub',
                'fields' => [
                    ['name' => 'smsgatewayhub_authkey', 'label' => 'Auth Key', 'column' => 'authkey', 'required' => true],
                    ['name' => 'smsgatewayhub_senderid', 'label' => 'Sender ID', 'column' => 'senderid', 'required' => true],
                    ['name' => 'smsgatewayhub_entityid', 'label' => 'Entity ID', 'column' => 'api_id', 'required' => true],
                    ['name' => 'smsgatewayhub_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => false, 'options' => $status],
                ],
            ],
            'custom' => [
                'label' => 'Custom SMS Gateway',
                'action' => 'custom',
                'fields' => [
                    ['name' => 'name', 'label' => 'Gateway Name', 'column' => 'name', 'required' => true],
                    ['name' => 'custom_status', 'label' => 'Status', 'column' => 'is_active', 'input' => 'status', 'required' => true, 'options' => $status],
                ],
            ],
        ];
    }

    /**
     * @return array<string, SmsConfig>
     */
    public function keyedByType(): array
    {
        $out = [];
        foreach (SmsConfig::query()->orderBy('id')->get() as $row) {
            $out[(string) $row->type] = $row;
        }

        return $out;
    }

    /**
     * CI controller method names → sms_config.type
     *
     * @return array<string, string>
     */
    public function actionTypes(): array
    {
        return [
            'clickatell' => 'clickatell',
            'twilio' => 'twilio',
            'custom' => 'custom',
            'msgnineone' => 'msg_nineone',
            'smscountry' => 'smscountry',
            'textlocal' => 'text_local',
            'bulk_sms' => 'bulk_sms',
            'smsgatewayhub' => 'smsgatewayhub',
            'mobireach' => 'mobireach',
            'nexmo' => 'nexmo',
            'africastalking' => 'africastalking',
            'smseg' => 'smseg',
        ];
    }

    public function typeForAction(string $action): ?string
    {
        return $this->actionTypes()[$action] ?? null;
    }

    public function findByType(string $type): SmsConfig
    {
        $row = SmsConfig::query()->where('type', $type)->first();
        if ($row) {
            return $row;
        }

        $blank = new SmsConfig();
        $blank->type = $type;
        $blank->name = '';
        $blank->api_id = '';
        $blank->authkey = '';
        $blank->senderid = '';
        $blank->contact = '';
        $blank->username = '';
        $blank->url = '';
        $blank->password = '';
        $blank->is_active = '';

        return $blank;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(string $type, array $input): SmsConfig
    {
        $gateways = $this->gateways();
        if (! isset($gateways[$type])) {
            throw ValidationException::withMessages([
                'type' => 'Unknown SMS gateway.',
            ]);
        }

        $payload = ['type' => $type];
        foreach ($gateways[$type]['fields'] as $field) {
            $payload[$field['column']] = (string) ($input[$field['name']] ?? '');
        }

        $existing = SmsConfig::query()->where('type', $type)->first();
        if (! isset($payload['name']) || $payload['name'] === '') {
            $payload['name'] = (string) ($existing?->name ?: $type);
        }

        if (! $existing) {
            $payload = array_merge([
                'api_id' => '',
                'authkey' => '',
                'senderid' => '',
                'contact' => '',
                'username' => '',
                'url' => '',
                'password' => '',
                'is_active' => 'disabled',
            ], $payload);
        }

        return DB::transaction(function () use ($type, $payload, $existing) {
            if ($existing) {
                $existing->fill($payload)->save();
                $row = $existing;
            } else {
                $row = SmsConfig::query()->create($payload);
            }

            if (($payload['is_active'] ?? '') === 'enabled') {
                SmsConfig::query()
                    ->where('type', '!=', $type)
                    ->update(['is_active' => 'disabled']);
            }

            return $row->fresh();
        });
    }
}
