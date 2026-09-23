-- ============================================================
-- EPS Korean Trainer - Complete Consolidated Database Export
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB 10.2+
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `eps_topik` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `eps_topik`;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('learner','admin') DEFAULT 'learner',
    `status` ENUM('active','inactive','banned') DEFAULT 'active',
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `email_verified_at` DATETIME DEFAULT NULL,
    `remember_token` VARCHAR(100) DEFAULT NULL,
    `reset_token` VARCHAR(100) DEFAULT NULL,
    `reset_token_expires` DATETIME DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `user_profiles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE `user_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `daily_target` INT DEFAULT 20,
    `learning_level` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    `preferred_study_mode` ENUM('flashcard','list','quiz') DEFAULT 'flashcard',
    `sound_enabled` TINYINT(1) DEFAULT 1,
    `dark_mode` TINYINT(1) DEFAULT 0,
    `notification_enabled` TINYINT(1) DEFAULT 1,
    `timezone` VARCHAR(50) DEFAULT 'Asia/Manila',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `user_streaks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_streaks`;
CREATE TABLE `user_streaks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `current_streak` INT DEFAULT 0,
    `longest_streak` INT DEFAULT 0,
    `last_activity_date` DATE DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `module` ENUM('lesson','vocabulary','listening','reading','quiz','mock_exam') NOT NULL,
    `icon` VARCHAR(50) DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#3B82F6',
    `sort_order` INT DEFAULT 0,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_module` (`module`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `lessons`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `lessons`;
CREATE TABLE `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `difficulty` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    `estimated_minutes` INT DEFAULT 15,
    `content` LONGTEXT NOT NULL,
    `summary` TEXT DEFAULT NULL,
    `tips` TEXT DEFAULT NULL,
    `audio_path` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `status` ENUM('published','draft') DEFAULT 'published',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_difficulty` (`difficulty`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `lesson_completions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `lesson_completions`;
CREATE TABLE `lesson_completions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `completed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_lesson` (`user_id`, `lesson_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `vocabulary`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vocabulary`;
CREATE TABLE `vocabulary` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `korean` VARCHAR(200) NOT NULL,
    `english` VARCHAR(200) NOT NULL,
    `pronunciation` VARCHAR(200) DEFAULT NULL,
    `part_of_speech` ENUM('noun','verb','adjective','adverb','phrase','expression') DEFAULT 'noun',
    `example_sentence_korean` TEXT DEFAULT NULL,
    `example_sentence_english` TEXT DEFAULT NULL,
    `audio_path` VARCHAR(255) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `difficulty` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_difficulty` (`difficulty`),
    FULLTEXT `idx_search` (`korean`, `english`, `pronunciation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `vocabulary_mastery`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vocabulary_mastery`;
CREATE TABLE `vocabulary_mastery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `vocabulary_id` INT NOT NULL,
    `mastery_level` INT DEFAULT 0,
    `review_count` INT DEFAULT 0,
    `correct_count` INT DEFAULT 0,
    `incorrect_count` INT DEFAULT 0,
    `last_reviewed_at` DATETIME DEFAULT NULL,
    `next_review_at` DATETIME DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabulary`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_vocab` (`user_id`, `vocabulary_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `listening_questions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `listening_questions`;
CREATE TABLE `listening_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `audio_path` VARCHAR(255) DEFAULT NULL,
    `dialogue_text` TEXT DEFAULT NULL,
    `question_text` TEXT NOT NULL,
    `choice_a` VARCHAR(300) NOT NULL,
    `choice_b` VARCHAR(300) NOT NULL,
    `choice_c` VARCHAR(300) NOT NULL,
    `choice_d` VARCHAR(300) NOT NULL,
    `correct_answer` ENUM('A','B','C','D') NOT NULL,
    `explanation` TEXT DEFAULT NULL,
    `difficulty` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_difficulty` (`difficulty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `reading_passages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reading_passages`;
CREATE TABLE `reading_passages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `passage_text` LONGTEXT NOT NULL,
    `content_type` ENUM('notice','sign','instruction','dialogue','passage','schedule','workplace') DEFAULT 'passage',
    `difficulty` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_difficulty` (`difficulty`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `reading_questions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reading_questions`;
CREATE TABLE `reading_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `passage_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `choice_a` VARCHAR(300) NOT NULL,
    `choice_b` VARCHAR(300) NOT NULL,
    `choice_c` VARCHAR(300) NOT NULL,
    `choice_d` VARCHAR(300) NOT NULL,
    `correct_answer` ENUM('A','B','C','D') NOT NULL,
    `explanation` TEXT DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`passage_id`) REFERENCES `reading_passages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `quizzes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `quizzes`;
CREATE TABLE `quizzes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category_id` INT DEFAULT NULL,
    `quiz_type` ENUM('vocabulary','listening','reading','mixed') DEFAULT 'mixed',
    `difficulty` ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    `time_limit_minutes` INT DEFAULT NULL,
    `question_count` INT DEFAULT 10,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `quiz_questions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `quiz_questions`;
CREATE TABLE `quiz_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `quiz_id` INT NOT NULL,
    `question_type` ENUM('multiple_choice','fill_blank','matching','word_recognition','meaning_recognition') DEFAULT 'multiple_choice',
    `question_text` TEXT NOT NULL,
    `question_media` VARCHAR(255) DEFAULT NULL,
    `choice_a` VARCHAR(300) NOT NULL,
    `choice_b` VARCHAR(300) NOT NULL,
    `choice_c` VARCHAR(300) DEFAULT NULL,
    `choice_d` VARCHAR(300) DEFAULT NULL,
    `correct_answer` VARCHAR(10) NOT NULL,
    `explanation` TEXT DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `quiz_attempts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `quiz_attempts`;
CREATE TABLE `quiz_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `quiz_id` INT NOT NULL,
    `score` INT DEFAULT 0,
    `total_questions` INT DEFAULT 0,
    `percentage` DECIMAL(5,2) DEFAULT 0,
    `time_spent_seconds` INT DEFAULT 0,
    `completed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `mock_exams`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `mock_exams`;
CREATE TABLE `mock_exams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `time_limit_minutes` INT DEFAULT 70,
    `listening_count` INT DEFAULT 20,
    `reading_count` INT DEFAULT 20,
    `total_score` INT DEFAULT 200,
    `passing_score` INT DEFAULT 100,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `mock_exam_questions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `mock_exam_questions`;
CREATE TABLE `mock_exam_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT NOT NULL,
    `section` ENUM('listening','reading') NOT NULL,
    `question_number` INT NOT NULL,
    `audio_path` VARCHAR(255) DEFAULT NULL,
    `passage_text` TEXT DEFAULT NULL,
    `question_text` TEXT NOT NULL,
    `choice_a` VARCHAR(300) NOT NULL,
    `choice_b` VARCHAR(300) NOT NULL,
    `choice_c` VARCHAR(300) NOT NULL,
    `choice_d` VARCHAR(300) NOT NULL,
    `correct_answer` ENUM('A','B','C','D') NOT NULL,
    `explanation` TEXT DEFAULT NULL,
    `points` INT DEFAULT 5,
    FOREIGN KEY (`exam_id`) REFERENCES `mock_exams`(`id`) ON DELETE CASCADE,
    INDEX `idx_section` (`section`),
    INDEX `idx_number` (`question_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `mock_exam_attempts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `mock_exam_attempts`;
CREATE TABLE `mock_exam_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `exam_id` INT NOT NULL,
    `listening_score` INT DEFAULT 0,
    `reading_score` INT DEFAULT 0,
    `total_score` INT DEFAULT 0,
    `is_passed` TINYINT(1) DEFAULT 0,
    `time_spent_seconds` INT DEFAULT 0,
    `completed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `mock_exams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `flashcard_decks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `flashcard_decks`;
CREATE TABLE `flashcard_decks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL DEFAULT 'My Flashcards',
    `description` TEXT DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#3B82F6',
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `flashcards`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `flashcards`;
CREATE TABLE `flashcards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `deck_id` INT DEFAULT NULL,
    `term` VARCHAR(500) NOT NULL,
    `definition` TEXT NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('new','known','review') DEFAULT 'new',
    `review_count` INT DEFAULT 0,
    `last_reviewed` DATETIME DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`deck_id`) REFERENCES `flashcard_decks`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_deck` (`deck_id`),
    INDEX `idx_status` (`status`),
    FULLTEXT `idx_search` (`term`, `definition`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `mistake_reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `mistake_reviews`;
CREATE TABLE `mistake_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `module` ENUM('vocabulary','listening','reading','quiz','mock_exam') NOT NULL,
    `question_id` INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `user_answer` VARCHAR(300) NOT NULL,
    `correct_answer` VARCHAR(300) NOT NULL,
    `explanation` TEXT DEFAULT NULL,
    `is_resolved` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_module` (`module`),
    INDEX `idx_resolved` (`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `daily_goals`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `daily_goals`;
CREATE TABLE `daily_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `goal_date` DATE NOT NULL,
    `vocab_target` INT DEFAULT 10,
    `vocab_completed` INT DEFAULT 0,
    `listening_target` INT DEFAULT 5,
    `listening_completed` INT DEFAULT 0,
    `reading_target` INT DEFAULT 5,
    `reading_completed` INT DEFAULT 0,
    `lesson_target` INT DEFAULT 1,
    `lesson_completed` INT DEFAULT 0,
    `is_achieved` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_date` (`user_id`, `goal_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `activity_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `activity_type` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `reference_id` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_type` (`activity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `generated_audio`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `generated_audio`;
CREATE TABLE `generated_audio` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module_type` ENUM('vocabulary','lesson','reading','listening','mock_exam','phrase') NOT NULL DEFAULT 'phrase',
    `module_item_id` INT DEFAULT NULL,
    `korean_text` TEXT NOT NULL,
    `text_hash` VARCHAR(64) NOT NULL,
    `provider` ENUM('google_cloud','openai','edge_tts','custom') NOT NULL DEFAULT 'google_cloud',
    `audio_path` VARCHAR(255) DEFAULT NULL,
    `duration_seconds` DECIMAL(6,2) DEFAULT NULL,
    `file_size_bytes` INT DEFAULT NULL,
    `is_cached` TINYINT(1) DEFAULT 1,
    `play_count` INT DEFAULT 0,
    `status` ENUM('pending','ready','failed') DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_hash_provider` (`text_hash`, `provider`),
    INDEX `idx_hash` (`text_hash`),
    INDEX `idx_provider` (`provider`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `system_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) DEFAULT 'general',
    `description` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA INSERTIONS
-- ============================================================

-- Users & Profiles (Default Admin: admin@epstopik.com / pass: admin123)
-- Default Learner: learner@epstopik.com / pass: learner123
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `status`) VALUES
(1, 'System Administrator', 'admin@epstopik.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(2, 'Korean Learner', 'learner@epstopik.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'learner', 'active');

INSERT INTO `user_profiles` (`user_id`, `daily_target`, `learning_level`, `preferred_study_mode`) VALUES
(1, 30, 'advanced', 'quiz'),
(2, 20, 'beginner', 'flashcard');

INSERT INTO `user_streaks` (`user_id`, `current_streak`, `longest_streak`, `last_activity_date`) VALUES
(1, 5, 12, CURDATE()),
(2, 1, 3, CURDATE());

-- System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('tts_provider', 'browser_tts', 'tts', 'Current TTS provider: browser_tts, google_cloud, openai'),
('tts_audio_preference', 'uploaded_first', 'tts', 'Priority: uploaded_first or generated_first'),
('tts_fallback_enabled', '1', 'tts', 'Fallback to Web Speech API if server TTS fails'),
('tts_default_rate', '1.0', 'tts', 'Default speech rate'),
('tts_default_pitch', '1.0', 'tts', 'Default speech pitch'),
('tts_cache_enabled', '1', 'tts', 'Enable audio caching in database'),
('tts_google_api_key', '', 'tts', 'Google Cloud Text-to-Speech API Key'),
('tts_openai_api_key', '', 'tts', 'OpenAI Audio API Key'),
('site_name', 'EPS Korean Trainer', 'general', 'Application Display Name'),
('allow_registration', '1', 'general', 'Enable public user registration');

-- Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `module`, `icon`, `color`, `sort_order`) VALUES
(1, 'Greetings & Basics', 'greetings-basics', 'Essential Korean greetings and basic expressions', 'vocabulary', '👋', '#3B82F6', 1),
(2, 'Workplace & Factory', 'workplace-factory', 'Vocabulary used in factories, construction sites, and manufacturing', 'vocabulary', '🏗️', '#F59E0B', 2),
(3, 'Tools & Equipment', 'tools-equipment', 'Names of machinery, hand tools, and safety gear', 'vocabulary', '🔧', '#10B981', 3),
(4, 'Safety & Health', 'safety-health', 'Workplace safety signs, health terms, and emergency phrases', 'vocabulary', '🦺', '#EF4444', 4),
(5, 'Food & Dining', 'food-dining', 'Food items, ordering meals, and cafeteria expressions', 'vocabulary', '🍲', '#8B5CF6', 5),
(6, 'Transportation & Places', 'transportation-places', 'Directions, public transit, and common location terms', 'vocabulary', '🚌', '#06B6D4', 6),
(7, 'Time & Numbers', 'time-numbers', 'Sino-Korean and Native Korean numbers, dates, and times', 'vocabulary', '⏰', '#EC4899', 7),
(8, 'Daily Life', 'daily-life', 'Everyday activities, weather, shopping, and hobbies', 'vocabulary', '🏠', '#6366F1', 8),
(11, 'Basic Korean Grammar', 'basic-korean-grammar', 'Fundamental Korean sentence structure and particles', 'lesson', '📖', '#3B82F6', 1),
(12, 'Workplace Expressions', 'workplace-expressions', 'Common phrases used with co-workers and supervisors', 'lesson', '🏢', '#F59E0B', 2),
(13, 'Hangul Writing System', 'hangul-writing-system', 'Learn how to read and write the Korean alphabet', 'lesson', '🇰🇷', '#10B981', 3),
(14, 'Workplace Safety Rules', 'workplace-safety-rules', 'Understanding safety manuals and emergency procedures', 'lesson', '⚠️', '#EF4444', 4),
(21, 'Workplace Dialogues', 'workplace-dialogues', 'Listening practice for factory floor and office conversations', 'listening', '🎧', '#3B82F6', 1),
(22, 'Daily Conversations', 'daily-conversations', 'Listening practice for everyday interactions', 'listening', '💬', '#10B981', 2),
(23, 'Public Announcements', 'public-announcements', 'Listening to announcements in transit, stores, and workplaces', 'listening', '📢', '#F59E0B', 3),
(24, 'EPS-TOPIK Audio Tests', 'eps-topik-audio-tests', 'Simulated EPS-TOPIK listening test questions', 'listening', '📝', '#8B5CF6', 4),
(25, 'Notices & Signs', 'notices-signs', 'Reading workplace notices, warning signs, and public postings', 'reading', '🪧', '#3B82F6', 1),
(26, 'Work Instructions', 'work-instructions', 'Reading equipment manuals, task assignments, and safety guides', 'reading', '📋', '#10B981', 2),
(27, 'Short Passages', 'short-passages', 'Reading comprehension passages about Korean workplace culture', 'reading', '📰', '#F59E0B', 3),
(28, 'Graphs & Schedules', 'graphs-schedules', 'Interpreting charts, work shifts, timetables, and invoices', 'reading', '📊', '#8B5CF6', 4);

-- Authentic Korean Reading Passages
INSERT INTO `reading_passages` (`id`, `category_id`, `title`, `passage_text`, `content_type`, `difficulty`) VALUES
(1, 25, '공장 안전 수칙', '<div class="notice-board">\n<h3 style="text-align:center; margin-bottom:12px;">⚠️ 안전 수칙</h3>\n<ol>\n<li>작업 시 반드시 안전모와 안전화를 착용하십시오.</li>\n<li>기계를 만지기 전에 반드시 전원을 끄십시오.</li>\n<li>비상구 위치를 미리 확인하십시오.</li>\n<li>위험한 물질은 지정된 장소에 보관하십시오.</li>\n<li>사고가 발생하면 즉시 관리자에게 보고하십시오.</li>\n<li>작업 중에 휴대전화를 사용하지 마십시오.</li>\n</ol>\n</div>', 'notice', 'beginner'),
(2, 25, '작업장 표지판', '<div class="sign-board" style="text-align:center; padding: 16px;">\n<p style="font-size:1.5em; font-weight:bold;">🚫 음식물 반입 금지</p>\n<p style="margin-top:8px;">작업장 안에 음식이나 음료수를 가지고 들어갈 수 없습니다.</p>\n<p style="margin-top:4px;">음식은 휴게실에서만 드십시오.</p>\n</div>', 'sign', 'beginner'),
(3, 28, '이번 주 작업 일정', '<div class="schedule">\n<h3 style="text-align:center; margin-bottom:12px;">📅 이번 주 작업 일정</h3>\n<table style="width:100%; border-collapse:collapse; text-align:center;">\n<tr style="background:#f1f5f9;"><th style="padding:8px; border:1px solid #e2e8f0;">요일</th><th style="padding:8px; border:1px solid #e2e8f0;">작업 내용</th><th style="padding:8px; border:1px solid #e2e8f0;">시간</th></tr>\n<tr><td style="padding:8px; border:1px solid #e2e8f0;">월요일</td><td style="padding:8px; border:1px solid #e2e8f0;">기계 점검</td><td style="padding:8px; border:1px solid #e2e8f0;">09:00 ~ 12:00</td></tr>\n<tr><td style="padding:8px; border:1px solid #e2e8f0;">화요일</td><td style="padding:8px; border:1px solid #e2e8f0;">생산 작업</td><td style="padding:8px; border:1px solid #e2e8f0;">08:00 ~ 17:00</td></tr>\n<tr><td style="padding:8px; border:1px solid #e2e8f0;">수요일</td><td style="padding:8px; border:1px solid #e2e8f0;">안전 교육</td><td style="padding:8px; border:1px solid #e2e8f0;">14:00 ~ 16:00</td></tr>\n<tr><td style="padding:8px; border:1px solid #e2e8f0;">목요일</td><td style="padding:8px; border:1px solid #e2e8f0;">생산 작업</td><td style="padding:8px; border:1px solid #e2e8f0;">08:00 ~ 17:00</td></tr>\n<tr><td style="padding:8px; border:1px solid #e2e8f0;">금요일</td><td style="padding:8px; border:1px solid #e2e8f0;">정리 및 보고</td><td style="padding:8px; border:1px solid #e2e8f0;">08:00 ~ 15:00</td></tr>\n</table>\n</div>', 'schedule', 'beginner'),
(4, 27, '김민수 씨의 하루', '<p>김민수 씨는 자동차 공장에서 일합니다. 매일 아침 7시에 일어나서 8시까지 공장에 갑니다. 오전에는 자동차 부품을 조립하고 오후에는 완성된 제품을 검사합니다.</p>\n<p>점심은 12시부터 1시까지입니다. 점심은 공장 식당에서 먹습니다. 식당 음식은 무료입니다. 오후 5시에 퇴근합니다.</p>', 'passage', 'beginner'),
(5, 25, '한국어 교실 안내', '<div class="notice-board">\n<h3 style="text-align:center; margin-bottom:12px;">📚 한국어 교실 안내</h3>\n<p><strong>일시:</strong> 매주 토요일 오전 10시 ~ 12시</p>\n<p><strong>장소:</strong> 다문화가족지원센터 3층 교육실</p>\n<p><strong>대상:</strong> 외국인 근로자</p>\n<p><strong>비용:</strong> 무료</p>\n</div>', 'notice', 'beginner');

-- Reading Questions
INSERT INTO `reading_questions` (`passage_id`, `question_text`, `choice_a`, `choice_b`, `choice_c`, `choice_d`, `correct_answer`, `explanation`, `sort_order`) VALUES
(1, '기계를 만지기 전에 무엇을 해야 합니까?', '장갑을 끼다', '전원을 끄다', '관리자에게 물어보다', '설명서를 읽다', 'B', '안전 수칙 2번: "기계를 만지기 전에 반드시 전원을 끄십시오."', 1),
(1, '사고가 발생하면 어떻게 해야 합니까?', '집에 가다', '경찰에 전화하다', '즉시 관리자에게 보고하다', '도움을 기다리다', 'C', '안전 수칙 5번: "사고가 발생하면 즉시 관리자에게 보고하십시오."', 2),
(2, '작업장에서 할 수 없는 것은 무엇입니까?', '안전모를 쓰다', '음식을 먹다', '기계를 사용하다', '일을 하다', 'B', '표지판: "음식물 반입 금지" — 작업장에서 음식을 먹을 수 없습니다.', 1),
(3, '수요일에 무엇을 합니까?', '기계 점검', '생산 작업', '안전 교육', '정리 및 보고', 'C', '일정표: 수요일 — 안전 교육 (14:00~16:00)', 1),
(4, '김민수 씨는 어디에서 일합니까?', '식당', '병원', '자동차 공장', '학교', 'C', '김민수 씨는 자동차 공장에서 일합니다.', 1);

-- Authentic Korean Listening Questions
INSERT INTO `listening_questions` (`id`, `category_id`, `audio_path`, `dialogue_text`, `question_text`, `choice_a`, `choice_b`, `choice_c`, `choice_d`, `correct_answer`, `explanation`, `difficulty`) VALUES
(1, 21, 'audio/listening/placeholder.mp3', '남자: 저기요, 비상구가 어디에 있어요?\n여자: 저쪽 복도 끝에 있어요. 초록색 표지판이 보이시죠?\n남자: 아, 네. 감사합니다.', '남자는 무엇을 찾고 있습니까?', '화장실', '식당', '사무실', '비상구', 'D', '남자가 "비상구가 어디에 있어요?"라고 물었습니다.', 'beginner'),
(2, 21, 'audio/listening/placeholder.mp3', '관리자: 이 구역에서는 안전모를 꼭 쓰세요.\n근로자: 네, 알겠습니다. 안전화도 신어야 해요?\n관리자: 네, 안전모와 안전화 둘 다 착용해야 합니다.', '관리자는 근로자에게 무엇을 하라고 했습니까?', '일찍 퇴근하다', '안전모를 쓰다', '휴식을 취하다', '기계를 청소하다', 'B', '관리자가 "안전모를 꼭 쓰세요"라고 했습니다.', 'beginner'),
(3, 22, 'audio/listening/placeholder.mp3', '여자: 어서 오세요. 뭐 드시겠어요?\n남자: 김치찌개 하나 주세요.\n여자: 네, 음료수는 뭐로 하시겠어요?\n남자: 물 주세요.', '이 대화는 어디에서 하고 있습니까?', '식당', '병원', '은행', '버스 정류장', 'A', '"뭐 드시겠어요?"는 식당에서 하는 말입니다.', 'beginner');

-- Authentic Vocabulary Seed Data
INSERT INTO `vocabulary` (`id`, `category_id`, `korean`, `english`, `pronunciation`, `part_of_speech`, `example_sentence_korean`, `example_sentence_english`, `difficulty`, `sort_order`) VALUES
(1, 1, '안녕하세요', 'Hello / How are you', 'annyeonghaseyo', 'expression', '안녕하세요! 반갑습니다.', 'Hello! Nice to meet you.', 'beginner', 1),
(2, 1, '감사합니다', 'Thank you', 'gamsahamnida', 'expression', '도와주셔서 감사합니다.', 'Thank you for your help.', 'beginner', 2),
(3, 1, '죄송합니다', 'I am sorry', 'joesonghamnida', 'expression', '늦어서 죄송합니다.', 'I am sorry for being late.', 'beginner', 3),
(4, 2, '작업장', 'Workplace / Work area', 'jageobjang', 'noun', '작업장은 항상 깨끗하게 유지해야 합니다.', 'The workplace must always be kept clean.', 'beginner', 4),
(5, 3, '안전모', 'Safety helmet / Hard hat', 'anjeonmo', 'noun', '공장에 들어갈 때 안전모를 꼭 쓰세요.', 'Make sure to wear a safety helmet when entering the factory.', 'beginner', 5),
(6, 3, '망치', 'Hammer', 'mangchi', 'noun', '망치로 못을 박으세요.', 'Drive the nail with a hammer.', 'beginner', 6),
(7, 4, '위험', 'Danger', 'wiheom', 'noun', '이 구역은 위험하니 들어가면 안 됩니다.', 'This area is dangerous so you must not enter.', 'beginner', 7),
(8, 4, '비상구', 'Emergency exit', 'bisanggu', 'noun', '불이 나면 비상구로 대피하세요.', 'If a fire breaks out, evacuate through the emergency exit.', 'beginner', 8);

-- Quizzes
INSERT INTO `quizzes` (`id`, `title`, `description`, `category_id`, `quiz_type`, `difficulty`, `time_limit_minutes`, `question_count`) VALUES
(1, 'Basic Korean Vocabulary Quiz', 'Test your knowledge of essential Korean words', 1, 'vocabulary', 'beginner', 10, 5),
(2, 'Workplace Safety Quiz', 'Quiz on workplace safety vocabulary and signs', 4, 'vocabulary', 'beginner', 10, 5);

INSERT INTO `quiz_questions` (`quiz_id`, `question_type`, `question_text`, `choice_a`, `choice_b`, `choice_c`, `choice_d`, `correct_answer`, `explanation`, `sort_order`) VALUES
(1, 'multiple_choice', 'What does 일하다 mean?', 'to eat', 'to work', 'to sleep', 'to run', 'B', '일하다 (ilhada) means "to work".', 1),
(1, 'multiple_choice', 'What is the Korean word for "hammer"?', '렌치', '드라이버', '망치', '측정기', 'C', '망치 (mangchi) means "hammer".', 2),
(2, 'multiple_choice', 'What does 위험 mean?', 'Safety', 'Caution', 'Danger', 'Exit', 'C', '위험 (wiheom) means "danger".', 1),
(2, 'multiple_choice', 'What is 비상구?', 'Main entrance', 'Emergency exit', 'Office door', 'Storage room', 'B', '비상구 (bisanggu) means "emergency exit".', 2);

-- Mock Exam
INSERT INTO `mock_exams` (`id`, `title`, `description`, `time_limit_minutes`, `listening_count`, `reading_count`, `total_score`, `passing_score`) VALUES
(1, 'EPS-TOPIK Practice Exam 1', 'Full practice exam simulating the actual EPS-TOPIK test format with listening and reading sections.', 70, 5, 5, 40, 16);

INSERT INTO `mock_exam_questions` (`exam_id`, `section`, `question_number`, `audio_path`, `passage_text`, `question_text`, `choice_a`, `choice_b`, `choice_c`, `choice_d`, `correct_answer`, `explanation`, `points`) VALUES
(1, 'listening', 1, 'audio/exam/listen1.mp3', NULL, 'What is the woman looking for?', 'The office', 'The cafeteria', 'The restroom', 'The parking lot', 'C', 'The woman asks "화장실이 어디에요?" (Where is the restroom?)', 4),
(1, 'reading', 6, NULL, '공지사항\n내일(3월 15일) 오후 2시에 안전 교육이 있습니다.\n모든 직원은 반드시 참석하세요.\n장소: 2층 회의실', 'When is the safety training?', 'March 14, 2 PM', 'March 15, 2 PM', 'March 15, 3 PM', 'March 16, 2 PM', 'B', 'The notice says "내일(3월 15일) 오후 2시" (Tomorrow, March 15, 2 PM)', 4);

SET FOREIGN_KEY_CHECKS=1;
