<?php

namespace App\Modules\Communication\Services;

use App\Modules\Communication\Models\EmailTemplate;
use App\Modules\Communication\Models\EmailTemplateAttachment;
use App\Modules\Communication\Models\SmsTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * CI Messages_model email_template / sms_template CRUD.
 * SaaS storage quota is deferred.
 */
class MailSmsTemplateService
{
    public const EMAIL_DIR = 'uploads/communicate/email_template_images';

    /**
     * @return list<array<string, mixed>>
     */
    public function listEmailTemplates(): array
    {
        return EmailTemplate::query()
            ->orderBy('id')
            ->get()
            ->map(fn (EmailTemplate $row) => $row->toArray())
            ->all();
    }

    public function findEmailTemplate(int $id): ?EmailTemplate
    {
        return EmailTemplate::query()->find($id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function emailAttachments(int $templateId): array
    {
        return EmailTemplateAttachment::query()
            ->where('email_template_id', $templateId)
            ->orderBy('id')
            ->get()
            ->map(fn (EmailTemplateAttachment $row) => $row->toArray())
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<UploadedFile>  $files
     */
    public function addEmailTemplate(array $input, array $files = []): EmailTemplate
    {
        $row = EmailTemplate::query()->create([
            'title' => (string) ($input['title'] ?? ''),
            'message' => (string) ($input['message'] ?? ''),
            'created_at' => date('Y-m-d'),
        ]);
        $this->storeNewAttachments((int) $row->id, $files);

        return $row;
    }

    /**
     * CI update_email_template: rewrite attachment rows; drop files not kept.
     *
     * @param  array<string, mixed>  $input
     * @param  array<int|string, string>  $keepPosted  template_attachment[id] => saved filename
     * @param  list<UploadedFile>  $files
     */
    public function updateEmailTemplate(EmailTemplate $row, array $input, array $keepPosted, array $files = []): EmailTemplate
    {
        $row->title = (string) ($input['title'] ?? '');
        $row->message = (string) ($input['message'] ?? '');
        $row->created_at = date('Y-m-d');
        $row->save();

        $keep = $this->resolveKeptAttachments((int) $row->id, $keepPosted);
        $existing = $this->emailAttachments((int) $row->id);
        $keepFiles = array_column($keep, 'attachment');

        foreach ($existing as $attachment) {
            $saved = (string) ($attachment['attachment'] ?? '');
            if ($saved !== '' && ! in_array($saved, $keepFiles, true)) {
                $this->deleteAttachmentFile($saved);
                DB::table('email_attachments')->where('attachment', $saved)->delete();
            }
        }

        EmailTemplateAttachment::query()->where('email_template_id', $row->id)->delete();
        foreach ($keep as $item) {
            EmailTemplateAttachment::query()->create([
                'email_template_id' => (int) $row->id,
                'attachment' => $item['attachment'],
                'attachment_name' => $item['attachment_name'],
            ]);
        }
        $this->storeNewAttachments((int) $row->id, $files);

        return $row;
    }

    public function deleteEmailTemplate(int $id): void
    {
        $row = $this->findEmailTemplate($id);
        if ($row === null) {
            return;
        }
        foreach ($this->emailAttachments($id) as $attachment) {
            $saved = (string) ($attachment['attachment'] ?? '');
            if ($saved !== '') {
                $this->deleteAttachmentFile($saved);
                DB::table('email_attachments')->where('attachment', $saved)->delete();
            }
        }
        EmailTemplateAttachment::query()->where('email_template_id', $id)->delete();
        $row->delete();
    }

    /**
     * CI templatedata JSON payload.
     *
     * @return array{data: array<string, mixed>|null, attachment_list: string}
     */
    public function emailTemplateData(int $id): array
    {
        $row = $this->findEmailTemplate($id);
        if ($row === null) {
            return ['data' => null, 'attachment_list' => ''];
        }

        $html = '';
        foreach ($this->emailAttachments($id) as $attachment) {
            $html .= $this->attachmentDiv(
                (string) $attachment['attachment_name'],
                (string) $attachment['attachment'],
                (int) $attachment['id'],
            );
        }

        return [
            'data' => $row->toArray(),
            'attachment_list' => $html,
        ];
    }

    public function viewDocumentsHtml(int $templateId): string
    {
        $items = $this->emailAttachments($templateId);
        if ($items === []) {
            return '<p class="text-muted">No Record Found</p>';
        }
        $html = '<div class="row">';
        foreach ($items as $value) {
            $file = (string) ($value['attachment'] ?? '');
            $name = (string) ($value['attachment_name'] ?? $file);
            $url = url(self::EMAIL_DIR.'/'.$file);
            $html .= "<div class='col-lg-2 col-sm-4 col-md-3 col-xs-6'><p>{$this->e($name)}</p>";
            $html .= "<a href=\"{$url}\" download>Download</a></div>";
        }
        $html .= '</div>';

        return $html;
    }

    public function attachmentPath(string $doc): ?string
    {
        $doc = basename($doc);
        if ($doc === '' || $doc === '.' || $doc === '..') {
            return null;
        }
        $path = public_path(self::EMAIL_DIR.DIRECTORY_SEPARATOR.$doc);
        if (! File::exists($path)) {
            return null;
        }

        return $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listSmsTemplates(): array
    {
        return SmsTemplate::query()
            ->orderBy('id')
            ->get()
            ->map(fn (SmsTemplate $row) => $row->toArray())
            ->all();
    }

    public function findSmsTemplate(int $id): ?SmsTemplate
    {
        return SmsTemplate::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function addSmsTemplate(array $input): SmsTemplate
    {
        return SmsTemplate::query()->create([
            'title' => (string) ($input['title'] ?? ''),
            'message' => (string) ($input['message'] ?? ''),
            'created_at' => date('Y-m-d'),
        ]);
    }

    /**
     * CI also writes created_at on update.
     *
     * @param  array<string, mixed>  $input
     */
    public function updateSmsTemplate(SmsTemplate $row, array $input): SmsTemplate
    {
        $row->title = (string) ($input['title'] ?? '');
        $row->message = (string) ($input['message'] ?? '');
        $row->created_at = date('Y-m-d');
        $row->save();

        return $row;
    }

    public function deleteSmsTemplate(int $id): void
    {
        SmsTemplate::query()->where('id', $id)->delete();
    }

    /**
     * @return array{data: array<string, mixed>|null}
     */
    public function smsTemplateData(int $id): array
    {
        $row = $this->findSmsTemplate($id);

        return ['data' => $row?->toArray()];
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    protected function storeNewAttachments(int $templateId, array $files): void
    {
        $dir = public_path(self::EMAIL_DIR);
        File::ensureDirectoryExists($dir);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $original = basename((string) $file->getClientOriginalName());
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $saved = time().'-'.uniqid((string) random_int(1000, 9999), false);
            if ($ext !== '') {
                $saved .= '.'.$ext;
            }
            $file->move($dir, $saved);
            EmailTemplateAttachment::query()->create([
                'email_template_id' => $templateId,
                'attachment' => $saved,
                'attachment_name' => $original,
            ]);
        }
    }

    /**
     * @param  array<int|string, mixed>  $keepPosted
     * @return list<array{attachment: string, attachment_name: string}>
     */
    protected function resolveKeptAttachments(int $templateId, array $keepPosted): array
    {
        $out = [];
        foreach ($keepPosted as $key => $value) {
            $filename = is_array($value) ? (string) ($value['attachment'] ?? '') : (string) $value;
            if ($filename === '') {
                continue;
            }
            $existing = EmailTemplateAttachment::query()
                ->where('email_template_id', $templateId)
                ->where(function ($q) use ($key, $filename) {
                    $q->where('attachment', $filename);
                    if (is_numeric($key)) {
                        $q->orWhere('id', (int) $key);
                    }
                })
                ->first();
            if ($existing === null) {
                continue;
            }
            $out[] = [
                'attachment' => (string) $existing->attachment,
                'attachment_name' => (string) $existing->attachment_name,
            ];
        }

        return $out;
    }

    protected function deleteAttachmentFile(string $saved): void
    {
        $path = public_path(self::EMAIL_DIR.DIRECTORY_SEPARATOR.basename($saved));
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    protected function attachmentDiv(string $name, string $saved, int $id): string
    {
        $deleteId = time().random_int(99, 999);

        return "<div class='col-sm-3 img_div_modal' id='image_div_{$deleteId}'>"
            ."<p><a href='#' onclick='removeAttachment({$deleteId});return false;'><i class='fa fa-trash-o'></i></a> "
            .$this->e($name)
            ."</p><input type='hidden' name='template_attachment[{$id}]' value='".$this->e($saved)."' /></div>";
    }

    protected function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
