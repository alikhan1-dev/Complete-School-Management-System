<?php

use App\Modules\LessonPlan\Controllers\CopyLessonController;
use App\Modules\LessonPlan\Controllers\LessonController;
use App\Modules\LessonPlan\Controllers\ModuleStatusController;
use App\Modules\LessonPlan\Controllers\SyllabusManageController;
use App\Modules\LessonPlan\Controllers\SyllabusStatusController;
use App\Modules\LessonPlan\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/lessonplan', [ModuleStatusController::class, 'status'])->name('lessonplan.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // Manage syllabus status (CI admin/lessonplan + admin/syllabus/status)
    Route::match(['get', 'post'], 'admin/lessonplan', [SyllabusStatusController::class, 'index'])
        ->name('lessonplan.status.index');
    Route::match(['get', 'post'], 'admin/lessonplan/index', [SyllabusStatusController::class, 'index']);
    Route::match(['get', 'post'], 'admin/syllabus/status', [SyllabusStatusController::class, 'index'])
        ->name('lessonplan.syllabus.status');

    // Weekly manage lesson plan (CI admin/syllabus)
    Route::get('admin/syllabus', [SyllabusManageController::class, 'index'])->name('lessonplan.syllabus.manage');
    Route::get('admin/syllabus/index', [SyllabusManageController::class, 'index']);
    Route::get('admin/syllabus/create', [SyllabusManageController::class, 'create'])->name('lessonplan.syllabus.create');
    Route::post('admin/syllabus/add_syllabus', [SyllabusManageController::class, 'store'])->name('lessonplan.syllabus.store');
    Route::get('admin/syllabus/show/{id}', [SyllabusManageController::class, 'show'])->whereNumber('id')->name('lessonplan.syllabus.show');
    Route::get('admin/syllabus/edit/{id}', [SyllabusManageController::class, 'edit'])->whereNumber('id')->name('lessonplan.syllabus.edit');
    Route::post('admin/syllabus/edit/{id}', [SyllabusManageController::class, 'update'])->whereNumber('id')->name('lessonplan.syllabus.update');
    Route::get('admin/syllabus/delete/{id}', [SyllabusManageController::class, 'destroy'])->whereNumber('id')->name('lessonplan.syllabus.destroy');
    Route::get('admin/syllabus/download/{id}', [SyllabusManageController::class, 'download'])->whereNumber('id')->name('lessonplan.syllabus.download');
    Route::get('admin/syllabus/lacture_video_download/{id}', [SyllabusManageController::class, 'downloadLectureVideo'])
        ->whereNumber('id')
        ->name('lessonplan.syllabus.video');
    Route::post('admin/syllabus/addmessage', [SyllabusManageController::class, 'addMessage'])
        ->name('lessonplan.syllabus.forum.store');
    Route::get('admin/syllabus/deletemessage/{id}', [SyllabusManageController::class, 'deleteMessage'])
        ->whereNumber('id')
        ->name('lessonplan.syllabus.forum.destroy');
    Route::post('admin/syllabus/deletemessage', function (\Illuminate\Http\Request $request) {
        $id = (int) $request->input('fourm_id', $request->input('id'));
        abort_if($id <= 0, 404);

        return app(SyllabusManageController::class)->deleteMessage($id);
    });
    // CI AJAX aliases kept for form cascade
    Route::match(['get', 'post'], 'admin/lessonplan/gettopicBylessonid/{lessonId}', [SyllabusManageController::class, 'topicsByLesson'])
        ->whereNumber('lessonId')
        ->name('lessonplan.topics.by_lesson');

    // Copy old lesson (CI admin/lessonplan/copylesson + saveCopyLesson)
    Route::match(['get', 'post'], 'admin/lessonplan/copylesson', [CopyLessonController::class, 'index'])
        ->name('lessonplan.copy.index');
    Route::post('admin/lessonplan/saveCopyLesson', [CopyLessonController::class, 'store'])
        ->name('lessonplan.copy.store');

    // Lesson CRUD (CI admin/lessonplan/lesson)
    Route::get('admin/lessonplan/lesson', [LessonController::class, 'index'])->name('lessonplan.lessons.index');
    Route::post('admin/lessonplan/createlesson', [LessonController::class, 'store'])->name('lessonplan.lessons.store');
    Route::get('admin/lessonplan/editlesson/{sgcsId}/{sgsId}', [LessonController::class, 'edit'])
        ->whereNumber(['sgcsId', 'sgsId'])
        ->name('lessonplan.lessons.edit');
    Route::post('admin/lessonplan/updatelesson', [LessonController::class, 'update'])->name('lessonplan.lessons.update');
    Route::get('admin/lessonplan/deletelessonbulk/{sgcsId}/{sgsId}', [LessonController::class, 'destroyBulk'])
        ->whereNumber(['sgcsId', 'sgsId'])
        ->name('lessonplan.lessons.destroy_bulk');
    Route::match(['get', 'post'], 'admin/lessonplan/getlessonBysubjectid/{subjectGroupSubjectId}', [LessonController::class, 'lessonsBySubject'])
        ->whereNumber('subjectGroupSubjectId')
        ->name('lessonplan.lessons.by_subject');
    Route::match(['get', 'post'], 'admin/lessonplan/getlessonBysubjectidedit/{subjectGroupSubjectId}', [LessonController::class, 'lessonsBySubjectEdit'])
        ->whereNumber('subjectGroupSubjectId')
        ->name('lessonplan.lessons.by_subject_edit');

    // Topic CRUD (CI admin/lessonplan/topic)
    Route::get('admin/lessonplan/topic', [TopicController::class, 'index'])->name('lessonplan.topics.index');
    Route::post('admin/lessonplan/createtopic', [TopicController::class, 'store'])->name('lessonplan.topics.store');
    Route::get('admin/lessonplan/edittopic/{lessonId}', [TopicController::class, 'edit'])
        ->whereNumber('lessonId')
        ->name('lessonplan.topics.edit');
    Route::post('admin/lessonplan/updatetopic/{lessonId}', [TopicController::class, 'update'])
        ->whereNumber('lessonId')
        ->name('lessonplan.topics.update');
    Route::post('admin/lessonplan/updatetopic', function (\Illuminate\Http\Request $request) {
        $lessonId = (int) $request->input('lesson_id');
        abort_if($lessonId <= 0, 404);

        return app(TopicController::class)->update($request, $lessonId);
    });
    Route::get('admin/lessonplan/deletetopicbulk/{lessonId}', [TopicController::class, 'destroyBulk'])
        ->whereNumber('lessonId')
        ->name('lessonplan.topics.destroy_bulk');
    Route::post('admin/lessonplan/topic/complete/{id}', [TopicController::class, 'complete'])
        ->whereNumber('id')
        ->name('lessonplan.topics.complete');
    Route::post('admin/lessonplan/topic/incomplete/{id}', [TopicController::class, 'incomplete'])
        ->whereNumber('id')
        ->name('lessonplan.topics.incomplete');
});
