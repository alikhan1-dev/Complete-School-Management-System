<?php

use App\Modules\Content\Controllers\ContentTypeController;
use App\Modules\Content\Controllers\ModuleStatusController;
use App\Modules\Content\Controllers\ShareContentController;
use App\Modules\Content\Controllers\SiteShareController;
use App\Modules\Content\Controllers\UploadContentController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/content', [ModuleStatusController::class, 'status'])->name('content.migration_status');

Route::get('site/share/{key}', [SiteShareController::class, 'share'])
    ->where('key', '.*')
    ->name('content.site.share');
Route::get('site/download_content/{share_id}/{content_id}', [SiteShareController::class, 'download_content'])
    ->whereNumber('share_id')
    ->where('content_id', '.*')
    ->name('content.site.download');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/contenttype
    Route::match(['get', 'post'], 'admin/contenttype', [ContentTypeController::class, 'index'])->name('content.types.index');
    Route::get('admin/contenttype/index', [ContentTypeController::class, 'index']);
    Route::match(['get', 'post'], 'admin/contenttype/edit/{id}', [ContentTypeController::class, 'edit'])
        ->whereNumber('id')
        ->name('content.types.edit');
    Route::get('admin/contenttype/delete/{id}', [ContentTypeController::class, 'destroy'])
        ->whereNumber('id')
        ->name('content.types.destroy');
    Route::match(['get', 'post'], 'admin/contenttype/getcontenttypelist', [ContentTypeController::class, 'getcontenttypelist'])
        ->name('content.types.datatable');

    // CI admin/content upload persist
    Route::match(['get', 'post'], 'admin/content/upload', [UploadContentController::class, 'upload'])->name('content.upload');
    Route::post('admin/content/ajaxupload', [UploadContentController::class, 'ajaxupload'])->name('content.ajaxupload');
    Route::match(['get', 'post'], 'admin/content/getuploaddata', [UploadContentController::class, 'getuploaddata'])->name('content.getuploaddata');
    Route::post('admin/content/ajaxupdate', [UploadContentController::class, 'ajaxupdate'])->name('content.ajaxupdate');
    Route::post('admin/content/delete', [UploadContentController::class, 'delete'])->name('content.delete');
    Route::post('admin/content/delete_array', [UploadContentController::class, 'delete_array'])->name('content.delete_array');
    Route::get('admin/content/download_content/{id}', [UploadContentController::class, 'download_content'])
        ->whereNumber('id')
        ->name('content.download');

    // CI admin/content share list
    Route::get('admin/content/list', [ShareContentController::class, 'list'])->name('content.share.list');
    Route::post('admin/content/share', [ShareContentController::class, 'share'])->name('content.share');
    Route::post('admin/content/generate_url', [ShareContentController::class, 'generate_url'])->name('content.generate_url');
    Route::match(['get', 'post'], 'admin/content/getsharelist', [ShareContentController::class, 'getsharelist'])->name('content.getsharelist');
    Route::post('admin/content/getsharedcontents', [ShareContentController::class, 'getsharedcontents'])->name('content.getsharedcontents');
    Route::get('admin/content/delete_content/{id}', [ShareContentController::class, 'delete_content'])
        ->whereNumber('id')
        ->name('content.delete_content');
});
