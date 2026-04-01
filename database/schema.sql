-- ============================================================
-- EPS Korean Trainer - Complete Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS eps_topik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eps_topik;

-- ============================================================
-- USERS & PROFILES
-- ============================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('learner','admin') DEFAULT 'learner',
    status ENUM('active','inactive','banned') DEFAULT 'active',
    profile_image VARCHAR(255) DEFAULT NULL,
    email_verified_at DATETIME DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    daily_target INT DEFAULT 20,
    learning_level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    preferred_study_mode ENUM('flashcard','list','quiz') DEFAULT 'flashcard',
    sound_enabled TINYINT(1) DEFAULT 1,
    dark_mode TINYINT(1) DEFAULT 0,
    notification_enabled TINYINT(1) DEFAULT 1,
    timezone VARCHAR(50) DEFAULT 'Asia/Manila',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_streaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    current_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_activity_date DATE DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES (shared across modules)
-- ============================================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    module ENUM('lesson','vocabulary','listening','reading','quiz','mock_exam') NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    color VARCHAR(20) DEFAULT '#3B82F6',
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_module (module),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- LESSONS
-- ============================================================

CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    difficulty ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    estimated_minutes INT DEFAULT 15,
    content LONGTEXT NOT NULL,
    summary TEXT DEFAULT NULL,
    tips TEXT DEFAULT NULL,
    audio_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    status ENUM('published','draft') DEFAULT 'published',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_difficulty (difficulty),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE lesson_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (user_id, lesson_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bookmarkable_type ENUM('lesson','vocabulary','reading') NOT NULL,
    bookmarkable_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bookmark (user_id, bookmarkable_type, bookmarkable_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- VOCABULARY
-- ============================================================

CREATE TABLE vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    korean_word VARCHAR(100) NOT NULL,
    transliteration VARCHAR(150) DEFAULT NULL,
    english_meaning VARCHAR(200) NOT NULL,
    part_of_speech ENUM('noun','verb','adjective','adverb','phrase','expression','other') DEFAULT 'noun',
    example_sentence_kr VARCHAR(500) DEFAULT NULL,
    example_sentence_en VARCHAR(500) DEFAULT NULL,
    audio_path VARCHAR(255) DEFAULT NULL,
    difficulty ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_difficulty (difficulty),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE user_vocabulary_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vocabulary_id INT NOT NULL,
    status ENUM('learning','mastered','hard','favorite') DEFAULT 'learning',
    review_count INT DEFAULT 0,
    last_reviewed DATETIME DEFAULT NULL,
    UNIQUE KEY unique_uv (user_id, vocabulary_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vocabulary_id) REFERENCES vocabulary(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- LISTENING QUESTIONS
-- ============================================================

CREATE TABLE listening_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    audio_path VARCHAR(255) NOT NULL,
    question_text TEXT NOT NULL,
    choice_a VARCHAR(300) NOT NULL,
    choice_b VARCHAR(300) NOT NULL,
    choice_c VARCHAR(300) NOT NULL,
    choice_d VARCHAR(300) NOT NULL,
    correct_answer ENUM('A','B','C','D') NOT NULL,
    explanation TEXT DEFAULT NULL,
    difficulty ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_difficulty (difficulty)
) ENGINE=InnoDB;

-- ============================================================
-- READING PASSAGES & QUESTIONS
-- ============================================================

CREATE TABLE reading_passages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    passage_text LONGTEXT NOT NULL,
    content_type ENUM('notice','sign','instruction','dialogue','passage','schedule','workplace') DEFAULT 'passage',
    difficulty ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_difficulty (difficulty)
) ENGINE=InnoDB;

CREATE TABLE reading_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passage_id INT NOT NULL,
    question_text TEXT NOT NULL,
    choice_a VARCHAR(300) NOT NULL,
    choice_b VARCHAR(300) NOT NULL,
    choice_c VARCHAR(300) NOT NULL,
    choice_d VARCHAR(300) NOT NULL,
    correct_answer ENUM('A','B','C','D') NOT NULL,
    explanation TEXT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (passage_id) REFERENCES reading_passages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- QUIZZES
-- ============================================================

CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    quiz_type ENUM('vocabulary','listening','reading','mixed') DEFAULT 'mixed',
    difficulty ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    time_limit_minutes INT DEFAULT NULL,
    question_count INT DEFAULT 10,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_type ENUM('multiple_choice','fill_blank','matching','word_recognition','meaning_recognition') DEFAULT 'multiple_choice',
    question_text TEXT NOT NULL,
    question_media VARCHAR(255) DEFAULT NULL,
    choice_a VARCHAR(300) NOT NULL,
    choice_b VARCHAR(300) NOT NULL,
    choice_c VARCHAR(300) DEFAULT NULL,
    choice_d VARCHAR(300) DEFAULT NULL,
    correct_answer VARCHAR(10) NOT NULL,
    explanation TEXT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0,
    time_spent_seconds INT DEFAULT 0,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_user_quiz (user_id, quiz_id)
) ENGINE=InnoDB;

