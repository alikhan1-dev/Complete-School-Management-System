<?php

use App\Modules\Communication\Controllers\EmailConfigController;
use App\Modules\Communication\Controllers\MailSmsController;
use App\Modules\Communication\Controllers\MailSmsTemplateController;
use App\Modules\Communication\Controllers\ModuleStatusController;
use App\Modules\Communication\Controllers\NoticeBoardController;
use App\Modules\Communication\Controllers\NotificationSettingController;
use App\Modules\Communication\Controllers\SmsConfigController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/communication', [ModuleStatusController::class, 'status'])->name('communication.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI Emailconfig
    Route::get('emailconfig', [EmailConfigController::class, 'index'])->name('communication.emailconfig.index');
    Route::get('emailconfig/index', [EmailConfigController::class, 'index']);
    Route::post('emailconfig', [EmailConfigController::class, 'save'])->name('communication.emailconfig.save');
    Route::post('emailconfig/index', [EmailConfigController::class, 'save']);

    // CI Smsconfig
    Route::get('smsconfig', [SmsConfigController::class, 'index'])->name('communication.smsconfig.index');
    Route::get('smsconfig/index', [SmsConfigController::class, 'index']);
    Route::post('smsconfig/{action}', [SmsConfigController::class, 'save'])
        ->where('action', 'clickatell|twilio|custom|msgnineone|smscountry|textlocal|bulk_sms|smsgatewayhub|mobireach|nexmo|africastalking|smseg')
        ->name('communication.smsconfig.save');

    // CI admin/notification — notice board
    Route::get('admin/notification', [NoticeBoardController::class, 'index'])->name('communication.notice.index');
    Route::get('admin/notification/index', [NoticeBoardController::class, 'index']);
    Route::get('admin/notification/add', [NoticeBoardController::class, 'create'])->name('communication.notice.create');
    Route::post('admin/notification/add', [NoticeBoardController::class, 'store'])->name('communication.notice.store');
    Route::get('admin/notification/edit/{id}', [NoticeBoardController::class, 'edit'])->whereNumber('id')->name('communication.notice.edit');
    Route::post('admin/notification/edit/{id}', [NoticeBoardController::class, 'update'])->whereNumber('id')->name('communication.notice.update');
    Route::get('admin/notification/delete/{id}', [NoticeBoardController::class, 'destroy'])->whereNumber('id')->name('communication.notice.destroy');
    Route::get('admin/notification/download/{id}', [NoticeBoardController::class, 'download'])->whereNumber('id')->name('communication.notice.download');
    Route::post('admin/notification/notification', [NoticeBoardController::class, 'detail'])->name('communication.notice.detail');
    Route::match(['get', 'post'], 'admin/notification/delete_notice_board_log', [NoticeBoardController::class, 'deletePastLogs'])
        ->name('communication.notice.delete_past');

    // CI admin/notification/setting + templates
    Route::get('admin/notification/setting', [NotificationSettingController::class, 'index'])->name('communication.notification_setting.index');
    Route::post('admin/notification/setting', [NotificationSettingController::class, 'save'])->name('communication.notification_setting.save');
    Route::get('admin/notification/template/{id}', [NotificationSettingController::class, 'editTemplate'])->whereNumber('id')->name('communication.notification_setting.template');
    Route::post('admin/notification/gettemplate', function (\Illuminate\Http\Request $request) {
        $id = (int) $request->input('id');
        abort_if($id <= 0, 404);

        return redirect()->route('communication.notification_setting.template', $id);
    });
    Route::post('admin/notification/savetemplate', [NotificationSettingController::class, 'saveTemplate'])->name('communication.notification_setting.template.save');
    Route::get('admin/notification/view_template/{id}', [NotificationSettingController::class, 'viewTemplate'])->whereNumber('id')->name('communication.notification_setting.template.view');
    Route::post('admin/notification/view_template', function (\Illuminate\Http\Request $request) {
        $id = (int) $request->input('id');
        abort_if($id <= 0, 404);

        return redirect()->route('communication.notification_setting.template.view', $id);
    });

    // CI admin/mailsms — log + group email persist
    Route::get('admin/mailsms', [MailSmsController::class, 'index'])->name('communication.mailsms.index');
    Route::get('admin/mailsms/index', [MailSmsController::class, 'index']);
    Route::get('admin/mailsms/schedule', [MailSmsController::class, 'schedule'])->name('communication.mailsms.schedule');
    Route::get('admin/mailsms/edit_schedule/{id}/{type?}', [MailSmsController::class, 'editSchedule'])
        ->whereNumber('id')
        ->name('communication.mailsms.schedule.edit');
    Route::post('admin/mailsms/edit_schedule/{id}/{type?}', [MailSmsController::class, 'updateSchedule'])
        ->whereNumber('id')
        ->name('communication.mailsms.schedule.update');
    Route::match(['get', 'post'], 'admin/mailsms/delete_schedule', [MailSmsController::class, 'deleteSchedule'])
        ->name('communication.mailsms.schedule.delete');
    Route::post('admin/mailsms/update_group_schedule', [MailSmsController::class, 'updateGroupSchedule'])
        ->name('communication.mailsms.schedule.update_group');
    Route::post('admin/mailsms/update_individual_schedule', [MailSmsController::class, 'updateIndividualSchedule'])
        ->name('communication.mailsms.schedule.update_individual');
    Route::post('admin/mailsms/update_class_schedule', [MailSmsController::class, 'updateClassSchedule'])
        ->name('communication.mailsms.schedule.update_class');
    Route::post('admin/mailsms/update_group_sms_schedule', [MailSmsController::class, 'updateGroupSmsSchedule'])
        ->name('communication.mailsms.schedule.update_group_sms');
    Route::post('admin/mailsms/update_individual_sms_schedule', [MailSmsController::class, 'updateIndividualSmsSchedule'])
        ->name('communication.mailsms.schedule.update_individual_sms');
    Route::post('admin/mailsms/update_class_sms_schedule', [MailSmsController::class, 'updateClassSmsSchedule'])
        ->name('communication.mailsms.schedule.update_class_sms');
    Route::match(['get', 'post'], 'admin/mailsms/delete_email_sms_log', [MailSmsController::class, 'deleteLog'])
        ->name('communication.mailsms.delete_log');
    Route::get('admin/mailsms/compose', [MailSmsController::class, 'compose'])->name('communication.mailsms.compose');
    Route::post('admin/mailsms/send_group', [MailSmsController::class, 'sendGroup'])->name('communication.mailsms.send_group');
    Route::post('admin/mailsms/search', [MailSmsController::class, 'search'])->name('communication.mailsms.search');
    Route::post('admin/mailsms/send_individual', [MailSmsController::class, 'sendIndividual'])->name('communication.mailsms.send_individual');
    Route::post('admin/mailsms/send_class', [MailSmsController::class, 'sendClass'])->name('communication.mailsms.send_class');
    Route::post('admin/mailsms/send_birthday', [MailSmsController::class, 'sendBirthday'])->name('communication.mailsms.send_birthday');
    Route::get('admin/mailsms/compose_sms', [MailSmsController::class, 'composeSms'])->name('communication.mailsms.compose_sms');
    Route::post('admin/mailsms/send_group_sms', [MailSmsController::class, 'sendGroupSms'])->name('communication.mailsms.send_group_sms');
    Route::post('admin/mailsms/send_individual_sms', [MailSmsController::class, 'sendIndividualSms'])->name('communication.mailsms.send_individual_sms');
    Route::post('admin/mailsms/send_class_sms', [MailSmsController::class, 'sendClassSms'])->name('communication.mailsms.send_class_sms');
    Route::post('admin/mailsms/send_birthday_sms', [MailSmsController::class, 'sendBirthdaySms'])->name('communication.mailsms.send_birthday_sms');

    // CI admin/mailsms email_template + sms_template
    Route::get('admin/mailsms/email_template', [MailSmsTemplateController::class, 'emailIndex'])->name('communication.mailsms.email_template');
    Route::get('admin/mailsms/add_email_template', [MailSmsTemplateController::class, 'emailCreate'])->name('communication.mailsms.email_template.create');
    Route::post('admin/mailsms/add_email_template', [MailSmsTemplateController::class, 'addEmailTemplate'])->name('communication.mailsms.email_template.store');
    Route::get('admin/mailsms/edit_email_template/{id}', [MailSmsTemplateController::class, 'emailEdit'])->whereNumber('id')->name('communication.mailsms.email_template.edit');
    Route::post('admin/mailsms/edit_email_template', [MailSmsTemplateController::class, 'editEmailTemplateJson']);
    Route::post('admin/mailsms/update_email_template', [MailSmsTemplateController::class, 'updateEmailTemplate'])->name('communication.mailsms.email_template.update');
    Route::get('admin/mailsms/delete_email_template/{id}', [MailSmsTemplateController::class, 'deleteEmailTemplate'])->whereNumber('id')->name('communication.mailsms.email_template.delete');
    Route::post('admin/mailsms/viewdocuments', [MailSmsTemplateController::class, 'viewDocuments'])->name('communication.mailsms.email_template.documents');
    Route::get('admin/mailsms/email_template_download/{doc}/{name?}', [MailSmsTemplateController::class, 'download'])
        ->name('communication.mailsms.email_template.download');
    Route::post('admin/mailsms/templatedata', [MailSmsTemplateController::class, 'templateData'])->name('communication.mailsms.templatedata');

    Route::get('admin/mailsms/sms_template/sms_template', [MailSmsTemplateController::class, 'smsIndex']);
    Route::get('admin/mailsms/sms_template', [MailSmsTemplateController::class, 'smsIndex'])->name('communication.mailsms.sms_template');
    Route::get('admin/mailsms/add_sms_template', [MailSmsTemplateController::class, 'smsCreate'])->name('communication.mailsms.sms_template.create');
    Route::post('admin/mailsms/add_sms_template', [MailSmsTemplateController::class, 'addSmsTemplate'])->name('communication.mailsms.sms_template.store');
    Route::get('admin/mailsms/edit_sms_template/{id}', [MailSmsTemplateController::class, 'smsEdit'])->whereNumber('id')->name('communication.mailsms.sms_template.edit');
    Route::post('admin/mailsms/edit_sms_template', [MailSmsTemplateController::class, 'editSmsTemplateJson']);
    Route::post('admin/mailsms/update_sms_template', [MailSmsTemplateController::class, 'updateSmsTemplate'])->name('communication.mailsms.sms_template.update');
    Route::get('admin/mailsms/delete_sms_template/{id}', [MailSmsTemplateController::class, 'deleteSmsTemplate'])->whereNumber('id')->name('communication.mailsms.sms_template.delete');
    Route::post('admin/mailsms/smstemplatedata', [MailSmsTemplateController::class, 'smsTemplateData'])->name('communication.mailsms.smstemplatedata');
});
