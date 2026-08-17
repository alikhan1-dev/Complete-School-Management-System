<?php

use App\Modules\Settings\Controllers\CaptchaSettingController;
use App\Modules\Settings\Controllers\ModuleStatusController;
use App\Modules\Settings\Controllers\SchoolAttendanceScheduleController;
use App\Modules\Settings\Controllers\SchoolAttendanceTypeSettingController;
use App\Modules\Settings\Controllers\SchoolBackendThemeController;
use App\Modules\Settings\Controllers\SchoolChatSettingController;
use App\Modules\Settings\Controllers\SchoolCurrencySettingController;
use App\Modules\Settings\Controllers\SchoolFeesSettingController;
use App\Modules\Settings\Controllers\SchoolGeneralSettingController;
use App\Modules\Settings\Controllers\SchoolGoogleDriveSettingController;
use App\Modules\Settings\Controllers\SchoolIdAutoGenerationController;
use App\Modules\Settings\Controllers\SchoolLoginBackgroundController;
use App\Modules\Settings\Controllers\SchoolLogoController;
use App\Modules\Settings\Controllers\SchoolMaintenanceSettingController;
use App\Modules\Settings\Controllers\SchoolMiscellaneousSettingController;
use App\Modules\Settings\Controllers\SchoolMobileAppSettingController;
use App\Modules\Settings\Controllers\SchoolModuleSettingController;
use App\Modules\Settings\Controllers\SchoolStudentGuardianPanelController;
use App\Modules\Settings\Controllers\SchoolThemeCssController;
use App\Modules\Settings\Controllers\SchoolWhatsappSettingController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/settings', [ModuleStatusController::class, 'status'])->name('settings.migration_status');

Route::get('theme.css', [SchoolThemeCssController::class, 'css']);
Route::get('theme/css', [SchoolThemeCssController::class, 'css']);
Route::get('fronttheme.css', [SchoolThemeCssController::class, 'frontCss']);
Route::get('FrontTheme/css', [SchoolThemeCssController::class, 'frontCss']);

Route::middleware(['staff.auth'])->group(function () {
    Route::get('admin/captcha', [CaptchaSettingController::class, 'index']);
    Route::post('admin/captcha/changeStatus', [CaptchaSettingController::class, 'changeStatus']);

    Route::get('schsettings', [SchoolGeneralSettingController::class, 'index']);
    Route::get('schsettings/index', [SchoolGeneralSettingController::class, 'index']);
    Route::post('schsettings/generalsetting', [SchoolGeneralSettingController::class, 'generalsetting']);
    Route::get('schsettings/getSchsetting', [SchoolGeneralSettingController::class, 'getSchsetting']);

    Route::get('schsettings/logo', [SchoolLogoController::class, 'logo']);
    Route::post('schsettings/ajax_editlogo', [SchoolLogoController::class, 'ajaxEditLogo']);
    Route::post('schsettings/ajax_editadmin_adminlogo', [SchoolLogoController::class, 'ajaxEditAdminLogo']);
    Route::post('schsettings/ajax_editadmin_smalllogo', [SchoolLogoController::class, 'ajaxEditAdminSmallLogo']);
    Route::post('schsettings/ajax_applogo', [SchoolLogoController::class, 'ajaxAppLogo']);

    Route::get('schsettings/login_page_background', [SchoolLoginBackgroundController::class, 'index']);
    Route::post('schsettings/add_admin_login_background', [SchoolLoginBackgroundController::class, 'addAdminLoginBackground']);

    Route::get('schsettings/backendtheme', [SchoolBackendThemeController::class, 'index']);
    Route::post('schsettings/savebackendtheme', [SchoolBackendThemeController::class, 'save']);

    Route::get('schsettings/mobileapp', [SchoolMobileAppSettingController::class, 'index']);
    Route::post('schsettings/savemobileapp', [SchoolMobileAppSettingController::class, 'save']);

    Route::get('schsettings/studentguardianpanel', [SchoolStudentGuardianPanelController::class, 'index']);
    Route::post('schsettings/studentguardian', [SchoolStudentGuardianPanelController::class, 'save']);

    Route::get('schsettings/fees', [SchoolFeesSettingController::class, 'index']);
    Route::post('schsettings/savefees', [SchoolFeesSettingController::class, 'save']);

    Route::get('schsettings/idautogeneration', [SchoolIdAutoGenerationController::class, 'index']);
    Route::post('schsettings/saveidautogeneration', [SchoolIdAutoGenerationController::class, 'save']);

    Route::match(['get', 'post'], 'schsettings/attendancetype', [SchoolAttendanceTypeSettingController::class, 'index']);
    Route::post('schsettings/saveattendancetype', [SchoolAttendanceTypeSettingController::class, 'save']);
    Route::post('schsettings/savestaffsetting', [SchoolAttendanceScheduleController::class, 'saveStaff']);
    Route::post('admin/stuattendence/saveclasstime', [SchoolAttendanceScheduleController::class, 'saveClassTime']);
    Route::post('admin/stuattendence/savestudentsetting', [SchoolAttendanceScheduleController::class, 'saveStudent']);

    Route::get('schsettings/maintenance', [SchoolMaintenanceSettingController::class, 'index']);
    Route::post('schsettings/save_maintenance', [SchoolMaintenanceSettingController::class, 'save']);

    Route::get('schsettings/whatsappsettings', [SchoolWhatsappSettingController::class, 'index']);
    Route::post('schsettings/savewhatsappsettings', [SchoolWhatsappSettingController::class, 'save']);

    Route::get('schsettings/chatsetting', [SchoolChatSettingController::class, 'index']);
    Route::post('schsettings/savechatsetting', [SchoolChatSettingController::class, 'save']);

    Route::get('schsettings/googledrivesetting', [SchoolGoogleDriveSettingController::class, 'index']);
    Route::post('schsettings/savegoogledrive', [SchoolGoogleDriveSettingController::class, 'save']);

    Route::get('schsettings/miscellaneous', [SchoolMiscellaneousSettingController::class, 'index']);
    Route::post('schsettings/savemiscellaneous', [SchoolMiscellaneousSettingController::class, 'save']);

    Route::get('admin/module', [SchoolModuleSettingController::class, 'index']);
    Route::post('admin/module/changeStatus', [SchoolModuleSettingController::class, 'changeStatus']);
    Route::post('admin/module/changeStudentStatus', [SchoolModuleSettingController::class, 'changeStudentStatus']);
    Route::post('admin/module/changeParentStatus', [SchoolModuleSettingController::class, 'changeParentStatus']);

    Route::get('admin/currency', [SchoolCurrencySettingController::class, 'index']);
    Route::get('admin/currency/index', [SchoolCurrencySettingController::class, 'index']);
    Route::post('admin/currency/editprice', [SchoolCurrencySettingController::class, 'editprice']);
    Route::post('admin/currency/editsymbol', [SchoolCurrencySettingController::class, 'editsymbol']);
    Route::post('admin/currency/changestatus', [SchoolCurrencySettingController::class, 'changestatus']);
    Route::post('admin/currency/changeactive', [SchoolCurrencySettingController::class, 'changeactive']);
    Route::post('admin/currency/change_currency', [SchoolCurrencySettingController::class, 'changeCurrency']);
    Route::post('admin/currency/getAmountFormat', [SchoolCurrencySettingController::class, 'getAmountFormat']);
});