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

        DB::unprepared('CREATE TABLE `addons` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `image` text NOT NULL,
  `name` varchar(500) DEFAULT NULL,
  `config_name` varchar(200) NOT NULL DEFAULT \'\',
  `short_name` varchar(100) NOT NULL,
  `directory` varchar(500) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `current_version` varchar(50) DEFAULT NULL,
  `article_link` text NOT NULL,
  `installation_by` int DEFAULT NULL,
  `uninstall_version` varchar(50) DEFAULT NULL,
  `unistall_by` int DEFAULT NULL,
  `addon_prod` text,
  `addon_ver` text,
  `last_update` datetime DEFAULT NULL,
  `current_stage` int NOT NULL DEFAULT \'0\' COMMENT \'0 for buy addon,1 for folder available ready to install,2 for folder addon installed\',
  `product_order` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `addon_versions` (
  `id` int NOT NULL,
  `addon_id` int DEFAULT NULL,
  `version` varchar(10) DEFAULT NULL,
  `version_order` int DEFAULT NULL,
  `folder_path` text,
  `sort_description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `alumni_events` (
  `id` int NOT NULL,
  `title` text NOT NULL,
  `event_for` varchar(100) NOT NULL,
  `session_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section` varchar(255) NOT NULL,
  `from_date` datetime NOT NULL,
  `to_date` datetime NOT NULL,
  `note` text NOT NULL,
  `photo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `is_active` int NOT NULL,
  `event_notification_message` text NOT NULL,
  `show_onwebsite` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `alumni_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `current_email` varchar(255) NOT NULL,
  `current_phone` varchar(255) NOT NULL,
  `occupation` text NOT NULL,
  `address` text NOT NULL,
  `photo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `annual_calendar` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `holiday_type` int NOT NULL,
  `from_date` datetime DEFAULT NULL,
  `to_date` datetime DEFAULT NULL,
  `description` text NOT NULL,
  `is_active` int NOT NULL DEFAULT \'1\',
  `holiday_color` varchar(200) NOT NULL,
  `front_site` int NOT NULL DEFAULT \'0\',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `api_session` (
  `id` int NOT NULL,
  `params` text NOT NULL,
  `createdat` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `attendence_type` (
  `id` int NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `key_value` varchar(50) NOT NULL,
  `long_lang_name` varchar(250) DEFAULT NULL,
  `long_name_style` varchar(250) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `for_qr_attendance` int NOT NULL DEFAULT \'1\',
  `for_schedule` int NOT NULL DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `books` (
  `id` int NOT NULL,
  `book_title` varchar(100) NOT NULL,
  `book_no` varchar(50) NOT NULL,
  `isbn_no` varchar(100) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `rack_no` varchar(100) NOT NULL,
  `publish` varchar(100) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `perunitcost` decimal(10,2) DEFAULT NULL,
  `postdate` date DEFAULT NULL,
  `description` text,
  `available` varchar(10) DEFAULT \'yes\',
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `book_issues` (
  `id` int NOT NULL,
  `book_id` int NOT NULL,
  `member_id` int DEFAULT NULL,
  `duereturn_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `is_returned` int DEFAULT \'0\',
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `captcha` (
  `id` int NOT NULL,
  `name` varchar(250) NOT NULL,
  `status` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `categories` (
  `id` int NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `certificates` (
  `id` int NOT NULL,
  `certificate_name` varchar(100) NOT NULL,
  `certificate_text` text NOT NULL,
  `left_header` varchar(100) NOT NULL,
  `center_header` varchar(100) NOT NULL,
  `right_header` varchar(100) NOT NULL,
  `left_footer` varchar(100) NOT NULL,
  `right_footer` varchar(100) NOT NULL,
  `center_footer` varchar(100) NOT NULL,
  `background_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_for` tinyint(1) NOT NULL COMMENT \'1 = staff, 2 = students\',
  `status` tinyint(1) NOT NULL,
  `header_height` int NOT NULL,
  `content_height` int NOT NULL,
  `footer_height` int NOT NULL,
  `content_width` int NOT NULL,
  `enable_student_image` tinyint(1) NOT NULL COMMENT \'0=no,1=yes\',
  `enable_image_height` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `chat_connections` (
  `id` int NOT NULL,
  `chat_user_one` int NOT NULL,
  `chat_user_two` int NOT NULL,
  `ip` varchar(30) DEFAULT NULL,
  `time` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `chat_messages` (
  `id` int NOT NULL,
  `message` text,
  `chat_user_id` int NOT NULL,
  `ip` varchar(30) NOT NULL,
  `time` int NOT NULL,
  `is_first` int DEFAULT \'0\',
  `is_read` int NOT NULL DEFAULT \'0\',
  `chat_connection_id` int NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `chat_users` (
  `id` int NOT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `create_staff_id` int DEFAULT NULL,
  `create_student_id` int DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `classes` (
  `id` int NOT NULL,
  `class` varchar(60) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `class_sections` (
  `id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `class_section_times` (
  `id` int NOT NULL,
  `class_section_id` int DEFAULT NULL,
  `time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `class_teacher` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `class_id` int NOT NULL,
  `section_id` int NOT NULL,
  `staff_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `complaint` (
  `id` int NOT NULL,
  `complaint_type` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(15) NOT NULL,
  `email` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `description` text NOT NULL,
  `action_taken` varchar(200) NOT NULL,
  `assigned` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `complaint_type` (
  `id` int NOT NULL,
  `complaint_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `contents` (
  `id` int NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_public` varchar(10) DEFAULT \'No\',
  `class_id` int DEFAULT NULL,
  `cls_sec_id` int DEFAULT NULL,
  `file` varchar(250) DEFAULT NULL,
  `date` date NOT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `content_for` (
  `id` int NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `content_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `content_types` (
  `id` int NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `description` text,
  `is_active` int DEFAULT \'1\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `cumulative_fine` (
  `id` int NOT NULL,
  `overdue_day` int NOT NULL,
  `fine_amount` decimal(10,2) NOT NULL,
  `fee_groups_feetype_id` int NOT NULL,
  `fee_session_group_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `currencies` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `short_name` varchar(100) DEFAULT NULL,
  `symbol` varchar(10) DEFAULT NULL,
  `base_price` varchar(10) NOT NULL DEFAULT \'1\',
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `custom_fields` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `belong_to` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `bs_column` int DEFAULT NULL,
  `validation` int DEFAULT \'0\',
  `field_values` text,
  `show_table` varchar(100) DEFAULT NULL,
  `visible_on_table` int NOT NULL,
  `weight` int DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `custom_field_values` (
  `id` int NOT NULL,
  `belong_table_id` int DEFAULT NULL,
  `custom_field_id` int DEFAULT NULL,
  `field_value` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `daily_assignment` (
  `id` int NOT NULL,
  `student_session_id` int NOT NULL,
  `subject_group_subject_id` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `attachment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `evaluated_by` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `evaluation_date` date DEFAULT NULL,
  `remark` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `department` (
  `id` int NOT NULL,
  `department_name` varchar(200) NOT NULL,
  `is_active` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `disable_reason` (
  `id` int NOT NULL,
  `reason` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `dispatch_receive` (
  `id` int NOT NULL,
  `reference_no` varchar(50) NOT NULL,
  `to_title` varchar(100) NOT NULL,
  `type` varchar(10) NOT NULL,
  `address` varchar(500) NOT NULL,
  `note` varchar(500) NOT NULL,
  `from_title` varchar(200) NOT NULL,
  `date` date DEFAULT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `email_attachments` (
  `id` int NOT NULL,
  `message_id` int NOT NULL,
  `directory` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `attachment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `attachment_name` varchar(200) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `email_config` (
  `id` int UNSIGNED NOT NULL,
  `email_type` varchar(100) DEFAULT NULL,
  `smtp_server` varchar(100) DEFAULT NULL,
  `smtp_port` varchar(100) DEFAULT NULL,
  `smtp_email` varchar(255) DEFAULT NULL,
  `smtp_username` varchar(100) DEFAULT NULL,
  `smtp_password` varchar(100) DEFAULT NULL,
  `ssl_tls` varchar(100) DEFAULT NULL,
  `smtp_auth` varchar(10) NOT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `api_secret` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `email_template` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `email_template_attachment` (
  `id` int NOT NULL,
  `email_template_id` int NOT NULL,
  `attachment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `attachment_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `enquiry` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `reference` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(500) NOT NULL,
  `follow_up_date` date NOT NULL,
  `note` text NOT NULL,
  `source` varchar(50) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `assigned` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `no_of_child` varchar(11) DEFAULT NULL,
  `status` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `enquiry_type` (
  `id` int NOT NULL,
  `enquiry_type` varchar(100) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `events` (
  `id` int NOT NULL,
  `event_title` varchar(200) NOT NULL,
  `event_description` text NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_color` varchar(200) NOT NULL,
  `event_for` varchar(100) NOT NULL,
  `role_id` int DEFAULT NULL,
  `is_active` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exams` (
  `id` int NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `sesion_id` int NOT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_groups` (
  `id` int NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `exam_type` varchar(250) DEFAULT NULL,
  `description` text,
  `is_active` int DEFAULT \'1\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_group_class_batch_exams` (
  `id` int NOT NULL,
  `exam` varchar(250) DEFAULT NULL,
  `passing_percentage` decimal(10,2) DEFAULT NULL,
  `session_id` int NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `exam_group_id` int DEFAULT NULL,
  `use_exam_roll_no` int NOT NULL DEFAULT \'1\',
  `is_publish` int DEFAULT \'0\',
  `is_rank_generated` int NOT NULL DEFAULT \'0\',
  `description` text,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_group_class_batch_exam_students` (
  `id` int NOT NULL,
  `exam_group_class_batch_exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_session_id` int NOT NULL,
  `roll_no` int DEFAULT NULL,
  `teacher_remark` text,
  `rank` int NOT NULL DEFAULT \'0\',
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_group_class_batch_exam_subjects` (
  `id` int NOT NULL,
  `exam_group_class_batch_exams_id` int DEFAULT NULL,
  `subject_id` int NOT NULL,
  `date_from` date NOT NULL,
  `time_from` time NOT NULL,
  `duration` varchar(50) NOT NULL,
  `room_no` varchar(100) DEFAULT NULL,
  `max_marks` decimal(10,2) DEFAULT NULL,
  `min_marks` decimal(10,2) DEFAULT NULL,
  `credit_hours` decimal(10,2) DEFAULT \'0.00\',
  `date_to` datetime DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_group_exam_connections` (
  `id` int NOT NULL,
  `exam_group_id` int DEFAULT NULL,
  `exam_group_class_batch_exams_id` int DEFAULT NULL,
  `exam_weightage` decimal(10,2) DEFAULT \'0.00\',
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_group_exam_results` (
  `id` int NOT NULL,
  `exam_group_class_batch_exam_student_id` int NOT NULL,
  `exam_group_class_batch_exam_subject_id` int DEFAULT NULL,
  `exam_group_student_id` int DEFAULT NULL,
  `attendence` varchar(10) DEFAULT NULL,
  `get_marks` decimal(10,2) DEFAULT \'0.00\',
  `note` text,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_group_students` (
  `id` int NOT NULL,
  `exam_group_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `exam_schedules` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `exam_id` int DEFAULT NULL,
  `teacher_subject_id` int DEFAULT NULL,
  `date_of_exam` date DEFAULT NULL,
  `start_to` varchar(50) DEFAULT NULL,
  `end_from` varchar(50) DEFAULT NULL,
  `room_no` varchar(50) DEFAULT NULL,
  `full_marks` int DEFAULT NULL,
  `passing_marks` int DEFAULT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `expenses` (
  `id` int NOT NULL,
  `exp_head_id` int DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `invoice_no` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `documents` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `note` text,
  `is_active` varchar(255) DEFAULT \'yes\',
  `is_deleted` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `expense_head` (
  `id` int NOT NULL,
  `exp_category` varchar(50) DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT \'yes\',
  `is_deleted` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `feemasters` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `feetype_id` int NOT NULL,
  `class_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `fees_discounts` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `percentage` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `discount_limit` int DEFAULT NULL,
  `expire_date` date DEFAULT NULL,
  `description` text,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `fees_reminder` (
  `id` int NOT NULL,
  `reminder_type` varchar(10) DEFAULT NULL,
  `day` int DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `feetype` (
  `id` int NOT NULL,
  `is_system` int NOT NULL DEFAULT \'0\',
  `feecategory_id` int DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `description` text,
  `session_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `nature` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `fee_groups` (
  `id` int NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `is_system` int NOT NULL DEFAULT \'0\',
  `description` text,
  `nature` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `fee_groups_feetype` (
  `id` int NOT NULL,
  `fee_session_group_id` int DEFAULT NULL,
  `fee_groups_id` int DEFAULT NULL,
  `feetype_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `fine_type` varchar(50) NOT NULL DEFAULT \'none\',
  `due_date` date DEFAULT NULL,
  `fine_percentage` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `fine_amount` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `fine_per_day` int NOT NULL DEFAULT \'0\',
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `fee_receipt_no` (
  `id` int NOT NULL,
  `payment` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `fee_session_groups` (
  `id` int NOT NULL,
  `fee_groups_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `filetypes` (
  `id` int NOT NULL,
  `file_extension` text,
  `file_mime` text,
  `file_size` int NOT NULL,
  `image_extension` text,
  `image_mime` text,
  `image_size` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `follow_up` (
  `id` int NOT NULL,
  `enquiry_id` int NOT NULL,
  `date` date NOT NULL,
  `next_date` date NOT NULL,
  `response` text NOT NULL,
  `note` text NOT NULL,
  `followup_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_media_gallery` (
  `id` int NOT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `thumb_path` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `dir_path` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `img_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `thumb_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `file_type` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `file_size` varchar(100) NOT NULL,
  `vid_url` text NOT NULL,
  `vid_title` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_menus` (
  `id` int NOT NULL,
  `menu` varchar(100) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `description` text,
  `open_new_tab` int NOT NULL DEFAULT \'0\',
  `ext_url` text NOT NULL,
  `ext_url_link` text NOT NULL,
  `publish` int NOT NULL DEFAULT \'0\',
  `content_type` varchar(10) NOT NULL DEFAULT \'manual\',
  `is_active` varchar(10) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_menu_items` (
  `id` int NOT NULL,
  `menu_id` int NOT NULL,
  `menu` varchar(100) DEFAULT NULL,
  `page_id` int NOT NULL,
  `parent_id` int NOT NULL,
  `ext_url` text,
  `open_new_tab` int DEFAULT \'0\',
  `ext_url_link` text,
  `slug` varchar(200) DEFAULT NULL,
  `weight` int DEFAULT NULL,
  `publish` int NOT NULL DEFAULT \'0\',
  `description` text,
  `is_active` varchar(10) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_pages` (
  `id` int NOT NULL,
  `page_type` varchar(10) NOT NULL DEFAULT \'manual\',
  `is_homepage` int DEFAULT \'0\',
  `title` varchar(250) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `meta_title` text,
  `meta_description` text,
  `meta_keyword` text,
  `feature_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `description` longtext,
  `publish_date` date DEFAULT NULL,
  `publish` int DEFAULT \'0\',
  `sidebar` int DEFAULT \'0\',
  `is_active` varchar(10) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_page_contents` (
  `id` int NOT NULL,
  `page_id` int DEFAULT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_programs` (
  `id` int NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `url` text,
  `title` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `event_start` date DEFAULT NULL,
  `event_end` date DEFAULT NULL,
  `event_venue` text,
  `description` text,
  `is_active` varchar(10) DEFAULT \'no\',
  `meta_title` text NOT NULL,
  `meta_description` text NOT NULL,
  `meta_keyword` text NOT NULL,
  `feature_image` text NOT NULL,
  `publish_date` date DEFAULT NULL,
  `publish` varchar(10) DEFAULT \'0\',
  `sidebar` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_program_photos` (
  `id` int NOT NULL,
  `program_id` int DEFAULT NULL,
  `media_gallery_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `front_cms_settings` (
  `id` int NOT NULL,
  `theme` varchar(50) DEFAULT NULL,
  `is_active_rtl` int DEFAULT \'0\',
  `is_active_front_cms` int DEFAULT \'0\',
  `is_active_sidebar` int DEFAULT \'0\',
  `logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `contact_us_email` varchar(100) DEFAULT NULL,
  `complain_form_email` varchar(100) DEFAULT NULL,
  `sidebar_options` text NOT NULL,
  `whatsapp_url` varchar(255) NOT NULL,
  `fb_url` varchar(200) NOT NULL,
  `twitter_url` varchar(200) NOT NULL,
  `youtube_url` varchar(200) NOT NULL,
  `google_plus` varchar(200) NOT NULL,
  `instagram_url` varchar(200) NOT NULL,
  `pinterest_url` varchar(200) NOT NULL,
  `linkedin_url` varchar(200) NOT NULL,
  `google_analytics` text,
  `footer_text` varchar(500) DEFAULT NULL,
  `cookie_consent` varchar(255) NOT NULL,
  `fav_icon` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `gateway_ins` (
  `id` int NOT NULL,
  `online_admission_id` int DEFAULT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `module_type` varchar(255) NOT NULL,
  `unique_id` varchar(255) NOT NULL,
  `parameter_details` mediumtext NOT NULL,
  `payment_status` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `gateway_ins_response` (
  `id` int NOT NULL,
  `gateway_ins_id` int DEFAULT NULL,
  `posted_data` text,
  `response` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `general_calls` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(12) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(500) NOT NULL,
  `follow_up_date` date NOT NULL,
  `call_duration` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `call_type` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `google_drive_setting` (
  `id` int NOT NULL,
  `client_id` text NOT NULL,
  `api_key` text NOT NULL,
  `project_number` varchar(255) NOT NULL,
  `is_enable` varchar(255) NOT NULL,
  `is_student` varchar(255) NOT NULL,
  `is_parent` varchar(255) NOT NULL,
  `is_staff` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `grades` (
  `id` int NOT NULL,
  `exam_type` varchar(250) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `point` float(10,1) DEFAULT NULL,
  `mark_from` decimal(10,2) DEFAULT NULL,
  `mark_upto` decimal(10,2) DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `holiday_type` (
  `id` int NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `is_default` int NOT NULL DEFAULT \'0\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `homework` (
  `id` int NOT NULL,
  `class_id` int NOT NULL,
  `section_id` int NOT NULL,
  `session_id` int NOT NULL,
  `staff_id` int NOT NULL,
  `subject_group_subject_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `homework_date` date NOT NULL,
  `submit_date` date NOT NULL,
  `marks` decimal(10,2) DEFAULT NULL,
  `description` text,
  `create_date` date NOT NULL,
  `evaluation_date` date DEFAULT NULL,
  `document` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_by` int NOT NULL,
  `evaluated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `homework_evaluation` (
  `id` int NOT NULL,
  `homework_id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_session_id` int DEFAULT NULL,
  `marks` decimal(10,2) DEFAULT NULL,
  `note` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `hostel` (
  `id` int NOT NULL,
  `hostel_name` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `address` text,
  `intake` int DEFAULT NULL,
  `description` text,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `hostel_rooms` (
  `id` int NOT NULL,
  `hostel_id` int DEFAULT NULL,
  `room_type_id` int DEFAULT NULL,
  `room_no` varchar(200) DEFAULT NULL,
  `no_of_bed` int DEFAULT NULL,
  `cost_per_bed` decimal(10,2) DEFAULT \'0.00\',
  `title` varchar(200) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `id_card` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `school_name` varchar(100) NOT NULL,
  `school_address` varchar(500) NOT NULL,
  `background` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `sign_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `enable_vertical_card` int NOT NULL DEFAULT \'0\',
  `header_color` varchar(100) NOT NULL,
  `enable_admission_no` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_student_name` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_class` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_fathers_name` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_mothers_name` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_address` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_phone` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_dob` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_blood_group` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_student_barcode` tinyint NOT NULL DEFAULT \'1\' COMMENT \'0=disable,1=enable\',
  `enable_student_rollno` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable	\',
  `enable_student_house_name` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'0=disable,1=enable	\',
  `status` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `income` (
  `id` int NOT NULL,
  `income_head_id` int DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `invoice_no` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT \'0.00\',
  `note` text,
  `is_active` varchar(255) DEFAULT \'yes\',
  `documents` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `is_deleted` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `income_head` (
  `id` int NOT NULL,
  `income_category` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` varchar(255) NOT NULL DEFAULT \'yes\',
  `is_deleted` varchar(255) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `item` (
  `id` int NOT NULL,
  `item_category_id` int DEFAULT NULL,
  `item_store_id` int DEFAULT NULL,
  `item_supplier_id` int DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `unit` varchar(100) NOT NULL,
  `item_photo` varchar(225) DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` int NOT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `item_category` (
  `id` int NOT NULL,
  `item_category` varchar(255) NOT NULL,
  `is_active` varchar(255) NOT NULL DEFAULT \'yes\',
  `description` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `item_issue` (
  `id` int NOT NULL,
  `issue_type` varchar(15) DEFAULT NULL,
  `issue_to` int NOT NULL,
  `issue_by` int DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `item_category_id` int DEFAULT NULL,
  `item_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `note` text NOT NULL,
  `is_returned` int NOT NULL DEFAULT \'1\',
  `is_active` varchar(10) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `item_stock` (
  `id` int NOT NULL,
  `item_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `store_id` int DEFAULT NULL,
  `symbol` varchar(10) NOT NULL DEFAULT \'+\',
  `quantity` int DEFAULT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `attachment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `description` text NOT NULL,
  `is_active` varchar(10) DEFAULT \'yes\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `item_store` (
  `id` int NOT NULL,
  `item_store` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `item_supplier` (
  `id` int NOT NULL,
  `item_supplier` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `contact_person_name` varchar(255) NOT NULL,
  `contact_person_phone` varchar(255) NOT NULL,
  `contact_person_email` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `languages` (
  `id` int NOT NULL,
  `language` varchar(50) DEFAULT NULL,
  `short_code` varchar(255) NOT NULL,
  `country_code` varchar(255) NOT NULL,
  `is_rtl` int NOT NULL,
  `is_deleted` varchar(10) NOT NULL DEFAULT \'yes\',
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `leave_types` (
  `id` int NOT NULL,
  `type` varchar(200) NOT NULL,
  `is_active` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `lesson` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `subject_group_subject_id` int NOT NULL,
  `subject_group_class_sections_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `lesson_plan_forum` (
  `id` int NOT NULL,
  `subject_syllabus_id` int NOT NULL,
  `type` varchar(20) NOT NULL COMMENT \'staff,student\',
  `staff_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `message` text NOT NULL,
  `created_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `libarary_members` (
  `id` int NOT NULL,
  `library_card_no` varchar(50) DEFAULT NULL,
  `member_type` varchar(50) DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `logs` (
  `id` int NOT NULL,
  `message` text,
  `record_id` text,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `agent` varchar(50) DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `mark_divisions` (
  `id` int NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `percentage_from` decimal(10,2) DEFAULT NULL,
  `percentage_to` decimal(10,2) DEFAULT NULL,
  `is_active` int DEFAULT \'1\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `messages` (
  `id` int NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `template_id` varchar(100) DEFAULT NULL,
  `email_template_id` int DEFAULT NULL,
  `sms_template_id` int DEFAULT NULL,
  `send_through` varchar(20) DEFAULT NULL,
  `message` text,
  `send_mail` varchar(10) DEFAULT \'0\',
  `send_sms` varchar(10) DEFAULT \'0\',
  `is_group` varchar(10) DEFAULT \'0\',
  `is_individual` varchar(10) DEFAULT \'0\',
  `is_class` int NOT NULL DEFAULT \'0\',
  `is_schedule` int NOT NULL,
  `sent` int DEFAULT NULL,
  `schedule_date_time` datetime DEFAULT NULL,
  `group_list` text,
  `user_list` text,
  `send_to` varchar(255) DEFAULT NULL,
  `schedule_class` int DEFAULT NULL,
  `schedule_section` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `migrations` (
  `version` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `notification_roles` (
  `id` int NOT NULL,
  `send_notification_id` int DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `notification_setting` (
  `id` int NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `is_mail` varchar(10) DEFAULT \'0\',
  `is_whatsapp` int DEFAULT \'0\',
  `is_sms` varchar(10) DEFAULT \'0\',
  `is_notification` int NOT NULL DEFAULT \'0\',
  `display_notification` int NOT NULL DEFAULT \'0\',
  `display_sms` int NOT NULL DEFAULT \'1\',
  `display_whatsapp` int NOT NULL DEFAULT \'1\',
  `is_student_recipient` int DEFAULT NULL,
  `is_guardian_recipient` int DEFAULT NULL,
  `is_staff_recipient` int DEFAULT NULL,
  `display_student_recipient` int DEFAULT NULL,
  `display_guardian_recipient` int DEFAULT NULL,
  `display_staff_recipient` int DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `template_id` varchar(100) NOT NULL,
  `whatsapp_template_id` varchar(255) NOT NULL,
  `template` longtext NOT NULL,
  `variables` text NOT NULL,
  `notification_order` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `offline_fees_payments` (
  `id` int NOT NULL,
  `invoice_id` varchar(50) DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `student_fees_master_id` int DEFAULT NULL,
  `fee_groups_feetype_id` int DEFAULT NULL,
  `student_transport_fee_id` int DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `bank_from` varchar(200) DEFAULT NULL,
  `bank_account_transferred` varchar(200) DEFAULT NULL,
  `reference` varchar(200) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `submit_date` datetime DEFAULT NULL,
  `approve_date` datetime DEFAULT NULL,
  `attachment` text,
  `reply` text,
  `approved_by` int DEFAULT NULL,
  `is_active` varchar(1) DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `onlineexam` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `exam` text,
  `attempt` int NOT NULL,
  `exam_from` datetime DEFAULT NULL,
  `exam_to` datetime DEFAULT NULL,
  `is_quiz` int NOT NULL DEFAULT \'0\',
  `auto_publish_date` datetime DEFAULT NULL,
  `time_from` time DEFAULT NULL,
  `time_to` time DEFAULT NULL,
  `duration` time NOT NULL,
  `passing_percentage` float NOT NULL DEFAULT \'0\',
  `description` text,
  `publish_result` int NOT NULL DEFAULT \'0\',
  `answer_word_count` int NOT NULL DEFAULT \'-1\',
  `is_active` varchar(1) DEFAULT \'0\',
  `is_marks_display` int NOT NULL DEFAULT \'0\',
  `is_neg_marking` int NOT NULL DEFAULT \'0\',
  `is_random_question` int NOT NULL DEFAULT \'0\',
  `is_rank_generated` int NOT NULL DEFAULT \'0\',
  `publish_exam_notification` int NOT NULL,
  `publish_result_notification` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `onlineexam_attempts` (
  `id` int NOT NULL,
  `onlineexam_student_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `onlineexam_questions` (
  `id` int NOT NULL,
  `question_id` int DEFAULT NULL,
  `onlineexam_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `marks` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `neg_marks` decimal(10,2) DEFAULT \'0.00\',
  `is_active` varchar(1) DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `onlineexam_students` (
  `id` int NOT NULL,
  `onlineexam_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `is_attempted` int NOT NULL DEFAULT \'0\',
  `rank` int DEFAULT \'0\',
  `quiz_attempted` int NOT NULL DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `onlineexam_student_results` (
  `id` int NOT NULL,
  `onlineexam_student_id` int NOT NULL,
  `onlineexam_question_id` int NOT NULL,
  `select_option` longtext,
  `marks` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `remark` text,
  `attachment_name` text,
  `attachment_upload_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `online_admissions` (
  `id` int NOT NULL,
  `admission_no` varchar(100) DEFAULT NULL,
  `roll_no` varchar(100) DEFAULT NULL,
  `reference_no` varchar(50) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(255) NOT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `rte` varchar(20) NOT NULL DEFAULT \'No\',
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `mobileno` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `cast` varchar(50) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(100) DEFAULT NULL,
  `current_address` text,
  `permanent_address` text,
  `category_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `route_id` int NOT NULL,
  `school_house_id` int DEFAULT NULL,
  `blood_group` varchar(200) NOT NULL,
  `vehroute_id` int NOT NULL,
  `hostel_room_id` int DEFAULT NULL,
  `adhar_no` varchar(100) DEFAULT NULL,
  `samagra_id` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `ifsc_code` varchar(100) DEFAULT NULL,
  `guardian_is` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_phone` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_phone` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(100) DEFAULT NULL,
  `guardian_occupation` varchar(150) NOT NULL,
  `guardian_address` text,
  `guardian_email` varchar(100) NOT NULL,
  `father_pic` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `mother_pic` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `guardian_pic` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `is_enroll` int DEFAULT \'0\',
  `previous_school` text,
  `height` varchar(100) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `note` text NOT NULL,
  `form_status` int NOT NULL,
  `paid_status` int NOT NULL,
  `measurement_date` date DEFAULT NULL,
  `app_key` text,
  `document` text,
  `submit_date` date DEFAULT NULL,
  `disable_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `online_admission_custom_field_value` (
  `id` int NOT NULL,
  `belong_table_id` int DEFAULT NULL,
  `custom_field_id` int DEFAULT NULL,
  `field_value` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `online_admission_fields` (
  `id` int NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `online_admission_payment` (
  `id` int NOT NULL,
  `online_admission_id` int NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `payment_type` varchar(100) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `note` varchar(100) NOT NULL,
  `date` datetime NOT NULL,
  `processing_charge_type` varchar(255) DEFAULT NULL,
  `processing_charge_value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `payment_settings` (
  `id` int NOT NULL,
  `payment_type` varchar(200) NOT NULL,
  `api_username` varchar(200) DEFAULT NULL,
  `api_secret_key` varchar(200) NOT NULL,
  `salt` varchar(200) NOT NULL,
  `api_publishable_key` varchar(200) NOT NULL,
  `api_password` varchar(200) DEFAULT NULL,
  `api_signature` varchar(200) DEFAULT NULL,
  `api_email` varchar(200) DEFAULT NULL,
  `paypal_demo` varchar(100) NOT NULL,
  `account_no` varchar(200) NOT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `gateway_mode` int NOT NULL COMMENT \'0 Testing, 1 live\',
  `paytm_website` varchar(255) NOT NULL,
  `paytm_industrytype` varchar(255) NOT NULL,
  `charge_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `charge_value` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `payslip_allowance` (
  `id` int NOT NULL,
  `payslip_id` int NOT NULL,
  `allowance_type` varchar(200) NOT NULL,
  `amount` float NOT NULL,
  `staff_id` int NOT NULL,
  `cal_type` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `permission_category` (
  `id` int NOT NULL,
  `perm_group_id` int DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `short_code` varchar(100) DEFAULT NULL,
  `enable_view` int DEFAULT \'0\',
  `enable_add` int DEFAULT \'0\',
  `enable_edit` int DEFAULT \'0\',
  `enable_delete` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `permission_group` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `short_code` varchar(100) NOT NULL,
  `is_active` int DEFAULT \'0\',
  `system` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `permission_student` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `short_code` varchar(100) NOT NULL,
  `system` int NOT NULL,
  `student` int NOT NULL,
  `parent` int NOT NULL,
  `group_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `pickup_point` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `latitude` varchar(100) DEFAULT NULL,
  `longitude` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `print_headerfooter` (
  `id` int NOT NULL,
  `print_type` varchar(255) NOT NULL,
  `header_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `footer_content` text NOT NULL,
  `created_by` int NOT NULL,
  `entry_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `questions` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `question_type` varchar(100) NOT NULL,
  `level` varchar(10) NOT NULL,
  `class_id` int NOT NULL,
  `section_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `question` text,
  `opt_a` text,
  `opt_b` text,
  `opt_c` text,
  `opt_d` text,
  `opt_e` text,
  `correct` text,
  `descriptive_word_limit` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `read_notification` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `notification_id` int DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `reference` (
  `id` int NOT NULL,
  `reference` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `resume_additional_fields_settings` (
  `id` int NOT NULL,
  `name` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `resume_settings_fields` (
  `id` int NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `is_system` int NOT NULL DEFAULT \'0\',
  `is_superadmin` int NOT NULL DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `roles_permissions` (
  `id` int NOT NULL,
  `role_id` int DEFAULT NULL,
  `perm_cat_id` int DEFAULT NULL,
  `can_view` int DEFAULT NULL,
  `can_add` int DEFAULT NULL,
  `can_edit` int DEFAULT NULL,
  `can_delete` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `room_types` (
  `id` int NOT NULL,
  `room_type` varchar(200) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `route_pickup_point` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `transport_route_id` int NOT NULL,
  `pickup_point_id` int NOT NULL,
  `fees` decimal(10,2) DEFAULT \'0.00\',
  `destination_distance` float(10,1) DEFAULT \'0.0\',
  `pickup_time` time DEFAULT NULL,
  `order_number` float NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `school_houses` (
  `id` int NOT NULL,
  `house_name` varchar(200) NOT NULL,
  `description` varchar(400) NOT NULL,
  `is_active` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `sch_settings` (
  `id` int NOT NULL,
  `base_url` varchar(500) DEFAULT NULL,
  `folder_path` text,
  `name` varchar(100) DEFAULT NULL,
  `biometric` int DEFAULT \'0\',
  `biometric_device` text,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text,
  `lang_id` int DEFAULT NULL,
  `languages` varchar(500) NOT NULL,
  `dise_code` varchar(50) DEFAULT NULL,
  `date_format` varchar(50) NOT NULL,
  `time_format` varchar(255) NOT NULL,
  `currency` varchar(50) NOT NULL,
  `currency_symbol` varchar(50) NOT NULL,
  `is_rtl` varchar(10) DEFAULT \'disabled\',
  `is_duplicate_fees_invoice` varchar(100) DEFAULT \'0\',
  `collect_back_date_fees` int NOT NULL,
  `single_page_print` int DEFAULT \'0\',
  `timezone` varchar(30) DEFAULT \'UTC\',
  `session_id` int DEFAULT NULL,
  `cron_secret_key` varchar(100) NOT NULL,
  `currency_place` varchar(50) NOT NULL DEFAULT \'before_number\',
  `currency_format` varchar(20) DEFAULT NULL,
  `class_teacher` varchar(100) NOT NULL,
  `start_month` varchar(40) NOT NULL,
  `attendence_type` int NOT NULL DEFAULT \'0\',
  `low_attendance_limit` decimal(10,2) NOT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `admin_logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `admin_small_logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `admin_login_page_background` varchar(255) NOT NULL,
  `user_login_page_background` varchar(255) NOT NULL,
  `theme` varchar(200) NOT NULL DEFAULT \'default.jpg\',
  `fee_due_days` int DEFAULT \'0\',
  `adm_auto_insert` int NOT NULL DEFAULT \'1\',
  `adm_prefix` varchar(50) NOT NULL DEFAULT \'ssadm19/20\',
  `adm_start_from` varchar(11) NOT NULL,
  `adm_no_digit` int NOT NULL DEFAULT \'6\',
  `adm_update_status` int NOT NULL DEFAULT \'0\',
  `staffid_auto_insert` int NOT NULL DEFAULT \'1\',
  `staffid_prefix` varchar(100) NOT NULL DEFAULT \'staffss/19/20\',
  `staffid_start_from` varchar(50) NOT NULL,
  `staffid_no_digit` int NOT NULL DEFAULT \'6\',
  `staffid_update_status` int NOT NULL DEFAULT \'0\',
  `is_active` varchar(255) DEFAULT \'no\',
  `online_admission` int DEFAULT \'0\',
  `online_admission_payment` varchar(50) NOT NULL,
  `online_admission_amount` float NOT NULL,
  `online_admission_instruction` text NOT NULL,
  `online_admission_conditions` text NOT NULL,
  `online_admission_application_form` varchar(255) DEFAULT NULL,
  `exam_result` int NOT NULL,
  `is_blood_group` int NOT NULL DEFAULT \'1\',
  `is_student_house` int NOT NULL DEFAULT \'1\',
  `roll_no` int NOT NULL DEFAULT \'1\',
  `category` int NOT NULL,
  `religion` int NOT NULL DEFAULT \'1\',
  `cast` int NOT NULL DEFAULT \'1\',
  `mobile_no` int NOT NULL DEFAULT \'1\',
  `student_email` int NOT NULL DEFAULT \'1\',
  `admission_date` int NOT NULL DEFAULT \'1\',
  `lastname` int NOT NULL,
  `middlename` int NOT NULL DEFAULT \'1\',
  `student_photo` int NOT NULL DEFAULT \'1\',
  `student_height` int NOT NULL DEFAULT \'1\',
  `student_weight` int NOT NULL DEFAULT \'1\',
  `measurement_date` int NOT NULL DEFAULT \'1\',
  `father_name` int NOT NULL DEFAULT \'1\',
  `father_phone` int NOT NULL DEFAULT \'1\',
  `father_occupation` int NOT NULL DEFAULT \'1\',
  `father_pic` int NOT NULL DEFAULT \'1\',
  `mother_name` int NOT NULL DEFAULT \'1\',
  `mother_phone` int NOT NULL DEFAULT \'1\',
  `mother_occupation` int NOT NULL DEFAULT \'1\',
  `mother_pic` int NOT NULL DEFAULT \'1\',
  `guardian_name` int NOT NULL,
  `guardian_relation` int NOT NULL DEFAULT \'1\',
  `guardian_phone` int NOT NULL,
  `guardian_email` int NOT NULL DEFAULT \'1\',
  `guardian_pic` int NOT NULL DEFAULT \'1\',
  `guardian_occupation` int NOT NULL,
  `guardian_address` int NOT NULL DEFAULT \'1\',
  `current_address` int NOT NULL DEFAULT \'1\',
  `permanent_address` int NOT NULL DEFAULT \'1\',
  `route_list` int NOT NULL DEFAULT \'1\',
  `hostel_id` int NOT NULL DEFAULT \'1\',
  `bank_account_no` int NOT NULL DEFAULT \'1\',
  `ifsc_code` int NOT NULL,
  `bank_name` int NOT NULL,
  `national_identification_no` int NOT NULL DEFAULT \'1\',
  `local_identification_no` int NOT NULL DEFAULT \'1\',
  `rte` int NOT NULL DEFAULT \'1\',
  `previous_school_details` int NOT NULL DEFAULT \'1\',
  `student_note` int NOT NULL DEFAULT \'1\',
  `upload_documents` int NOT NULL DEFAULT \'1\',
  `student_barcode` int NOT NULL DEFAULT \'1\',
  `staff_designation` int NOT NULL DEFAULT \'1\',
  `staff_department` int NOT NULL DEFAULT \'1\',
  `staff_last_name` int NOT NULL DEFAULT \'1\',
  `staff_father_name` int NOT NULL DEFAULT \'1\',
  `staff_mother_name` int NOT NULL DEFAULT \'1\',
  `staff_date_of_joining` int NOT NULL DEFAULT \'1\',
  `staff_phone` int NOT NULL DEFAULT \'1\',
  `staff_emergency_contact` int NOT NULL DEFAULT \'1\',
  `staff_marital_status` int NOT NULL DEFAULT \'1\',
  `staff_photo` int NOT NULL DEFAULT \'1\',
  `staff_current_address` int NOT NULL DEFAULT \'1\',
  `staff_permanent_address` int NOT NULL DEFAULT \'1\',
  `staff_qualification` int NOT NULL DEFAULT \'1\',
  `staff_work_experience` int NOT NULL DEFAULT \'1\',
  `staff_note` int NOT NULL DEFAULT \'1\',
  `staff_epf_no` int NOT NULL DEFAULT \'1\',
  `staff_basic_salary` int NOT NULL DEFAULT \'1\',
  `staff_contract_type` int NOT NULL DEFAULT \'1\',
  `staff_work_shift` int NOT NULL DEFAULT \'1\',
  `staff_work_location` int NOT NULL DEFAULT \'1\',
  `staff_leaves` int NOT NULL DEFAULT \'1\',
  `staff_account_details` int NOT NULL DEFAULT \'1\',
  `staff_social_media` int NOT NULL DEFAULT \'1\',
  `staff_upload_documents` int NOT NULL DEFAULT \'1\',
  `staff_barcode` int NOT NULL DEFAULT \'1\',
  `staff_notification_email` varchar(50) NOT NULL,
  `mobile_api_url` tinytext NOT NULL,
  `app_primary_color_code` varchar(20) DEFAULT NULL,
  `app_secondary_color_code` varchar(20) DEFAULT NULL,
  `admin_mobile_api_url` tinytext NOT NULL,
  `admin_app_primary_color_code` varchar(20) NOT NULL,
  `admin_app_secondary_color_code` varchar(20) NOT NULL,
  `app_logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `student_profile_edit` int NOT NULL DEFAULT \'0\',
  `start_week` varchar(10) NOT NULL,
  `my_question` int NOT NULL,
  `superadmin_restriction` varchar(20) NOT NULL,
  `student_timeline` varchar(20) NOT NULL,
  `calendar_event_reminder` int DEFAULT NULL,
  `event_reminder` varchar(20) NOT NULL,
  `student_login` varchar(100) DEFAULT NULL,
  `parent_login` varchar(100) DEFAULT NULL,
  `student_panel_login` int NOT NULL DEFAULT \'1\',
  `parent_panel_login` int NOT NULL DEFAULT \'1\',
  `is_student_feature_lock` int NOT NULL DEFAULT \'0\',
  `maintenance_mode` int NOT NULL DEFAULT \'0\',
  `lock_grace_period` int NOT NULL DEFAULT \'0\',
  `is_offline_fee_payment` int NOT NULL DEFAULT \'0\',
  `offline_bank_payment_instruction` text NOT NULL,
  `scan_code_type` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT \'barcode\',
  `display_previous_fees` int NOT NULL DEFAULT \'0\',
  `student_resume_download` int NOT NULL DEFAULT \'1\',
  `download_admit_card` int NOT NULL DEFAULT \'0\',
  `fees_discount` int NOT NULL,
  `front_side_whatsapp` int NOT NULL DEFAULT \'0\',
  `front_side_whatsapp_mobile` varchar(50) DEFAULT NULL,
  `front_side_whatsapp_from` time DEFAULT NULL,
  `front_side_whatsapp_to` time DEFAULT NULL,
  `admin_panel_whatsapp` int NOT NULL DEFAULT \'0\',
  `admin_panel_whatsapp_mobile` varchar(50) DEFAULT NULL,
  `admin_panel_whatsapp_from` time DEFAULT NULL,
  `admin_panel_whatsapp_to` time DEFAULT NULL,
  `student_panel_whatsapp` int NOT NULL DEFAULT \'0\',
  `student_panel_whatsapp_mobile` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `student_panel_whatsapp_from` time DEFAULT NULL,
  `student_panel_whatsapp_to` time DEFAULT NULL,
  `saas_key` text,
  `student_delete_chat` int NOT NULL,
  `guardian_delete_chat` int NOT NULL,
  `student_partial_payment` int DEFAULT NULL,
  `staff_delete_chat` int NOT NULL,
  `theme_color` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT \'#7367f0\',
  `theme_shadow` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT \'shadow-applied\',
  `theme_background` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT \'light-mode\',
  `theme_content` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT \'container-fluid\',
  `theme_type` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT \'default\',
  `theme_navigation` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT \'expanded\',
  `theme_font_color` varchar(50) NOT NULL,
  `student_form_multi_class` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `sections` (
  `id` int NOT NULL,
  `section` varchar(60) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `send_notification` (
  `id` int NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `attachment` varchar(500) DEFAULT NULL,
  `message` text,
  `visible_student` varchar(10) NOT NULL DEFAULT \'no\',
  `visible_staff` varchar(10) NOT NULL DEFAULT \'no\',
  `visible_parent` varchar(10) NOT NULL DEFAULT \'no\',
  `created_by` varchar(60) DEFAULT NULL,
  `created_id` int DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `sessions` (
  `id` int NOT NULL,
  `session` varchar(60) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `share_contents` (
  `id` int NOT NULL,
  `send_to` varchar(50) DEFAULT NULL,
  `title` text,
  `share_date` date DEFAULT NULL,
  `valid_upto` date DEFAULT NULL,
  `description` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `share_content_for` (
  `id` int NOT NULL,
  `group_id` varchar(20) DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `user_parent_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `share_content_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `share_upload_contents` (
  `id` int NOT NULL,
  `upload_content_id` int DEFAULT NULL,
  `share_content_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `sidebar_menus` (
  `id` int NOT NULL,
  `product_name` varchar(50) NOT NULL,
  `permission_group_id` int DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `menu` varchar(500) DEFAULT NULL,
  `activate_menu` varchar(100) DEFAULT NULL,
  `lang_key` varchar(250) NOT NULL,
  `system_level` int DEFAULT \'0\',
  `level` int DEFAULT NULL,
  `sidebar_display` int DEFAULT \'0\',
  `access_permissions` text,
  `is_active` int NOT NULL DEFAULT \'1\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `sidebar_sub_menus` (
  `id` int NOT NULL,
  `sidebar_menu_id` int DEFAULT NULL,
  `menu` varchar(500) DEFAULT NULL,
  `key` varchar(500) DEFAULT NULL,
  `lang_key` varchar(250) DEFAULT NULL,
  `url` text,
  `level` int DEFAULT NULL,
  `access_permissions` varchar(500) DEFAULT NULL,
  `permission_group_id` int DEFAULT NULL,
  `activate_controller` varchar(100) DEFAULT NULL COMMENT \'income\',
  `activate_methods` varchar(500) DEFAULT NULL COMMENT \'index,edit\',
  `addon_permission` varchar(100) DEFAULT NULL,
  `is_active` int DEFAULT \'1\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `sms_config` (
  `id` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `api_id` varchar(100) NOT NULL,
  `authkey` varchar(100) NOT NULL,
  `senderid` varchar(100) NOT NULL,
  `contact` text,
  `username` varchar(150) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `password` varchar(150) DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'disabled\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `sms_template` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `source` (
  `id` int NOT NULL,
  `source` varchar(100) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff` (
  `id` int NOT NULL,
  `employee_id` varchar(200) NOT NULL,
  `lang_id` int NOT NULL,
  `currency_id` int DEFAULT \'0\',
  `department` int DEFAULT NULL,
  `designation` int DEFAULT NULL,
  `qualification` varchar(200) NOT NULL,
  `work_exp` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `surname` varchar(200) NOT NULL,
  `father_name` varchar(200) NOT NULL,
  `mother_name` varchar(200) NOT NULL,
  `contact_no` varchar(200) NOT NULL,
  `emergency_contact_no` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `dob` date NOT NULL,
  `marital_status` varchar(100) NOT NULL,
  `date_of_joining` date DEFAULT NULL,
  `date_of_leaving` date DEFAULT NULL,
  `local_address` varchar(300) NOT NULL,
  `permanent_address` varchar(200) NOT NULL,
  `note` varchar(200) NOT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `password` varchar(250) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `account_title` varchar(200) NOT NULL,
  `bank_account_no` varchar(200) NOT NULL,
  `bank_name` varchar(200) NOT NULL,
  `ifsc_code` varchar(200) NOT NULL,
  `bank_branch` varchar(100) NOT NULL,
  `payscale` varchar(200) NOT NULL,
  `basic_salary` int DEFAULT NULL,
  `epf_no` varchar(200) NOT NULL,
  `contract_type` varchar(100) NOT NULL,
  `shift` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `facebook` varchar(200) NOT NULL,
  `twitter` varchar(200) NOT NULL,
  `linkedin` varchar(200) NOT NULL,
  `instagram` varchar(200) NOT NULL,
  `resume` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `joining_letter` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `resignation_letter` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `other_document_name` varchar(200) NOT NULL,
  `other_document_file` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `is_active` int NOT NULL,
  `verification_code` varchar(100) NOT NULL,
  `disable_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_attendance` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `staff_id` int NOT NULL,
  `staff_attendance_type_id` int NOT NULL,
  `biometric_attendence` int DEFAULT \'0\',
  `qrcode_attendance` int NOT NULL DEFAULT \'0\',
  `biometric_device_data` text,
  `user_agent` varchar(250) DEFAULT NULL,
  `remark` varchar(200) NOT NULL,
  `is_active` int NOT NULL,
  `in_time` time DEFAULT NULL,
  `out_time` time DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_attendance_type` (
  `id` int NOT NULL,
  `type` varchar(200) NOT NULL,
  `key_value` varchar(200) NOT NULL,
  `is_active` varchar(50) NOT NULL,
  `for_qr_attendance` int NOT NULL DEFAULT \'1\',
  `long_lang_name` varchar(250) DEFAULT NULL,
  `long_name_style` varchar(250) DEFAULT NULL,
  `for_schedule` int NOT NULL DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_attendence_schedules` (
  `id` int NOT NULL,
  `staff_attendence_type_id` int DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `entry_time_from` time DEFAULT NULL,
  `entry_time_to` time DEFAULT NULL,
  `total_institute_hour` time DEFAULT \'00:00:00\',
  `is_active` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_designation` (
  `id` int NOT NULL,
  `designation` varchar(200) NOT NULL,
  `is_active` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_id_card` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `school_name` varchar(255) NOT NULL,
  `school_address` varchar(255) NOT NULL,
  `background` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `sign_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `header_color` varchar(100) NOT NULL,
  `enable_vertical_card` int NOT NULL DEFAULT \'0\',
  `enable_staff_role` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_staff_id` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_staff_department` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_designation` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_name` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_fathers_name` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_mothers_name` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_date_of_joining` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_permanent_address` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_staff_dob` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_staff_phone` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `enable_staff_barcode` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\',
  `status` tinyint(1) NOT NULL COMMENT \'0=disable,1=enable\'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_leave_details` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `alloted_leave` decimal(10,2) NOT NULL,
  `session_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_leave_request` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type_id` int NOT NULL,
  `leave_from` date NOT NULL,
  `leave_to` date NOT NULL,
  `leave_days` decimal(10,2) NOT NULL,
  `employee_remark` varchar(200) NOT NULL,
  `admin_remark` varchar(200) NOT NULL,
  `approve_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `applied_by` int DEFAULT NULL,
  `document_file` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `session_id` int DEFAULT NULL,
  `date` date NOT NULL,
  `half_day_leave` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_payroll` (
  `id` int NOT NULL,
  `basic_salary` int NOT NULL,
  `pay_scale` varchar(200) NOT NULL,
  `grade` varchar(50) NOT NULL,
  `is_active` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_payslip` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `basic` decimal(10,2) NOT NULL,
  `total_allowance` decimal(10,2) NOT NULL,
  `total_deduction` decimal(10,2) NOT NULL,
  `leave_deduction` int NOT NULL,
  `tax` varchar(200) NOT NULL,
  `net_salary` decimal(10,2) NOT NULL,
  `status` varchar(100) NOT NULL,
  `month` varchar(200) NOT NULL,
  `year` varchar(200) NOT NULL,
  `payment_mode` varchar(200) NOT NULL,
  `payment_date` date NOT NULL,
  `remark` varchar(200) NOT NULL,
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_rating` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `comment` text NOT NULL,
  `rate` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(255) NOT NULL,
  `status` int NOT NULL COMMENT \'0 decline, 1 Approve\',
  `entrydt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_roles` (
  `id` int NOT NULL,
  `role_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `staff_timeline` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `timeline_date` date NOT NULL,
  `description` varchar(300) NOT NULL,
  `document` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `status` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `students` (
  `id` int NOT NULL,
  `parent_id` int NOT NULL,
  `admission_no` varchar(100) DEFAULT NULL,
  `roll_no` varchar(100) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `rte` varchar(20) DEFAULT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `mobileno` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `pincode` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `cast` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(100) DEFAULT NULL,
  `current_address` text,
  `permanent_address` text,
  `category_id` varchar(100) DEFAULT NULL,
  `school_house_id` int DEFAULT NULL,
  `blood_group` varchar(200) NOT NULL,
  `hostel_room_id` int DEFAULT NULL,
  `adhar_no` varchar(100) DEFAULT NULL,
  `samagra_id` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `ifsc_code` varchar(100) DEFAULT NULL,
  `guardian_is` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `father_phone` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `mother_phone` varchar(100) DEFAULT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(100) DEFAULT NULL,
  `guardian_occupation` varchar(150) NOT NULL,
  `guardian_address` text,
  `guardian_email` varchar(100) DEFAULT NULL,
  `father_pic` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `mother_pic` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `guardian_pic` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `is_active` varchar(255) DEFAULT \'yes\',
  `previous_school` text,
  `height` varchar(100) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `measurement_date` date DEFAULT NULL,
  `dis_reason` int NOT NULL,
  `note` varchar(200) DEFAULT NULL,
  `dis_note` text NOT NULL,
  `about` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `designation` varchar(255) DEFAULT NULL,
  `app_key` text,
  `parent_app_key` text,
  `created_by` int DEFAULT NULL,
  `disable_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_applied_discounts` (
  `id` int NOT NULL,
  `student_fees_deposite_id` int DEFAULT NULL,
  `student_fees_discount_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `sub_invoice_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_applyleave` (
  `id` int NOT NULL,
  `student_session_id` int NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `apply_date` date NOT NULL,
  `status` int NOT NULL,
  `docs` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `reason` text NOT NULL,
  `approve_by` int DEFAULT NULL,
  `approve_date` date DEFAULT NULL,
  `request_type` int NOT NULL COMMENT \'0 student,1 staff\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_attendences` (
  `id` int NOT NULL,
  `student_session_id` int DEFAULT NULL,
  `biometric_attendence` int NOT NULL DEFAULT \'0\',
  `qrcode_attendance` int NOT NULL DEFAULT \'0\',
  `date` date DEFAULT NULL,
  `attendence_type_id` int DEFAULT NULL,
  `remark` varchar(200) NOT NULL,
  `biometric_device_data` text,
  `user_agent` varchar(250) DEFAULT NULL,
  `in_time` time DEFAULT NULL,
  `out_time` time DEFAULT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_attendence_schedules` (
  `id` int NOT NULL,
  `attendence_type_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `entry_time_from` time DEFAULT NULL,
  `entry_time_to` time DEFAULT NULL,
  `total_institute_hour` time DEFAULT NULL,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_dashboard_settings` (
  `id` int NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `short_code` varchar(255) NOT NULL,
  `is_student` int DEFAULT NULL,
  `is_parent` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_doc` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `doc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_edit_fields` (
  `id` int NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `status` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_educational_details` (
  `id` int NOT NULL,
  `course` varchar(255) NOT NULL,
  `university` varchar(255) NOT NULL,
  `education_year` varchar(255) NOT NULL,
  `education_detail` varchar(255) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_fees` (
  `id` int NOT NULL,
  `student_session_id` int DEFAULT NULL,
  `feemaster_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `amount_discount` decimal(10,2) NOT NULL,
  `amount_fine` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `description` text,
  `date` date DEFAULT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_fees_deposite` (
  `id` int NOT NULL,
  `student_fees_master_id` int DEFAULT NULL,
  `fee_groups_feetype_id` int DEFAULT NULL,
  `student_transport_fee_id` int DEFAULT NULL,
  `amount_detail` text,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_fees_discounts` (
  `id` int NOT NULL,
  `student_session_id` int DEFAULT NULL,
  `fees_discount_id` int DEFAULT NULL,
  `status` varchar(20) DEFAULT \'assigned\',
  `payment_id` varchar(50) DEFAULT NULL,
  `description` text,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_fees_master` (
  `id` int NOT NULL,
  `is_system` int NOT NULL DEFAULT \'0\',
  `student_session_id` int DEFAULT NULL,
  `fee_session_group_id` int DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT \'0.00\',
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_fees_processing` (
  `id` int NOT NULL,
  `gateway_ins_id` int NOT NULL,
  `fee_category` varchar(255) NOT NULL,
  `student_fees_master_id` int DEFAULT NULL,
  `fee_groups_feetype_id` int DEFAULT NULL,
  `student_transport_fee_id` int DEFAULT NULL,
  `amount_detail` text,
  `is_active` varchar(10) NOT NULL DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_refrence` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `relation` varchar(255) NOT NULL,
  `age` varchar(255) NOT NULL,
  `profession` varchar(255) NOT NULL,
  `contact` varchar(255) NOT NULL,
  `student_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_session` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `hostel_room_id` int DEFAULT NULL,
  `vehroute_id` int DEFAULT NULL,
  `route_pickup_point_id` int DEFAULT NULL,
  `transport_fees` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `fees_discount` decimal(10,2) NOT NULL DEFAULT \'0.00\',
  `is_leave` int NOT NULL DEFAULT \'0\',
  `is_active` varchar(255) DEFAULT \'no\',
  `is_alumni` int NOT NULL,
  `default_login` int NOT NULL DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_skills_detail` (
  `id` int NOT NULL,
  `skill_category` varchar(255) NOT NULL,
  `skill_detail` varchar(255) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_subject_attendances` (
  `id` int NOT NULL,
  `student_session_id` int DEFAULT NULL,
  `subject_timetable_id` int DEFAULT NULL,
  `attendence_type_id` int DEFAULT NULL,
  `date` date DEFAULT NULL,
  `remark` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_timeline` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `timeline_date` date NOT NULL,
  `description` text NOT NULL,
  `document` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `status` varchar(200) NOT NULL,
  `created_student_id` int NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_transport_fees` (
  `id` int NOT NULL,
  `transport_feemaster_id` int NOT NULL,
  `student_session_id` int NOT NULL,
  `route_pickup_point_id` int NOT NULL,
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `student_work_experience` (
  `id` int NOT NULL,
  `institute` text NOT NULL,
  `designation` text NOT NULL,
  `year` varchar(255) NOT NULL,
  `location` text NOT NULL,
  `detail` text NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `subject_groups` (
  `id` int NOT NULL,
  `name` varchar(250) DEFAULT NULL,
  `description` text,
  `session_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `subject_group_class_sections` (
  `id` int NOT NULL,
  `subject_group_id` int DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `description` text,
  `is_active` int DEFAULT \'0\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `subject_group_subjects` (
  `id` int NOT NULL,
  `subject_group_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `subject_syllabus` (
  `id` int NOT NULL,
  `topic_id` int NOT NULL,
  `session_id` int NOT NULL,
  `created_by` int NOT NULL,
  `created_for` int NOT NULL,
  `date` date NOT NULL,
  `time_from` varchar(255) NOT NULL,
  `time_to` varchar(255) NOT NULL,
  `presentation` text NOT NULL,
  `attachment` text NOT NULL,
  `lacture_youtube_url` varchar(255) NOT NULL,
  `lacture_video` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `sub_topic` text NOT NULL,
  `teaching_method` text NOT NULL,
  `general_objectives` text NOT NULL,
  `previous_knowledge` text NOT NULL,
  `comprehensive_questions` text NOT NULL,
  `status` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `subject_timetable` (
  `id` int NOT NULL,
  `session_id` int DEFAULT NULL,
  `class_id` int DEFAULT NULL,
  `section_id` int DEFAULT NULL,
  `subject_group_id` int DEFAULT NULL,
  `subject_group_subject_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL,
  `time_from` varchar(20) DEFAULT NULL,
  `time_to` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room_no` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `submit_assignment` (
  `id` int NOT NULL,
  `homework_id` int NOT NULL,
  `student_id` int NOT NULL,
  `message` text NOT NULL,
  `docs` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `file_name` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `template_admitcards` (
  `id` int NOT NULL,
  `template` varchar(250) DEFAULT NULL,
  `heading` text,
  `title` text,
  `left_logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `right_logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `exam_name` varchar(200) DEFAULT NULL,
  `school_name` varchar(200) DEFAULT NULL,
  `exam_center` varchar(200) DEFAULT NULL,
  `sign` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `background_img` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `is_name` int NOT NULL DEFAULT \'1\',
  `is_father_name` int NOT NULL DEFAULT \'1\',
  `is_mother_name` int NOT NULL DEFAULT \'1\',
  `is_dob` int NOT NULL DEFAULT \'1\',
  `is_admission_no` int NOT NULL DEFAULT \'1\',
  `is_roll_no` int NOT NULL DEFAULT \'1\',
  `is_address` int NOT NULL DEFAULT \'1\',
  `is_gender` int NOT NULL DEFAULT \'1\',
  `is_photo` int NOT NULL,
  `is_class` int NOT NULL DEFAULT \'0\',
  `is_section` int NOT NULL DEFAULT \'0\',
  `is_active` int DEFAULT \'0\',
  `content_footer` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `template_marksheets` (
  `id` int NOT NULL,
  `header_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `template` varchar(200) DEFAULT NULL,
  `heading` text,
  `title` text,
  `left_logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `right_logo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `exam_name` varchar(200) DEFAULT NULL,
  `school_name` varchar(200) DEFAULT NULL,
  `exam_center` varchar(200) DEFAULT NULL,
  `left_sign` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `middle_sign` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `right_sign` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `exam_session` int DEFAULT \'1\',
  `is_name` int DEFAULT \'1\',
  `is_father_name` int DEFAULT \'1\',
  `is_mother_name` int DEFAULT \'1\',
  `is_dob` int DEFAULT \'1\',
  `is_admission_no` int DEFAULT \'1\',
  `is_roll_no` int DEFAULT \'1\',
  `is_photo` int DEFAULT \'1\',
  `is_division` int NOT NULL DEFAULT \'1\',
  `is_rank` int NOT NULL DEFAULT \'0\',
  `is_customfield` int NOT NULL,
  `background_img` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `date` varchar(20) DEFAULT NULL,
  `is_class` int NOT NULL DEFAULT \'0\',
  `is_teacher_remark` int NOT NULL DEFAULT \'1\',
  `is_section` int NOT NULL DEFAULT \'0\',
  `content` text,
  `content_footer` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `topic` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `lesson_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` int NOT NULL,
  `complete_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `transfer_certificate_fields` (
  `id` int NOT NULL,
  `name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci COMMENT \'sch_settings columns\',
  `lang_key` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `status` int DEFAULT NULL,
  `position` int NOT NULL,
  `is_default` int NOT NULL DEFAULT \'0\',
  `is_active` int NOT NULL DEFAULT \'1\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `transfer_certificate_no` (
  `id` int NOT NULL,
  `student_session_id` int NOT NULL,
  `tc_no` int NOT NULL,
  `is_regenerte` int NOT NULL,
  `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `transfer_certificate_settings` (
  `id` int NOT NULL,
  `tc_no_start` int NOT NULL,
  `header_image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `footer_content` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `class_teacher_signature` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `checked_by` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `signature_of_principle` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `create_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `transport_feemaster` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `month` varchar(50) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT \'0.00\',
  `fine_type` varchar(50) DEFAULT NULL,
  `fine_percentage` decimal(10,2) DEFAULT \'0.00\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `transport_route` (
  `id` int NOT NULL,
  `route_title` varchar(100) DEFAULT NULL,
  `no_of_vehicle` int DEFAULT NULL,
  `note` text,
  `is_active` varchar(255) DEFAULT \'no\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `upload_contents` (
  `id` int NOT NULL,
  `content_type_id` int NOT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `thumb_path` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `dir_path` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `real_name` text NOT NULL,
  `img_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `thumb_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `file_type` varchar(100) NOT NULL,
  `mime_type` text NOT NULL,
  `file_size` varchar(100) NOT NULL,
  `vid_url` text NOT NULL,
  `vid_title` varchar(250) NOT NULL,
  `upload_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `userlog` (
  `id` int NOT NULL,
  `user` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `class_section_id` int DEFAULT NULL,
  `ipaddress` varchar(100) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `users` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `childs` text NOT NULL,
  `role` varchar(30) NOT NULL,
  `lang_id` int NOT NULL,
  `currency_id` int DEFAULT \'0\',
  `verification_code` varchar(200) NOT NULL,
  `login_token` varchar(255) NOT NULL,
  `is_active` varchar(255) DEFAULT \'yes\',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `users_authentication` (
  `id` int NOT NULL,
  `users_id` int NOT NULL,
  `token` varchar(255) NOT NULL,
  `staff_id` int DEFAULT NULL,
  `expired_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `vehicles` (
  `id` int NOT NULL,
  `vehicle_no` varchar(20) DEFAULT NULL,
  `vehicle_model` varchar(100) NOT NULL DEFAULT \'None\',
  `vehicle_photo` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `manufacture_year` varchar(4) DEFAULT NULL,
  `registration_number` varchar(50) NOT NULL,
  `chasis_number` varchar(100) NOT NULL,
  `max_seating_capacity` varchar(255) NOT NULL,
  `driver_name` varchar(50) DEFAULT NULL,
  `driver_licence` varchar(50) NOT NULL DEFAULT \'None\',
  `driver_contact` varchar(20) DEFAULT NULL,
  `note` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `vehicle_routes` (
  `id` int NOT NULL,
  `route_id` int DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `video_tutorial` (
  `id` int NOT NULL,
  `title` varchar(100) NOT NULL,
  `vid_title` text,
  `description` text NOT NULL,
  `thumb_path` varchar(500) DEFAULT NULL,
  `dir_path` varchar(500) DEFAULT NULL,
  `img_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `thumb_name` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `video_link` varchar(100) NOT NULL,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `video_tutorial_class_sections` (
  `id` int NOT NULL,
  `video_tutorial_id` int NOT NULL,
  `class_section_id` int NOT NULL,
  `created_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `visitors_book` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `student_session_id` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact` varchar(12) NOT NULL,
  `id_proof` varchar(50) NOT NULL,
  `no_of_people` int NOT NULL,
  `date` date NOT NULL,
  `in_time` varchar(20) NOT NULL,
  `out_time` varchar(20) NOT NULL,
  `note` text NOT NULL,
  `image` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `meeting_with` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
        DB::unprepared('CREATE TABLE `visitors_purpose` (
  `id` int NOT NULL,
  `visitors_purpose` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

    }

    public function down(): void
    {
        // Irreversible baseline — restore from database/dumps instead.
    }
};
