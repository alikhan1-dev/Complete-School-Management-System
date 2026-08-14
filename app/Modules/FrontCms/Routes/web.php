<?php

use App\Modules\FrontCms\Controllers\FrontCmsBannerController;
use App\Modules\FrontCms\Controllers\FrontCmsEventController;
use App\Modules\FrontCms\Controllers\FrontCmsGalleryController;
use App\Modules\FrontCms\Controllers\FrontCmsMediaController;
use App\Modules\FrontCms\Controllers\FrontCmsMenuController;
use App\Modules\FrontCms\Controllers\FrontCmsNoticeController;
use App\Modules\FrontCms\Controllers\FrontCmsPageController;
use App\Modules\FrontCms\Controllers\FrontCmsSettingController;
use App\Modules\FrontCms\Controllers\FrontCmsWelcomeController;
use App\Modules\FrontCms\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/frontcms', [ModuleStatusController::class, 'status'])->name('frontcms.migration_status');

Route::get('frontend', [FrontCmsWelcomeController::class, 'index']);
Route::match(['get', 'post'], 'page/{slug}', [FrontCmsWelcomeController::class, 'page'])->where('slug', '.*');
Route::get('read/{slug}', [FrontCmsWelcomeController::class, 'read'])->where('slug', '.*');
Route::post('welcome/ajaxPaginationData', [FrontCmsWelcomeController::class, 'ajaxPaginationData']);
Route::post('welcome/setsitecookies', [FrontCmsWelcomeController::class, 'setSiteCookies']);
Route::match(['get', 'post'], 'welcome/examresult', [FrontCmsWelcomeController::class, 'examresult']);
Route::match(['get', 'post'], 'welcome/getstudentexam', [FrontCmsWelcomeController::class, 'getstudentexam']);

Route::middleware(['staff.auth'])->group(function () {
    Route::match(['get', 'post'], 'admin/frontcms', [FrontCmsSettingController::class, 'index'])->name('frontcms.settings.index');
    Route::get('admin/frontcms/index', [FrontCmsSettingController::class, 'index']);

    Route::get('admin/front/page', [FrontCmsPageController::class, 'index'])->name('frontcms.pages.index');
    Route::get('admin/front/page/index', [FrontCmsPageController::class, 'index']);
    Route::match(['get', 'post'], 'admin/front/page/create', [FrontCmsPageController::class, 'create'])->name('frontcms.pages.create');
    Route::match(['get', 'post'], 'admin/front/page/edit/{slug}', [FrontCmsPageController::class, 'edit'])->name('frontcms.pages.edit');
    Route::get('admin/front/page/delete/{slug}', [FrontCmsPageController::class, 'delete'])->name('frontcms.pages.delete');

    Route::get('admin/front/banner', [FrontCmsBannerController::class, 'index'])->name('frontcms.banners.index');
    Route::get('admin/front/banner/index', [FrontCmsBannerController::class, 'index']);
    Route::post('admin/front/banner/add', [FrontCmsBannerController::class, 'add'])->name('frontcms.banners.add');
    Route::post('admin/front/banner/remove', [FrontCmsBannerController::class, 'remove'])->name('frontcms.banners.remove');

    Route::get('admin/front/gallery', [FrontCmsGalleryController::class, 'index'])->name('frontcms.gallery.index');
    Route::get('admin/front/gallery/index', [FrontCmsGalleryController::class, 'index']);
    Route::match(['get', 'post'], 'admin/front/gallery/create', [FrontCmsGalleryController::class, 'create'])->name('frontcms.gallery.create');
    Route::match(['get', 'post'], 'admin/front/gallery/edit/{slug}', [FrontCmsGalleryController::class, 'edit'])->name('frontcms.gallery.edit');
    Route::get('admin/front/gallery/delete/{slug}', [FrontCmsGalleryController::class, 'delete'])->name('frontcms.gallery.delete');

    Route::match(['get', 'post'], 'admin/front/menus', [FrontCmsMenuController::class, 'index'])->name('frontcms.menus.index');
    Route::get('admin/front/menus/index', [FrontCmsMenuController::class, 'index']);
    Route::match(['get', 'post'], 'admin/front/menus/additem/{slug}', [FrontCmsMenuController::class, 'additem'])->name('frontcms.menus.additem');
    Route::match(['get', 'post'], 'admin/front/menus/edititem/{slug}/{top_menu}', [FrontCmsMenuController::class, 'edititem'])->name('frontcms.menus.edititem');
    Route::post('admin/front/menus/updateMenu', [FrontCmsMenuController::class, 'updateMenu'])->name('frontcms.menus.updateMenu');
    Route::post('admin/front/menus/deleteMenuItem', [FrontCmsMenuController::class, 'deleteMenuItem'])->name('frontcms.menus.deleteMenuItem');
    Route::get('admin/front/menus/delete/{slug}', [FrontCmsMenuController::class, 'delete'])->name('frontcms.menus.delete');

    Route::get('admin/front/notice', [FrontCmsNoticeController::class, 'index'])->name('frontcms.notices.index');
    Route::get('admin/front/notice/index', [FrontCmsNoticeController::class, 'index']);
    Route::match(['get', 'post'], 'admin/front/notice/create', [FrontCmsNoticeController::class, 'create'])->name('frontcms.notices.create');
    Route::match(['get', 'post'], 'admin/front/notice/edit/{slug}', [FrontCmsNoticeController::class, 'edit'])->name('frontcms.notices.edit');
    Route::get('admin/front/notice/delete/{slug}', [FrontCmsNoticeController::class, 'delete'])->name('frontcms.notices.delete');

    Route::get('admin/front/events', [FrontCmsEventController::class, 'index'])->name('frontcms.events.index');
    Route::get('admin/front/events/index', [FrontCmsEventController::class, 'index']);
    Route::match(['get', 'post'], 'admin/front/events/create', [FrontCmsEventController::class, 'create'])->name('frontcms.events.create');
    Route::match(['get', 'post'], 'admin/front/events/edit/{slug}', [FrontCmsEventController::class, 'edit'])->name('frontcms.events.edit');
    Route::get('admin/front/events/delete/{slug}', [FrontCmsEventController::class, 'delete'])->name('frontcms.events.delete');

    Route::get('admin/front/media', [FrontCmsMediaController::class, 'index'])->name('frontcms.media.index');
    Route::get('admin/front/media/index', [FrontCmsMediaController::class, 'index']);
    Route::get('admin/front/media/getMedia', [FrontCmsMediaController::class, 'getMedia'])->name('frontcms.media.getMedia');
    Route::get('admin/front/media/getPage/{page?}', [FrontCmsMediaController::class, 'getPage'])->name('frontcms.media.getPage');
    Route::post('admin/front/media/addImage', [FrontCmsMediaController::class, 'addImage'])->name('frontcms.media.addImage');
    Route::post('admin/front/media/addVideo', [FrontCmsMediaController::class, 'addVideo'])->name('frontcms.media.addVideo');
    Route::post('admin/front/media/deleteItem', [FrontCmsMediaController::class, 'deleteItem'])->name('frontcms.media.deleteItem');
});