CREATE TABLE quiz_attempt_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer VARCHAR(10) DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- MOCK EXAMS
-- ============================================================

CREATE TABLE mock_exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    time_limit_minutes INT DEFAULT 70,
    listening_count INT DEFAULT 25,
    reading_count INT DEFAULT 25,
    total_score INT DEFAULT 200,
    passing_score INT DEFAULT 80,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE mock_exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    section ENUM('listening','reading') NOT NULL,
    question_number INT NOT NULL,
    audio_path VARCHAR(255) DEFAULT NULL,
    passage_text TEXT DEFAULT NULL,
    question_text TEXT NOT NULL,
    choice_a VARCHAR(300) NOT NULL,
    choice_b VARCHAR(300) NOT NULL,
    choice_c VARCHAR(300) NOT NULL,
    choice_d VARCHAR(300) NOT NULL,
    correct_answer ENUM('A','B','C','D') NOT NULL,
    explanation TEXT DEFAULT NULL,
    points INT DEFAULT 4,
    FOREIGN KEY (exam_id) REFERENCES mock_exams(id) ON DELETE CASCADE,
    INDEX idx_section (section)
) ENGINE=InnoDB;

CREATE TABLE mock_exam_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    listening_score INT DEFAULT 0,
    reading_score INT DEFAULT 0,
    total_score INT DEFAULT 0,
    total_correct INT DEFAULT 0,
    total_incorrect INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0,
    time_spent_seconds INT DEFAULT 0,
    status ENUM('in_progress','completed','abandoned') DEFAULT 'in_progress',
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES mock_exams(id) ON DELETE CASCADE,
    INDEX idx_user_exam (user_id, exam_id)
) ENGINE=InnoDB;

CREATE TABLE mock_exam_attempt_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    user_answer VARCHAR(10) DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES mock_exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES mock_exam_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DAILY GOALS
-- ============================================================

CREATE TABLE daily_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    goal_date DATE NOT NULL,
    target_words INT DEFAULT 20,
    target_lessons INT DEFAULT 1,
    target_listening INT DEFAULT 10,
    target_reading INT DEFAULT 5,
    completed_words INT DEFAULT 0,
    completed_lessons INT DEFAULT 0,
    completed_listening INT DEFAULT 0,
    completed_reading INT DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_daily (user_id, goal_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- MISTAKE REVIEWS
-- ============================================================

CREATE TABLE mistake_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module ENUM('listening','reading','quiz','mock_exam') NOT NULL,
    question_id INT NOT NULL,
    question_text TEXT NOT NULL,
    user_answer VARCHAR(300) NOT NULL,
    correct_answer VARCHAR(300) NOT NULL,
    explanation TEXT DEFAULT NULL,
    is_reviewed TINYINT(1) DEFAULT 0,
    retry_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_module (user_id, module)
) ENGINE=InnoDB;

-- ============================================================
-- ACTIVITY LOG
-- ============================================================

CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description VARCHAR(500) NOT NULL,
    reference_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_activity (user_id, created_at)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','achievement') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- ============================================================
-- ADMIN LOGS
-- ============================================================

CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_table VARCHAR(50) DEFAULT NULL,
    target_id INT DEFAULT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SYSTEM SETTINGS (for admin-configurable options)
-- ============================================================

CREATE TABLE system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    description VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Voice/TTS defaults
INSERT INTO system_settings (setting_key, setting_value, setting_group, description) VALUES
('tts_provider', 'browser_tts', 'voice', 'Active TTS provider: browser_tts | google_cloud | openai'),
('tts_fallback_enabled', '1', 'voice', 'Enable browser TTS as fallback when premium unavailable'),
('tts_default_rate', '1', 'voice', 'Default speech rate (0.5 - 2.0)'),
('tts_default_pitch', '1', 'voice', 'Default speech pitch (0.5 - 2.0)'),
('tts_audio_preference', 'uploaded_first', 'voice', 'Audio priority: uploaded_first | generated_first | browser_only'),
('tts_google_api_key', '', 'voice', 'Google Cloud TTS API key'),
('tts_openai_api_key', '', 'voice', 'OpenAI TTS API key'),
('tts_cache_enabled', '1', 'voice', 'Cache generated audio files');

-- ============================================================
-- GENERATED AUDIO CACHE
-- ============================================================

