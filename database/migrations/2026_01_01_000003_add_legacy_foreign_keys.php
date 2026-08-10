<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip when schema already imported (parity / local imported databases).
        if (Schema::hasTable('sch_settings')) {
            return;
        }

        DB::unprepared('ALTER TABLE `addon_versions` ADD CONSTRAINT `addon_versions_ibfk_1` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `alumni_events` ADD CONSTRAINT `alumni_events_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `alumni_events_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `alumni_students` ADD CONSTRAINT `alumni_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `annual_calendar` ADD CONSTRAINT `annual_calendar_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)');
        DB::unprepared('ALTER TABLE `book_issues` ADD CONSTRAINT `book_issues_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `book_issues_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `libarary_members` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `chat_connections` ADD CONSTRAINT `chat_connections_ibfk_1` FOREIGN KEY (`chat_user_one`) REFERENCES `chat_users` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `chat_connections_ibfk_2` FOREIGN KEY (`chat_user_two`) REFERENCES `chat_users` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `chat_messages` ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`chat_user_id`) REFERENCES `chat_users` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`chat_connection_id`) REFERENCES `chat_connections` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `chat_users` ADD CONSTRAINT `chat_users_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `chat_users_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_users_ibfk_3` FOREIGN KEY (`create_staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_users_ibfk_4` FOREIGN KEY (`create_student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `class_sections` ADD CONSTRAINT `class_sections_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `class_sections_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `class_section_times` ADD CONSTRAINT `class_section_times_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `class_teacher` ADD CONSTRAINT `class_teacher_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `class_teacher_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_teacher_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_teacher_ibfk_4` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `contents` ADD CONSTRAINT `contents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `contents_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contents_ibfk_3` FOREIGN KEY (`cls_sec_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `content_for` ADD CONSTRAINT `content_for_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `content_for_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `custom_field_values` ADD CONSTRAINT `custom_field_values_ibfk_1` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_fields` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `daily_assignment` ADD CONSTRAINT `daily_assignment_ibfk_1` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `daily_assignment_ibfk_2` FOREIGN KEY (`evaluated_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_assignment_ibfk_3` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `email_attachments` ADD CONSTRAINT `email_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `enquiry` ADD CONSTRAINT `enquiry_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `enquiry_ibfk_3` FOREIGN KEY (`assigned`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enquiry_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `events` ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exams` ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`sesion_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exam_group_class_batch_exams` ADD CONSTRAINT `exam_group_class_batch_exams_ibfk_1` FOREIGN KEY (`exam_group_id`) REFERENCES `exam_groups` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exams_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exam_group_class_batch_exam_students` ADD CONSTRAINT `exam_group_class_batch_exam_students_ibfk_1` FOREIGN KEY (`exam_group_class_batch_exam_id`) REFERENCES `exam_group_class_batch_exams` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exam_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exam_students_ibfk_3` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exam_group_class_batch_exam_subjects` ADD CONSTRAINT `exam_group_class_batch_exam_subjects_ibfk_1` FOREIGN KEY (`exam_group_class_batch_exams_id`) REFERENCES `exam_group_class_batch_exams` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_class_batch_exam_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exam_group_exam_connections` ADD CONSTRAINT `exam_group_exam_connections_ibfk_1` FOREIGN KEY (`exam_group_id`) REFERENCES `exam_groups` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_exam_connections_ibfk_2` FOREIGN KEY (`exam_group_class_batch_exams_id`) REFERENCES `exam_group_class_batch_exams` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exam_group_exam_results` ADD CONSTRAINT `exam_group_exam_results_ibfk_1` FOREIGN KEY (`exam_group_class_batch_exam_subject_id`) REFERENCES `exam_group_class_batch_exam_subjects` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_exam_results_ibfk_2` FOREIGN KEY (`exam_group_student_id`) REFERENCES `exam_group_students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_exam_results_ibfk_3` FOREIGN KEY (`exam_group_class_batch_exam_student_id`) REFERENCES `exam_group_class_batch_exam_students` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exam_group_students` ADD CONSTRAINT `exam_group_students_ibfk_1` FOREIGN KEY (`exam_group_id`) REFERENCES `exam_groups` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_students_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_group_students_ibfk_3` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `exam_schedules` ADD CONSTRAINT `exam_schedules_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `exam_schedules_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `expenses` ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`exp_head_id`) REFERENCES `expense_head` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `feemasters` ADD CONSTRAINT `feemasters_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `feemasters_ibfk_2` FOREIGN KEY (`feetype_id`) REFERENCES `feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feemasters_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `fees_discounts` ADD CONSTRAINT `fees_discounts_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `fee_groups_feetype` ADD CONSTRAINT `fee_groups_feetype_ibfk_1` FOREIGN KEY (`fee_session_group_id`) REFERENCES `fee_session_groups` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `fee_groups_feetype_ibfk_2` FOREIGN KEY (`fee_groups_id`) REFERENCES `fee_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_groups_feetype_ibfk_3` FOREIGN KEY (`feetype_id`) REFERENCES `feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_groups_feetype_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `fee_session_groups` ADD CONSTRAINT `fee_session_groups_ibfk_1` FOREIGN KEY (`fee_groups_id`) REFERENCES `fee_groups` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `fee_session_groups_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `follow_up` ADD CONSTRAINT `follow_up_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiry` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `follow_up_ibfk_2` FOREIGN KEY (`followup_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `front_cms_menu_items` ADD CONSTRAINT `front_cms_menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `front_cms_menus` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `front_cms_page_contents` ADD CONSTRAINT `front_cms_page_contents_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `front_cms_pages` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `front_cms_program_photos` ADD CONSTRAINT `front_cms_program_photos_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `front_cms_programs` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `gateway_ins` ADD CONSTRAINT `gateway_ins_ibfk_1` FOREIGN KEY (`online_admission_id`) REFERENCES `online_admissions` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `gateway_ins_response` ADD CONSTRAINT `gateway_ins_response_ibfk_1` FOREIGN KEY (`gateway_ins_id`) REFERENCES `gateway_ins` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `homework` ADD CONSTRAINT `homework_ibfk_1` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_3` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_5` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_6` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_7` FOREIGN KEY (`evaluated_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_ibfk_8` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `homework_evaluation` ADD CONSTRAINT `homework_evaluation_ibfk_1` FOREIGN KEY (`homework_id`) REFERENCES `homework` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `homework_evaluation_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `homework_evaluation_ibfk_3` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `hostel_rooms` ADD CONSTRAINT `hostel_rooms_ibfk_1` FOREIGN KEY (`hostel_id`) REFERENCES `hostel` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `hostel_rooms_ibfk_2` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `income` ADD CONSTRAINT `income_ibfk_1` FOREIGN KEY (`income_head_id`) REFERENCES `income_head` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `item` ADD CONSTRAINT `item_ibfk_1` FOREIGN KEY (`item_category_id`) REFERENCES `item_category` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `item_ibfk_2` FOREIGN KEY (`item_store_id`) REFERENCES `item_store` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_ibfk_3` FOREIGN KEY (`item_supplier_id`) REFERENCES `item_supplier` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `item_issue` ADD CONSTRAINT `item_issue_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `item_issue_ibfk_2` FOREIGN KEY (`item_category_id`) REFERENCES `item_category` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_issue_ibfk_3` FOREIGN KEY (`issue_to`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_issue_ibfk_4` FOREIGN KEY (`issue_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `item_stock` ADD CONSTRAINT `item_stock_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `item_stock_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `item_supplier` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_stock_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `item_store` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `lesson` ADD CONSTRAINT `lesson_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_ibfk_2` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_ibfk_3` FOREIGN KEY (`subject_group_class_sections_id`) REFERENCES `subject_group_class_sections` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `lesson_plan_forum` ADD CONSTRAINT `lesson_plan_forum_ibfk_1` FOREIGN KEY (`subject_syllabus_id`) REFERENCES `subject_syllabus` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_plan_forum_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lesson_plan_forum_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `notification_roles` ADD CONSTRAINT `notification_roles_ibfk_1` FOREIGN KEY (`send_notification_id`) REFERENCES `send_notification` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `notification_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `offline_fees_payments` ADD CONSTRAINT `offline_fees_payments_ibfk_1` FOREIGN KEY (`student_fees_master_id`) REFERENCES `student_fees_master` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_2` FOREIGN KEY (`fee_groups_feetype_id`) REFERENCES `fee_groups_feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_3` FOREIGN KEY (`student_transport_fee_id`) REFERENCES `student_transport_fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `offline_fees_payments_ibfk_5` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `onlineexam` ADD CONSTRAINT `onlineexam_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `onlineexam_attempts` ADD CONSTRAINT `onlineexam_attempts_ibfk_1` FOREIGN KEY (`onlineexam_student_id`) REFERENCES `onlineexam_students` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `onlineexam_questions` ADD CONSTRAINT `onlineexam_questions_ibfk_1` FOREIGN KEY (`onlineexam_id`) REFERENCES `onlineexam` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_questions_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `onlineexam_students` ADD CONSTRAINT `onlineexam_students_ibfk_1` FOREIGN KEY (`onlineexam_id`) REFERENCES `onlineexam` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_students_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `onlineexam_student_results` ADD CONSTRAINT `onlineexam_student_results_ibfk_1` FOREIGN KEY (`onlineexam_student_id`) REFERENCES `onlineexam_students` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `onlineexam_student_results_ibfk_2` FOREIGN KEY (`onlineexam_question_id`) REFERENCES `onlineexam_questions` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `online_admissions` ADD CONSTRAINT `online_admissions_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `online_admissions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `online_admissions_ibfk_3` FOREIGN KEY (`hostel_room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `online_admissions_ibfk_4` FOREIGN KEY (`school_house_id`) REFERENCES `school_houses` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `online_admission_custom_field_value` ADD CONSTRAINT `online_admission_custom_field_value_ibfk_1` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_fields` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `online_admission_payment` ADD CONSTRAINT `online_admission_payment_ibfk_1` FOREIGN KEY (`online_admission_id`) REFERENCES `online_admissions` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `payslip_allowance` ADD CONSTRAINT `payslip_allowance_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `payslip_allowance_ibfk_2` FOREIGN KEY (`payslip_id`) REFERENCES `staff_payslip` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `permission_category` ADD CONSTRAINT `permission_category_ibfk_1` FOREIGN KEY (`perm_group_id`) REFERENCES `permission_group` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `permission_student` ADD CONSTRAINT `permission_student_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `permission_group` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `questions` ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_4` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_5` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `questions_ibfk_6` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `read_notification` ADD CONSTRAINT `read_notification_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `send_notification` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `read_notification_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `read_notification_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `roles_permissions` ADD CONSTRAINT `roles_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `roles_permissions_ibfk_2` FOREIGN KEY (`perm_cat_id`) REFERENCES `permission_category` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `route_pickup_point` ADD CONSTRAINT `route_pickup_point_ibfk_1` FOREIGN KEY (`transport_route_id`) REFERENCES `transport_route` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `route_pickup_point_ibfk_2` FOREIGN KEY (`pickup_point_id`) REFERENCES `pickup_point` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `send_notification` ADD CONSTRAINT `send_notification_ibfk_1` FOREIGN KEY (`created_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `share_contents` ADD CONSTRAINT `share_contents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `share_content_for` ADD CONSTRAINT `share_content_for_ibfk_1` FOREIGN KEY (`share_content_id`) REFERENCES `share_contents` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `share_content_for_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `share_content_for_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`),
  ADD CONSTRAINT `share_content_for_ibfk_4` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`),
  ADD CONSTRAINT `share_content_for_ibfk_5` FOREIGN KEY (`user_parent_id`) REFERENCES `users` (`id`)');
        DB::unprepared('ALTER TABLE `share_upload_contents` ADD CONSTRAINT `share_upload_contents_ibfk_1` FOREIGN KEY (`upload_content_id`) REFERENCES `upload_contents` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `share_upload_contents_ibfk_2` FOREIGN KEY (`share_content_id`) REFERENCES `share_contents` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `sidebar_menus` ADD CONSTRAINT `sidebar_menus_ibfk_1` FOREIGN KEY (`permission_group_id`) REFERENCES `permission_group` (`id`)ON DELETE SET NULL');
        DB::unprepared('ALTER TABLE `sidebar_sub_menus` ADD CONSTRAINT `sidebar_sub_menus_ibfk_1` FOREIGN KEY (`sidebar_menu_id`) REFERENCES `sidebar_menus` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `sidebar_sub_menus_ibfk_2` FOREIGN KEY (`permission_group_id`) REFERENCES `permission_group` (`id`) ON DELETE SET NULL');
        DB::unprepared('ALTER TABLE `staff` ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`designation`) REFERENCES `staff_designation` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`department`) REFERENCES `department` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `staff_attendance` ADD CONSTRAINT `FK_staff_attendance_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `FK_staff_attendance_staff_attendance_type` FOREIGN KEY (`staff_attendance_type_id`) REFERENCES `staff_attendance_type` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `staff_leave_details` ADD CONSTRAINT `FK_staff_leave_details_leave_types` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `FK_staff_leave_details_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `staff_leave_request` ADD CONSTRAINT `FK_staff_leave_request_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `staff_leave_request_ibfk_1` FOREIGN KEY (`applied_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_leave_request_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `staff_payslip` ADD CONSTRAINT `FK_staff_payslip_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `staff_rating` ADD CONSTRAINT `FK_staff_rating_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `staff_roles` ADD CONSTRAINT `FK_staff_roles_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `FK_staff_roles_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `staff_timeline` ADD CONSTRAINT `FK_staff_timeline_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_applied_discounts` ADD CONSTRAINT `student_applied_discounts_ibfk_1` FOREIGN KEY (`student_fees_deposite_id`) REFERENCES `student_fees_deposite` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_applied_discounts_ibfk_2` FOREIGN KEY (`student_fees_discount_id`) REFERENCES `student_fees_discounts` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_applyleave` ADD CONSTRAINT `student_applyleave_ibfk_1` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_applyleave_ibfk_2` FOREIGN KEY (`approve_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_attendences` ADD CONSTRAINT `student_attendences_ibfk_1` FOREIGN KEY (`attendence_type_id`) REFERENCES `attendence_type` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_attendences_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_fees` ADD CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`feemaster_id`) REFERENCES `feemasters` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_fees_deposite` ADD CONSTRAINT `student_fees_deposite_ibfk_1` FOREIGN KEY (`student_transport_fee_id`) REFERENCES `student_transport_fees` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_deposite_ibfk_2` FOREIGN KEY (`student_fees_master_id`) REFERENCES `student_fees_master` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_deposite_ibfk_3` FOREIGN KEY (`fee_groups_feetype_id`) REFERENCES `fee_groups_feetype` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_fees_discounts` ADD CONSTRAINT `student_fees_discounts_ibfk_1` FOREIGN KEY (`fees_discount_id`) REFERENCES `fees_discounts` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_discounts_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_fees_master` ADD CONSTRAINT `student_fees_master_ibfk_1` FOREIGN KEY (`fee_session_group_id`) REFERENCES `fee_session_groups` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_master_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_fees_processing` ADD CONSTRAINT `student_fees_processing_ibfk_1` FOREIGN KEY (`student_fees_master_id`) REFERENCES `student_fees_master` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_processing_ibfk_2` FOREIGN KEY (`student_transport_fee_id`) REFERENCES `student_transport_fees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_processing_ibfk_3` FOREIGN KEY (`fee_groups_feetype_id`) REFERENCES `fee_groups_feetype` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fees_processing_ibfk_4` FOREIGN KEY (`gateway_ins_id`) REFERENCES `gateway_ins` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_session` ADD CONSTRAINT `student_session_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_4` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_session_ibfk_5` FOREIGN KEY (`vehroute_id`) REFERENCES `vehicle_routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_session_ibfk_6` FOREIGN KEY (`route_pickup_point_id`) REFERENCES `route_pickup_point` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_session_ibfk_7` FOREIGN KEY (`hostel_room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE SET NULL');
        DB::unprepared('ALTER TABLE `student_subject_attendances` ADD CONSTRAINT `student_subject_attendances_ibfk_1` FOREIGN KEY (`attendence_type_id`) REFERENCES `attendence_type` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_subject_attendances_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subject_attendances_ibfk_3` FOREIGN KEY (`subject_timetable_id`) REFERENCES `subject_timetable` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_timeline` ADD CONSTRAINT `student_timeline_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `student_transport_fees` ADD CONSTRAINT `student_transport_fees_ibfk_1` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `student_transport_fees_ibfk_2` FOREIGN KEY (`route_pickup_point_id`) REFERENCES `route_pickup_point` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_transport_fees_ibfk_3` FOREIGN KEY (`transport_feemaster_id`) REFERENCES `transport_feemaster` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `subject_groups` ADD CONSTRAINT `subject_groups_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `subject_group_class_sections` ADD CONSTRAINT `subject_group_class_sections_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_class_sections_ibfk_2` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_class_sections_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `subject_group_subjects` ADD CONSTRAINT `subject_group_subjects_ibfk_1` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_subjects_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_group_subjects_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `subject_syllabus` ADD CONSTRAINT `subject_syllabus_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topic` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `subject_syllabus_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_syllabus_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_syllabus_ibfk_4` FOREIGN KEY (`created_for`) REFERENCES `staff` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `subject_timetable` ADD CONSTRAINT `subject_timetable_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_3` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_4` FOREIGN KEY (`subject_group_subject_id`) REFERENCES `subject_group_subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_5` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subject_timetable_ibfk_6` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `submit_assignment` ADD CONSTRAINT `submit_assignment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `submit_assignment_ibfk_2` FOREIGN KEY (`homework_id`) REFERENCES `homework` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `topic` ADD CONSTRAINT `topic_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `topic_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lesson` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `transport_feemaster` ADD CONSTRAINT `transport_feemaster_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `upload_contents` ADD CONSTRAINT `upload_contents_ibfk_1` FOREIGN KEY (`upload_by`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `upload_contents_ibfk_2` FOREIGN KEY (`content_type_id`) REFERENCES `content_types` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `userlog` ADD CONSTRAINT `userlog_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `vehicle_routes` ADD CONSTRAINT `vehicle_routes_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `transport_route` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_routes_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `video_tutorial` ADD CONSTRAINT `video_tutorial_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`)ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `video_tutorial_class_sections` ADD CONSTRAINT `video_tutorial_class_sections_ibfk_1` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `video_tutorial_class_sections_ibfk_2` FOREIGN KEY (`video_tutorial_id`) REFERENCES `video_tutorial` (`id`) ON DELETE CASCADE');
        DB::unprepared('ALTER TABLE `visitors_book` ADD CONSTRAINT `visitors_book_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`)ON DELETE CASCADE,
  ADD CONSTRAINT `visitors_book_ibfk_2` FOREIGN KEY (`student_session_id`) REFERENCES `student_session` (`id`) ON DELETE CASCADE');

    }

    public function down(): void
    {
        // Irreversible baseline — restore from database/dumps instead.
    }
};
