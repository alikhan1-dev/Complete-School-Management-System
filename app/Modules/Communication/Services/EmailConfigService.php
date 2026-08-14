<?php

namespace App\Modules\Communication\Services;

use App\Modules\Communication\Models\EmailConfig;
use Illuminate\Validation\ValidationException;

/**
 * CI Emailconfig_model — single-row upsert of mail engine settings.
 * Runtime send (PHPMailer / AWS SES) deferred to compose/Mailsmsconf.
 */
class EmailConfigService
{
    /**
     * @return array<string, string>
     */
    public function mailMethods(): array
    {
        return [
            'sendmail' => 'SendMail',
            'smtp' => 'SMTP',
            'aws_ses' => 'AWS SES',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function smtpEncryption(): array
    {
        return [
            '' => 'OFF',
            'ssl' => 'SSL',
            'tls' => 'TLS',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function smtpAuth(): array
    {
        return [
            'true' => 'ON',
            'false' => 'OFF',
        ];
    }

    public function current(): EmailConfig
    {
        $row = EmailConfig::query()->orderBy('id')->first();
        if ($row) {
            return $row;
        }

        $row = new EmailConfig();
        $row->email_type = 'sendmail';
        $row->smtp_server = '';
        $row->smtp_port = '';
        $row->smtp_email = '';
        $row->smtp_username = '';
        $row->smtp_password = '';
        $row->ssl_tls = '';
        $row->smtp_auth = 'false';
        $row->api_key = '';
        $row->api_secret = '';
        $row->region = '';
        $row->is_active = 'yes';

        return $row;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function save(array $input): EmailConfig
    {
        $type = (string) ($input['email_type'] ?? '');
        if (! array_key_exists($type, $this->mailMethods())) {
            throw ValidationException::withMessages([
                'email_type' => 'The email type field is required.',
            ]);
        }

        // CI sets $email only for aws_ses / smtp; sendmail left the variable unset (null).
        $username = '';
        if ($type === 'aws_ses') {
            $username = (string) ($input['aws_email'] ?? '');
        } elseif ($type === 'smtp') {
            $username = (string) ($input['smtp_username'] ?? '');
        }

        $payload = [
            'email_type' => $type,
            'smtp_email' => (string) ($input['smtp_email'] ?? ''),
            'smtp_username' => $username,
            'smtp_password' => (string) ($input['smtp_password'] ?? ''),
            'smtp_server' => (string) ($input['smtp_server'] ?? ''),
            'smtp_port' => (string) ($input['smtp_port'] ?? ''),
            'ssl_tls' => (string) ($input['smtp_security'] ?? ''),
            'smtp_auth' => (string) ($input['smtp_auth'] ?? 'false'),
            'api_key' => (string) ($input['access_key'] ?? ''),
            'api_secret' => (string) ($input['secret_access_key'] ?? ''),
            'region' => (string) ($input['region'] ?? ''),
            'is_active' => 'yes',
        ];

        $row = EmailConfig::query()->orderBy('id')->first();
        if ($row) {
            $row->fill($payload)->save();

            return $row->fresh();
        }

        return EmailConfig::query()->create($payload);
    }
}