CREATE TABLE generated_audio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_type ENUM('vocabulary','lesson','reading','listening','mock_exam','phrase') NOT NULL,
    module_item_id INT DEFAULT NULL,
    korean_text TEXT NOT NULL,
    text_hash VARCHAR(64) NOT NULL,
    provider ENUM('browser_tts','google_cloud','openai','uploaded') DEFAULT 'browser_tts',
    audio_path VARCHAR(500) DEFAULT NULL,
    audio_format VARCHAR(10) DEFAULT 'mp3',
    duration_seconds DECIMAL(6,2) DEFAULT NULL,
    is_cached TINYINT(1) DEFAULT 0,
    status ENUM('pending','generating','ready','failed') DEFAULT 'pending',
    error_message VARCHAR(500) DEFAULT NULL,
    play_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_text_provider (text_hash, provider),
    INDEX idx_module (module_type, module_item_id),
    INDEX idx_status (status),
    INDEX idx_text_hash (text_hash)
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin user (password: admin123)
INSERT INTO users (full_name, email, password, role, status) VALUES 
('Admin User', 'admin@epstrainer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Sample learners (password: password)
INSERT INTO users (full_name, email, password, role, status) VALUES 
('Juan Dela Cruz', 'juan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'learner', 'active'),
('Maria Santos', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'learner', 'active'),
('Pedro Reyes', 'pedro@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'learner', 'active');

-- User profiles
INSERT INTO user_profiles (user_id, daily_target, learning_level) VALUES 
(1, 30, 'advanced'),
(2, 20, 'beginner'),
(3, 25, 'intermediate'),
(4, 15, 'beginner');

-- User streaks
INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_activity_date) VALUES 
(2, 5, 12, CURDATE()),
(3, 3, 8, CURDATE()),
(4, 1, 4, CURDATE());

-- ============================================================
-- CATEGORIES
-- ============================================================

-- Lesson categories
INSERT INTO categories (name, slug, description, module, icon, color, sort_order) VALUES 
('Hangul Basics', 'hangul-basics', 'Learn Korean alphabet fundamentals', 'lesson', '🔤', '#3B82F6', 1),
('Beginner Vocabulary', 'beginner-vocabulary', 'Essential words for beginners', 'lesson', '📖', '#10B981', 2),
('Grammar Basics', 'grammar-basics', 'Basic Korean grammar patterns', 'lesson', '📝', '#8B5CF6', 3),
('Sentence Patterns', 'sentence-patterns', 'Common sentence structures', 'lesson', '💬', '#F59E0B', 4),
('Workplace Vocabulary', 'workplace-vocabulary', 'Words used in Korean workplaces', 'lesson', '🏭', '#EF4444', 5),
('Factory Vocabulary', 'factory-vocabulary', 'Factory and manufacturing terms', 'lesson', '⚙️', '#6366F1', 6),
('Safety Vocabulary', 'safety-vocabulary', 'Safety signs and related words', 'lesson', '⚠️', '#F97316', 7),
('Daily Conversation', 'daily-conversation', 'Everyday Korean phrases', 'lesson', '🗣️', '#14B8A6', 8),
('Reading Tips', 'reading-tips', 'Strategies for EPS-TOPIK reading', 'lesson', '📚', '#EC4899', 9),
('Listening Tips', 'listening-tips', 'Strategies for EPS-TOPIK listening', 'lesson', '🎧', '#06B6D4', 10);

-- Vocabulary categories
INSERT INTO categories (name, slug, description, module, icon, color, sort_order) VALUES 
('Work Actions', 'work-actions', 'Action verbs used at work', 'vocabulary', '💪', '#3B82F6', 1),
('Tools', 'tools', 'Names of tools and equipment', 'vocabulary', '🔧', '#10B981', 2),
('Workplace Objects', 'workplace-objects', 'Common workplace items', 'vocabulary', '🏢', '#8B5CF6', 3),
('Safety Signs', 'safety-signs', 'Safety-related vocabulary', 'vocabulary', '🚧', '#EF4444', 4),
('Numbers & Dates', 'numbers-dates', 'Numbers, dates, and time', 'vocabulary', '🔢', '#F59E0B', 5),
('Places', 'places', 'Location and place names', 'vocabulary', '📍', '#6366F1', 6),
('Daily Words', 'daily-words', 'Everyday essential words', 'vocabulary', '☀️', '#14B8A6', 7),
('Instructions', 'instructions', 'Command and instruction words', 'vocabulary', '📋', '#EC4899', 8),
('Verbs', 'verbs', 'Common Korean verbs', 'vocabulary', '🏃', '#06B6D4', 9),
('Greetings', 'greetings', 'Greeting expressions', 'vocabulary', '👋', '#F97316', 10);

-- Listening categories
INSERT INTO categories (name, slug, description, module, icon, color, sort_order) VALUES 
('Workplace Conversations', 'workplace-conversations', 'Dialogues at work', 'listening', '🏭', '#3B82F6', 1),
('Daily Life', 'daily-life-listening', 'Everyday life scenarios', 'listening', '🏠', '#10B981', 2),
('Announcements', 'announcements', 'Public announcements and notices', 'listening', '📢', '#F59E0B', 3),
('Instructions', 'instructions-listening', 'Listening to instructions', 'listening', '📋', '#8B5CF6', 4);

-- Reading categories
INSERT INTO categories (name, slug, description, module, icon, color, sort_order) VALUES 
('Notices', 'notices', 'Workplace and public notices', 'reading', '📌', '#3B82F6', 1),
('Signs', 'signs', 'Signs and labels', 'reading', '🪧', '#EF4444', 2),
('Workplace Instructions', 'workplace-instructions', 'Work instruction documents', 'reading', '📄', '#10B981', 3),
('Dialogues', 'dialogues-reading', 'Written conversations', 'reading', '💬', '#F59E0B', 4),
('Schedules', 'schedules', 'Timetables and schedules', 'reading', '📅', '#8B5CF6', 5);

-- ============================================================
-- SAMPLE LESSONS
-- ============================================================

INSERT INTO lessons (category_id, title, slug, difficulty, estimated_minutes, content, summary, tips) VALUES 
(1, 'Introduction to Hangul - Vowels', 'intro-hangul-vowels', 'beginner', 20,
'<h2>Basic Korean Vowels (모음)</h2>
<p>Korean has 10 basic vowels. Let''s learn them one by one.</p>
<div class="vocab-grid">
<div class="vocab-item"><span class="korean-large">ㅏ</span> <span class="romanize">a</span> - as in "father"</div>
<div class="vocab-item"><span class="korean-large">ㅓ</span> <span class="romanize">eo</span> - as in "cup"</div>
<div class="vocab-item"><span class="korean-large">ㅗ</span> <span class="romanize">o</span> - as in "go"</div>
<div class="vocab-item"><span class="korean-large">ㅜ</span> <span class="romanize">u</span> - as in "moon"</div>
<div class="vocab-item"><span class="korean-large">ㅡ</span> <span class="romanize">eu</span> - no English equivalent (unrounded "oo")</div>
<div class="vocab-item"><span class="korean-large">ㅣ</span> <span class="romanize">i</span> - as in "see"</div>
<div class="vocab-item"><span class="korean-large">ㅐ</span> <span class="romanize">ae</span> - as in "bed"</div>
<div class="vocab-item"><span class="korean-large">ㅔ</span> <span class="romanize">e</span> - as in "yes"</div>
<div class="vocab-item"><span class="korean-large">ㅘ</span> <span class="romanize">wa</span> - as in "want"</div>
<div class="vocab-item"><span class="korean-large">ㅢ</span> <span class="romanize">ui</span> - "eu" + "i"</div>
</div>
<h3>Practice</h3>
<p>Try writing each vowel 5 times. Say the sound out loud as you write.</p>', 
'Learn the 10 basic Korean vowels with pronunciation guides.',
'Practice writing each vowel slowly. Focus on the stroke order. Listen to native speakers for accurate pronunciation.'),

(1, 'Introduction to Hangul - Consonants', 'intro-hangul-consonants', 'beginner', 25,
'<h2>Basic Korean Consonants (자음)</h2>
<p>Korean has 14 basic consonants. Let''s learn them!</p>
<div class="vocab-grid">
<div class="vocab-item"><span class="korean-large">ㄱ</span> <span class="romanize">g/k</span></div>
<div class="vocab-item"><span class="korean-large">ㄴ</span> <span class="romanize">n</span></div>
<div class="vocab-item"><span class="korean-large">ㄷ</span> <span class="romanize">d/t</span></div>
<div class="vocab-item"><span class="korean-large">ㄹ</span> <span class="romanize">r/l</span></div>
<div class="vocab-item"><span class="korean-large">ㅁ</span> <span class="romanize">m</span></div>
<div class="vocab-item"><span class="korean-large">ㅂ</span> <span class="romanize">b/p</span></div>
<div class="vocab-item"><span class="korean-large">ㅅ</span> <span class="romanize">s</span></div>
<div class="vocab-item"><span class="korean-large">ㅇ</span> <span class="romanize">ng (silent at start)</span></div>
<div class="vocab-item"><span class="korean-large">ㅈ</span> <span class="romanize">j</span></div>
<div class="vocab-item"><span class="korean-large">ㅊ</span> <span class="romanize">ch</span></div>
<div class="vocab-item"><span class="korean-large">ㅋ</span> <span class="romanize">k</span></div>
<div class="vocab-item"><span class="korean-large">ㅌ</span> <span class="romanize">t</span></div>
<div class="vocab-item"><span class="korean-large">ㅍ</span> <span class="romanize">p</span></div>
<div class="vocab-item"><span class="korean-large">ㅎ</span> <span class="romanize">h</span></div>
</div>',
'Master the 14 basic Korean consonants.',
'Some consonants change sound depending on their position in a syllable. Pay attention to initial vs final sounds.'),

(5, 'Workplace Greetings', 'workplace-greetings', 'beginner', 15,
'<h2>Essential Workplace Greetings</h2>
<p>In Korean workplaces, greetings are very important. Here are the most common ones:</p>
<div class="vocab-grid">
<div class="vocab-item"><span class="korean-large">안녕하세요</span><br><span class="romanize">annyeonghaseyo</span><br>Hello (formal)</div>
<div class="vocab-item"><span class="korean-large">수고하셨습니다</span><br><span class="romanize">sugohasheosseumnida</span><br>You''ve worked hard (end of work)</div>
<div class="vocab-item"><span class="korean-large">감사합니다</span><br><span class="romanize">gamsahamnida</span><br>Thank you (formal)</div>
<div class="vocab-item"><span class="korean-large">죄송합니다</span><br><span class="romanize">joesonghamnida</span><br>I''m sorry (formal)</div>
<div class="vocab-item"><span class="korean-large">네, 알겠습니다</span><br><span class="romanize">ne, algesseumnida</span><br>Yes, I understand</div>
<div class="vocab-item"><span class="korean-large">실례합니다</span><br><span class="romanize">sillyehamnida</span><br>Excuse me</div>
</div>
<h3>Tips for the Workplace</h3>
<ul>
<li>Always use formal speech (존댓말) with superiors and colleagues</li>
<li>Bow slightly when greeting</li>
<li>Use both hands when giving or receiving items</li>
</ul>',
'Learn essential Korean greetings for the workplace.',
'Always default to formal speech in Korean workplaces. It shows respect and professionalism.');

-- ============================================================
-- SAMPLE VOCABULARY
-- ============================================================

INSERT INTO vocabulary (category_id, korean_word, transliteration, english_meaning, part_of_speech, example_sentence_kr, example_sentence_en, difficulty) VALUES 
-- Work Actions (category 11)
(11, '일하다', 'ilhada', 'to work', 'verb', '저는 공장에서 일합니다.', 'I work at a factory.', 'beginner'),
(11, '만들다', 'mandeulda', 'to make/produce', 'verb', '이 부품을 만들어 주세요.', 'Please make this part.', 'beginner'),
(11, '확인하다', 'hwaginhada', 'to check/confirm', 'verb', '안전 장비를 확인하세요.', 'Please check the safety equipment.', 'beginner'),
(11, '정리하다', 'jeongrihada', 'to organize/clean up', 'verb', '작업 후에 정리하세요.', 'Please clean up after work.', 'beginner'),
(11, '운반하다', 'unbanhada', 'to transport/carry', 'verb', '이 상자를 운반해 주세요.', 'Please carry this box.', 'intermediate'),

-- Tools (category 12)
(12, '망치', 'mangchi', 'hammer', 'noun', '망치를 가져다 주세요.', 'Please bring the hammer.', 'beginner'),
(12, '드라이버', 'deuraibeo', 'screwdriver', 'noun', '드라이버가 필요합니다.', 'I need a screwdriver.', 'beginner'),
(12, '렌치', 'renchi', 'wrench', 'noun', '렌치로 볼트를 조이세요.', 'Tighten the bolt with a wrench.', 'beginner'),
(12, '용접기', 'yongjeobgi', 'welding machine', 'noun', '용접기를 사용할 때 주의하세요.', 'Be careful when using the welding machine.', 'intermediate'),
(12, '측정기', 'cheukjeonggi', 'measuring tool', 'noun', '측정기로 길이를 재세요.', 'Measure the length with the measuring tool.', 'intermediate'),

-- Safety Signs (category 14)
(14, '위험', 'wiheom', 'danger', 'noun', '위험! 들어가지 마세요.', 'Danger! Do not enter.', 'beginner'),
(14, '주의', 'juui', 'caution', 'noun', '주의해서 작업하세요.', 'Work with caution.', 'beginner'),
(14, '안전모', 'anjeonmo', 'safety helmet', 'noun', '안전모를 쓰세요.', 'Wear your safety helmet.', 'beginner'),
(14, '소화기', 'sohwagi', 'fire extinguisher', 'noun', '소화기 위치를 확인하세요.', 'Check the fire extinguisher location.', 'beginner'),
(14, '비상구', 'bisanggu', 'emergency exit', 'noun', '비상구를 알아 두세요.', 'Know where the emergency exit is.', 'beginner'),

-- Numbers & Dates (category 15)
(15, '하나', 'hana', 'one (native Korean)', 'noun', '하나 주세요.', 'Please give me one.', 'beginner'),
(15, '둘', 'dul', 'two (native Korean)', 'noun', '사과 둘 주세요.', 'Please give me two apples.', 'beginner'),
(15, '오늘', 'oneul', 'today', 'noun', '오늘 몇 시에 퇴근해요?', 'What time do you leave work today?', 'beginner'),
(15, '내일', 'naeil', 'tomorrow', 'noun', '내일 아침에 만나요.', 'Let''s meet tomorrow morning.', 'beginner'),
(15, '월요일', 'woryoil', 'Monday', 'noun', '월요일에 출근합니다.', 'I go to work on Monday.', 'beginner'),

-- Daily Words (category 17)
(17, '밥', 'bap', 'rice/meal', 'noun', '밥 먹었어요?', 'Did you eat?', 'beginner'),
(17, '물', 'mul', 'water', 'noun', '물 좀 주세요.', 'Please give me some water.', 'beginner'),
(17, '집', 'jip', 'house/home', 'noun', '집에 가고 싶어요.', 'I want to go home.', 'beginner'),
(17, '병원', 'byeongwon', 'hospital', 'noun', '병원에 가야 해요.', 'I need to go to the hospital.', 'beginner'),
(17, '화장실', 'hwajangsil', 'bathroom', 'noun', '화장실이 어디에요?', 'Where is the bathroom?', 'beginner'),

-- Greetings (category 20)
(20, '안녕하세요', 'annyeonghaseyo', 'hello (formal)', 'expression', '안녕하세요, 만나서 반갑습니다.', 'Hello, nice to meet you.', 'beginner'),
(20, '감사합니다', 'gamsahamnida', 'thank you (formal)', 'expression', '도와주셔서 감사합니다.', 'Thank you for helping.', 'beginner'),
(20, '죄송합니다', 'joesonghamnida', 'I am sorry (formal)', 'expression', '늦어서 죄송합니다.', 'I''m sorry for being late.', 'beginner'),
(20, '잘 부탁합니다', 'jal butakhamnida', 'please take care of me', 'expression', '앞으로 잘 부탁합니다.', 'I look forward to working with you.', 'beginner'),
(20, '수고하셨습니다', 'sugohasheosseumnida', 'you''ve worked hard', 'expression', '오늘도 수고하셨습니다.', 'You''ve worked hard today too.', 'beginner');

-- ============================================================
-- SAMPLE LISTENING QUESTIONS
-- ============================================================

INSERT INTO listening_questions (category_id, audio_path, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty) VALUES 
(21, 'audio/listening/sample1.mp3', 'What is the man asking about?', 'The location of the bathroom', 'The time for lunch', 'The name of the supervisor', 'The location of the emergency exit', 'D', 'The man asks "비상구가 어디에 있어요?" which means "Where is the emergency exit?"', 'beginner'),
(21, 'audio/listening/sample2.mp3', 'What does the supervisor tell the worker to do?', 'Go home early', 'Wear a safety helmet', 'Take a break', 'Clean the machine', 'B', 'The supervisor says "안전모를 꼭 쓰세요" meaning "Make sure to wear your safety helmet."', 'beginner'),
(22, 'audio/listening/sample3.mp3', 'Where is this conversation taking place?', 'At a restaurant', 'At a hospital', 'At a bank', 'At a bus stop', 'A', 'The dialogue mentions ordering food: "뭐 드시겠어요?" (What would you like to eat?)', 'beginner'),
(22, 'audio/listening/sample4.mp3', 'What time does the store close?', '8 PM', '9 PM', '10 PM', '11 PM', 'C', 'The announcement says "저희 매장은 밤 10시에 문을 닫습니다" (Our store closes at 10 PM)', 'beginner'),
(23, 'audio/listening/sample5.mp3', 'What is being announced?', 'A fire drill schedule', 'A holiday notice', 'A pay raise', 'A new employee introduction', 'A', 'The announcement talks about 소방 훈련 (fire drill) scheduled for next week.', 'intermediate');

-- ============================================================
-- SAMPLE READING PASSAGES & QUESTIONS
-- ============================================================

INSERT INTO reading_passages (category_id, title, passage_text, content_type, difficulty) VALUES 
(25, 'Factory Safety Notice', '<div class="notice-board">
<h3>⚠️ 안전 수칙 (Safety Rules)</h3>
<ol>
<li>작업 시 안전모를 꼭 쓰세요. (Always wear a safety helmet while working.)</li>
<li>기계를 만지기 전에 전원을 끄세요. (Turn off the power before touching machinery.)</li>
<li>비상구 위치를 확인하세요. (Check the emergency exit location.)</li>
<li>위험한 물질은 지정된 곳에 보관하세요. (Store hazardous materials in designated areas.)</li>
<li>사고가 발생하면 즉시 보고하세요. (Report accidents immediately.)</li>
</ol>
</div>', 'notice', 'beginner'),

(26, 'Workplace Sign', '<div class="sign-board">
<p class="korean-xlarge text-center">🚫 음식물 반입 금지</p>
<p class="text-center">No Food or Drinks Allowed</p>
<p class="korean-large text-center">작업장 내 음식물 반입을 금지합니다.</p>
<p class="text-center">Bringing food into the work area is prohibited.</p>
</div>', 'sign', 'beginner'),

(27, 'Work Schedule', '<div class="schedule">
<h3>📅 이번 주 작업 일정 (This Week''s Work Schedule)</h3>
<table class="schedule-table">
<tr><th>요일 (Day)</th><th>작업 내용 (Task)</th><th>시간 (Time)</th></tr>
<tr><td>월요일 (Mon)</td><td>기계 점검 (Machine inspection)</td><td>09:00 - 12:00</td></tr>
<tr><td>화요일 (Tue)</td><td>생산 작업 (Production work)</td><td>08:00 - 17:00</td></tr>
<tr><td>수요일 (Wed)</td><td>안전 교육 (Safety training)</td><td>14:00 - 16:00</td></tr>
<tr><td>목요일 (Thu)</td><td>생산 작업 (Production work)</td><td>08:00 - 17:00</td></tr>
<tr><td>금요일 (Fri)</td><td>정리 및 보고 (Cleanup & report)</td><td>08:00 - 15:00</td></tr>
</table>
</div>', 'schedule', 'beginner');

-- Reading Questions
INSERT INTO reading_questions (passage_id, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES 
(1, 'According to the notice, what should you do before touching machinery?', 'Wear gloves', 'Turn off the power', 'Ask the supervisor', 'Read the manual', 'B', 'Rule #2 states: 기계를 만지기 전에 전원을 끄세요 (Turn off the power before touching machinery).', 1),
(1, 'What should you do when an accident occurs?', 'Leave the building', 'Call the police', 'Report immediately', 'Wait for help', 'C', 'Rule #5 states: 사고가 발생하면 즉시 보고하세요 (Report accidents immediately).', 2),
(2, 'What is NOT allowed in the work area?', 'Mobile phones', 'Food and drinks', 'Personal belongings', 'Music', 'B', 'The sign clearly states: 음식물 반입 금지 (No food or drinks allowed).', 1),
(3, 'What is scheduled for Wednesday?', 'Machine inspection', 'Production work', 'Safety training', 'Cleanup', 'C', 'The schedule shows Wednesday: 안전 교육 (Safety training) from 14:00-16:00.', 1),
(3, 'What time does Friday work end?', '3 PM', '4 PM', '5 PM', '6 PM', 'A', 'Friday schedule shows 08:00 - 15:00 (3 PM).', 2);

-- ============================================================
-- SAMPLE QUIZZES
-- ============================================================

INSERT INTO quizzes (title, description, category_id, quiz_type, difficulty, time_limit_minutes, question_count) VALUES 
('Basic Korean Vocabulary Quiz', 'Test your knowledge of essential Korean words', 11, 'vocabulary', 'beginner', 10, 5),
('Workplace Safety Quiz', 'Quiz on workplace safety vocabulary and signs', 14, 'vocabulary', 'beginner', 10, 5),
('Mixed Beginner Quiz', 'A mix of vocabulary, reading, and listening questions', NULL, 'mixed', 'beginner', 15, 10);

INSERT INTO quiz_questions (quiz_id, question_type, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, sort_order) VALUES 
-- Quiz 1: Basic Vocabulary
(1, 'multiple_choice', 'What does 일하다 mean?', 'to eat', 'to work', 'to sleep', 'to run', 'B', '일하다 (ilhada) means "to work".', 1),
(1, 'multiple_choice', 'What is the Korean word for "hammer"?', '렌치', '드라이버', '망치', '측정기', 'C', '망치 (mangchi) means "hammer".', 2),
(1, 'meaning_recognition', 'Which word means "water"?', '밥', '집', '물', '길', 'C', '물 (mul) means "water".', 3),
(1, 'multiple_choice', 'What does 감사합니다 mean?', 'I am sorry', 'Goodbye', 'Thank you', 'Hello', 'C', '감사합니다 (gamsahamnida) means "Thank you" in formal speech.', 4),
(1, 'multiple_choice', '"안전모" refers to what?', 'Safety goggles', 'Safety helmet', 'Safety shoes', 'Safety gloves', 'B', '안전모 (anjeonmo) means "safety helmet".', 5),

-- Quiz 2: Workplace Safety
(2, 'multiple_choice', 'What does 위험 mean?', 'Safety', 'Caution', 'Danger', 'Exit', 'C', '위험 (wiheom) means "danger".', 1),
(2, 'multiple_choice', 'What is 비상구?', 'Main entrance', 'Emergency exit', 'Office door', 'Storage room', 'B', '비상구 (bisanggu) means "emergency exit".', 2),
(2, 'multiple_choice', 'What is 소화기 used for?', 'Cooking', 'Measuring', 'Putting out fires', 'Cleaning', 'C', '소화기 (sohwagi) is a fire extinguisher.', 3),
(2, 'fill_blank', 'Complete: ___를 꼭 쓰세요. (Wear your safety helmet.)', '안전모', '소화기', '비상구', '주의', 'A', 'The complete sentence is "안전모를 꼭 쓰세요" (Make sure to wear your safety helmet).', 4),
(2, 'multiple_choice', '"주의" on a sign means:', 'Stop', 'Go', 'Caution', 'Welcome', 'C', '주의 (juui) means "caution".', 5);

-- ============================================================
-- SAMPLE MOCK EXAM
-- ============================================================

INSERT INTO mock_exams (title, description, time_limit_minutes, listening_count, reading_count, total_score, passing_score) VALUES 
('EPS-TOPIK Practice Exam 1', 'Full practice exam simulating the actual EPS-TOPIK test format with listening and reading sections.', 70, 5, 5, 40, 16);

-- Mock Exam Questions - Listening Section
INSERT INTO mock_exam_questions (exam_id, section, question_number, audio_path, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, points) VALUES 
(1, 'listening', 1, 'audio/exam/listen1.mp3', 'What is the woman looking for?', 'The office', 'The cafeteria', 'The restroom', 'The parking lot', 'C', 'The woman asks "화장실이 어디에요?" (Where is the restroom?)', 4),
(1, 'listening', 2, 'audio/exam/listen2.mp3', 'What time does work start?', '7:00 AM', '8:00 AM', '9:00 AM', '10:00 AM', 'B', 'The speaker says "오전 8시에 출근합니다" (I go to work at 8 AM)', 4),
(1, 'listening', 3, 'audio/exam/listen3.mp3', 'Why is the man calling?', 'To report sick', 'To ask for directions', 'To order food', 'To schedule a meeting', 'A', 'The man says "아파서 오늘 못 갑니다" (I''m sick so I can''t come today)', 4),
(1, 'listening', 4, 'audio/exam/listen4.mp3', 'Where should employees gather during the drill?', 'In the office', 'In the parking lot', 'At the front gate', 'In the cafeteria', 'B', 'The announcement says "주차장으로 모여 주세요" (Please gather in the parking lot)', 4),
(1, 'listening', 5, 'audio/exam/listen5.mp3', 'What does the supervisor ask the worker to bring?', 'A report', 'Safety equipment', 'Lunch boxes', 'Tools', 'D', 'The supervisor says "공구를 가져와 주세요" (Please bring the tools)', 4);

-- Mock Exam Questions - Reading Section
INSERT INTO mock_exam_questions (exam_id, section, question_number, passage_text, question_text, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, points) VALUES 
(1, 'reading', 6, '공지사항\n내일(3월 15일) 오후 2시에 안전 교육이 있습니다.\n모든 직원은 반드시 참석하세요.\n장소: 2층 회의실', 'When is the safety training?', 'March 14, 2 PM', 'March 15, 2 PM', 'March 15, 3 PM', 'March 16, 2 PM', 'B', 'The notice says "내일(3월 15일) 오후 2시" (Tomorrow, March 15, 2 PM)', 4),
(1, 'reading', 7, '식당 이용 안내\n점심시간: 12:00 ~ 13:00\n저녁시간: 18:00 ~ 19:00\n※ 음식물은 밖으로 가져갈 수 없습니다.', 'What is NOT allowed at the cafeteria?', 'Eating during lunch hours', 'Taking food outside', 'Having dinner', 'Using the facility', 'B', 'The notice states: 음식물은 밖으로 가져갈 수 없습니다 (Food cannot be taken outside)', 4),
(1, 'reading', 8, '김민수 씨는 자동차 공장에서 일합니다. 매일 아침 8시에 출근해서 오후 5시에 퇴근합니다. 점심은 공장 식당에서 먹습니다.', 'Where does Mr. Kim eat lunch?', 'At home', 'At a restaurant', 'At the factory cafeteria', 'He skips lunch', 'C', '점심은 공장 식당에서 먹습니다 means "He eats lunch at the factory cafeteria."', 4),
(1, 'reading', 9, '⚠️ 이 구역은 관계자 외 출입금지입니다.\n안전장비를 착용하지 않으면 들어갈 수 없습니다.', 'Who can enter this area?', 'Anyone with permission', 'Only authorized personnel with safety equipment', 'Visitors with a guide', 'All employees', 'B', 'The sign says only authorized persons (관계자) can enter, and safety equipment (안전장비) must be worn.', 4),
(1, 'reading', 10, '한국어 수업 안내\n일시: 매주 토요일 오전 10시~12시\n장소: 다문화센터 3층\n대상: 외국인 근로자\n비용: 무료', 'How much does the Korean class cost?', '10,000 won', '20,000 won', '50,000 won', 'Free', 'D', '비용: 무료 means "Cost: Free"', 4);
