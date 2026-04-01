# EPS Korean Trainer

A comprehensive web application for EPS-TOPIK (Employment Permit System - Test of Proficiency in Korean) exam preparation. Built with PHP, MySQL, Tailwind CSS, and Chart.js.

## Features

### Learner Side
- **Dashboard** — Stats overview, streak tracking, weekly activity chart, quick actions
- **Lessons** — Browse by category/difficulty, mark complete, bookmark, prev/next navigation
- **Vocabulary** — Card view, list view, flashcard mode; filter by category/difficulty/status; AJAX status updates
- **Listening Practice** — Audio playback, answer submission, progress tracking, mistake logging
- **Reading Practice** — Passages with comprehension questions, result review with explanations
- **Quizzes** — Timed quizzes, scoring, detailed result review, mistake logging
- **Mock Exams** — Full EPS-TOPIK simulation with listening + reading sections, timer, question navigation
- **Daily Goals** — Configurable daily targets with progress tracking
- **Review Mistakes** — Filter and review past mistakes by module
- **Progress** — Charts, accuracy stats, mastery breakdown, achievements
- **Profile & Settings** — Update profile, change password, study preferences

### Admin Side
- **Dashboard** — User stats, content counts, signup/quiz charts, recent activity
- **User Management** — Search, filter, toggle status, delete users
- **Lesson Management** — Full CRUD with HTML content editor
- **Vocabulary Management** — Full CRUD with audio upload support
- **Listening Management** — CRUD for audio-based questions
- **Reading Management** — Passages + questions with inline add/delete
- **Quiz Management** — Quiz creation with question builder
- **Mock Exam Management** — Exam creation with listening/reading sections
- **Category Management** — Shared categories across all modules
- **Reports & Analytics** — User growth, score distribution, activity charts, top learners

## Tech Stack

- **Backend:** PHP 7.4+ with PDO (prepared statements)
- **Database:** MySQL 5.7+ / MariaDB 10.3+
- **Frontend:** Tailwind CSS (CDN), Chart.js, vanilla JavaScript
- **Fonts:** Inter + Noto Sans KR (Google Fonts)

## Requirements

- XAMPP / WAMP / LAMP with PHP 7.4+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled

## Installation

### 1. Clone / Copy Files
Place the project folder in your web server's document root:
```
htdocs/EPS-TOPIK/
```

### 2. Create the Database
Import the schema file into MySQL:
```sql
source Z:/xampp/htdocs/EPS-TOPIK/database/schema.sql;
```
Or via phpMyAdmin: Import `database/schema.sql`

### 3. Configure Database Connection
Edit `config.php` if your database credentials differ from defaults:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'eps_topik');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Set Permissions
Ensure the `uploads/` directory is writable:
```
uploads/
├── audio/
│   ├── listening/
│   └── exam/
└── profiles/
```

### 5. Access the Application
- **App URL:** http://localhost/EPS-TOPIK
- **Admin login:** `admin@epstrainer.com` / `password`
- **Sample learner:** `juan@example.com` / `password`

## Project Structure

```
EPS-TOPIK/
├── admin/                  # Admin panel pages
│   ├── categories.php
│   ├── dashboard.php
│   ├── index.php
│   ├── lessons.php
│   ├── listening.php
│   ├── mock-exams.php
│   ├── quizzes.php
│   ├── reading.php
│   ├── reports.php
│   ├── users.php
│   └── vocabulary.php
├── database/
│   └── schema.sql          # Full schema + sample data
├── includes/
│   ├── admin-check.php     # Admin auth guard
│   ├── admin-footer.php    # Admin layout footer
│   ├── admin-header.php    # Admin layout header + sidebar
│   ├── auth-check.php      # Learner auth guard
│   ├── db.php              # PDO database connection
│   ├── footer.php          # Learner layout footer
│   ├── header.php          # Learner layout header + sidebar
│   ├── helpers.php         # Utility functions
│   └── session.php         # Session & CSRF management
├── uploads/
│   ├── audio/              # Audio files
│   └── profiles/           # Profile images
├── .htaccess               # Rewrite rules & security headers
├── config.php              # Application configuration
├── daily-goals.php         # Daily study goals
├── dashboard.php           # Learner dashboard
├── forgot-password.php     # Password recovery
├── index.php               # Entry point (redirects)
├── lesson-view.php         # Single lesson view
├── lessons.php             # Lesson listing
├── listening.php           # Listening practice
├── login.php               # Login page
├── logout.php              # Logout handler
├── mock-exam.php           # Mock exam simulation
├── profile.php             # Profile & settings
├── progress.php            # Progress & achievements
├── quizzes.php             # Quiz module
├── reading.php             # Reading practice
├── register.php            # User registration
├── reset-password.php      # Password reset
├── review-mistakes.php     # Mistake review
└── vocabulary.php          # Vocabulary module
```

## Security Features

- CSRF token protection on all forms
- PDO prepared statements (no SQL injection)
- Password hashing with `password_hash()` / `password_verify()`
- Session regeneration on login
- HTTP-only session cookies
- `.htaccess` protection for includes/database directories
- XSS prevention via `htmlspecialchars()` sanitization
- Role-based access control (learner/admin)

## Sample Data

The schema includes sample data for testing:
- 3 sample lessons (Hangul vowels, consonants, workplace greetings)
- 25 vocabulary words across multiple categories
- 5 listening questions
- 3 reading passages with 5 questions
- 3 quizzes with 10 questions total
- 1 mock exam with 10 questions (5 listening + 5 reading)
- 10 lesson categories, 10 vocabulary categories, 4 listening categories, 5 reading categories
