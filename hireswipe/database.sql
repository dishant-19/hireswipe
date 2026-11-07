-- ============================================================
--  HireSwipe database  (MySQL 5.7+ / MariaDB 10.3+)
--  Import this file once in phpMyAdmin (Import tab) or run:
--      mysql -u root -p < database.sql
--  It is safe to import again: it never deletes existing data.
--  Demo accounts at the bottom all use the password:  password123
-- ============================================================

CREATE DATABASE IF NOT EXISTS hireswipe
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hireswipe;

-- ------------------------------------------------------------
-- 1. Users: everyone who signs up (job seeker or employer)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  mobile        VARCHAR(20)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('seeker','employer') NULL DEFAULT NULL,
  onboarded     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Job seeker profile (one row per job seeker)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS seeker_profiles (
  user_id          INT UNSIGNED NOT NULL,
  age              TINYINT UNSIGNED NOT NULL,
  location         VARCHAR(120) NOT NULL,
  nationality      VARCHAR(80)  NOT NULL,
  qualification    VARCHAR(150) NOT NULL,
  experience_years DECIMAL(3,1) NOT NULL DEFAULT 0,
  expected_salary  INT UNSIGNED NOT NULL DEFAULT 0,      -- per month
  employment_type  ENUM('full_time','part_time','contract','internship','freelance') NOT NULL,
  industries       VARCHAR(255) NOT NULL,                -- comma separated
  headline         VARCHAR(150) NULL,
  bio              TEXT NULL,
  photo            VARCHAR(255) NULL,
  cv               VARCHAR(255) NULL,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_seeker_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Employer / company profile with the job on offer
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS employer_profiles (
  user_id         INT UNSIGNED NOT NULL,
  company_name    VARCHAR(150) NOT NULL,
  location        VARCHAR(120) NOT NULL,
  industry        VARCHAR(80)  NOT NULL,
  position        VARCHAR(150) NOT NULL,
  employment_type ENUM('full_time','part_time','contract','internship','freelance') NOT NULL,
  salary          INT UNSIGNED NOT NULL DEFAULT 0,       -- offered, per month
  job_detail      TEXT NOT NULL,
  logo            VARCHAR(255) NULL,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_employer_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Swipes: every like / pass a person makes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS swipes (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  swiper_id  INT UNSIGNED NOT NULL,
  target_id  INT UNSIGNED NOT NULL,
  direction  ENUM('like','pass') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_swipe (swiper_id, target_id),
  KEY idx_swipe_target (target_id, direction),
  CONSTRAINT fk_swipe_swiper FOREIGN KEY (swiper_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_swipe_target FOREIGN KEY (target_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Matches: created when both sides like each other
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS matches (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  seeker_id   INT UNSIGNED NOT NULL,
  employer_id INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_match (seeker_id, employer_id),
  KEY idx_match_employer (employer_id),
  CONSTRAINT fk_match_seeker   FOREIGN KEY (seeker_id)   REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_match_employer FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. Messages: chat inside a match
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  match_id   INT UNSIGNED NOT NULL,
  sender_id  INT UNSIGNED NOT NULL,
  body       TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_msg_match (match_id, id),
  CONSTRAINT fk_msg_match  FOREIGN KEY (match_id)  REFERENCES matches(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. One-time codes for mobile (OTP) login
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS otp_codes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mobile     VARCHAR(20)  NOT NULL,
  code_hash  VARCHAR(255) NOT NULL,
  attempts   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  used       TINYINT(1) NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_otp_mobile (mobile, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. Forgot-password links
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  used       TINYINT(1) NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reset_token (token_hash),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  DEMO DATA  (password for every demo account: password123)
--  Employers  : employer1@demo.com ... employer6@demo.com
--  Job seekers: seeker1@demo.com   ... seeker6@demo.com
-- ============================================================
INSERT IGNORE INTO users (id, name, email, mobile, password_hash, role, onboarded) VALUES
(1,  'TechNova HR',      'employer1@demo.com', '9000000001', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'employer', 1),
(2,  'GreenLeaf HR',     'employer2@demo.com', '9000000002', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'employer', 1),
(3,  'BrightPath HR',    'employer3@demo.com', '9000000003', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'employer', 1),
(4,  'PixelCraft HR',    'employer4@demo.com', '9000000004', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'employer', 1),
(5,  'CareWell HR',      'employer5@demo.com', '9000000005', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'employer', 1),
(6,  'SwiftMove HR',     'employer6@demo.com', '9000000006', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'employer', 1),
(7,  'Aarav Patel',      'seeker1@demo.com',   '9000000007', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'seeker',   1),
(8,  'Diya Shah',        'seeker2@demo.com',   '9000000008', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'seeker',   1),
(9,  'Rohan Mehta',      'seeker3@demo.com',   '9000000009', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'seeker',   1),
(10, 'Isha Desai',       'seeker4@demo.com',   '9000000010', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'seeker',   1),
(11, 'Karan Joshi',      'seeker5@demo.com',   '9000000011', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'seeker',   1),
(12, 'Neha Verma',       'seeker6@demo.com',   '9000000012', '$2y$10$O95SjGku1IWKs67/WekkkO9RKtV5BzapIJXbfLzj1PdtCgtKa0JJ6', 'seeker',   1);

INSERT IGNORE INTO employer_profiles (user_id, company_name, location, industry, position, employment_type, salary, job_detail) VALUES
(1, 'TechNova Solutions', 'Ahmedabad, Gujarat', 'IT & Software',      'Full Stack Developer',     'full_time', 60000, 'Build and maintain web apps using PHP, MySQL and JavaScript. Friendly team, flexible hours, and a real chance to grow into a lead role.'),
(2, 'GreenLeaf Foods',    'Surat, Gujarat',     'Food & Beverage',    'Marketing Executive',      'full_time', 35000, 'Plan social media campaigns and local promotions for our organic snack brand. Creative ideas are very welcome.'),
(3, 'BrightPath Academy', 'Vadodara, Gujarat',  'Education',          'Maths Teacher',            'part_time', 25000, 'Teach maths to grades 8 to 10 in the evening batch. Patience and a love for teaching matter more than years of experience.'),
(4, 'PixelCraft Studio',  'Ahmedabad, Gujarat', 'Design & Creative',  'UI/UX Designer',           'contract',  50000, '6-month contract designing mobile and web screens for startup clients. Portfolio required.'),
(5, 'CareWell Hospital',  'Rajkot, Gujarat',    'Healthcare',         'Front Desk Coordinator',   'full_time', 28000, 'Welcome patients, manage appointments and keep the front desk running smoothly. Good communication is key.'),
(6, 'SwiftMove Logistics','Mumbai, Maharashtra','Logistics',          'Operations Associate',     'full_time', 32000, 'Track shipments, coordinate with drivers and keep customers updated. Fast-paced and friendly environment.');

INSERT IGNORE INTO seeker_profiles (user_id, age, location, nationality, qualification, experience_years, expected_salary, employment_type, industries, headline, bio) VALUES
(7,  24, 'Ahmedabad, Gujarat', 'Indian', 'B.Tech Computer Engineering', 2.0, 45000, 'full_time', 'IT & Software',                      'Junior PHP developer',        'I love turning ideas into working websites. Two years of PHP and MySQL, always learning something new.'),
(8,  26, 'Surat, Gujarat',     'Indian', 'MBA Marketing',               3.0, 38000, 'full_time', 'Food & Beverage,Marketing & Sales',   'Brand and social media marketer', 'Grew a local cafe''s Instagram from 2k to 20k followers. Enjoy storytelling and numbers.'),
(9,  22, 'Vadodara, Gujarat',  'Indian', 'B.Sc Mathematics',            1.0, 22000, 'part_time', 'Education',                           'Maths tutor',                 'Patient tutor who makes tricky topics feel simple. Evening availability.'),
(10, 27, 'Ahmedabad, Gujarat', 'Indian', 'B.Des Interaction Design',    4.0, 55000, 'contract',  'Design & Creative,IT & Software',     'Product designer',            'Four years designing apps people enjoy using. Figma, research and prototyping.'),
(11, 29, 'Rajkot, Gujarat',    'Indian', 'BBA',                         5.0, 30000, 'full_time', 'Healthcare,Hospitality',              'Customer care professional',  'Calm under pressure and great with people. Five years in front desk and guest care.'),
(12, 25, 'Mumbai, Maharashtra','Indian', 'B.Com',                       2.0, 30000, 'full_time', 'Logistics,Finance & Banking',         'Operations and reporting',    'Organised, quick with spreadsheets and good at keeping everyone updated.');

-- Swipes: two matches already exist, two companies are waiting for a like back
INSERT IGNORE INTO swipes (swiper_id, target_id, direction) VALUES
(7, 1, 'like'), (1, 7, 'like'),
(10, 4, 'like'), (4, 10, 'like'),
(2, 8, 'like'),   -- GreenLeaf likes Diya: swipe right on GreenLeaf as seeker2 to see a match
(3, 9, 'like'),   -- BrightPath likes Rohan
(6, 12, 'like');  -- SwiftMove likes Neha

INSERT IGNORE INTO matches (id, seeker_id, employer_id) VALUES
(1, 7, 1),
(2, 10, 4);

INSERT IGNORE INTO messages (id, match_id, sender_id, body, is_read) VALUES
(1, 1, 1, 'Hi Aarav! Your profile looks like a great fit for our Full Stack role. Are you free for a quick call this week?', 0),
(2, 1, 7, 'Hello! Thank you, I would love that. Thursday afternoon works well for me.', 1),
(3, 2, 10, 'Hi PixelCraft team, happy to share my portfolio whenever you are ready.', 1),
(4, 2, 4, 'Great to meet you Isha! Please send the link and we will review it today.', 0);

-- ------------------------------------------------------------
-- 9. Files kept in the database (used when the uploads folder is locked)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stored_files (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  owner_id   INT UNSIGNED NOT NULL,
  kind       ENUM('image','cv') NOT NULL,
  mime       VARCHAR(100) NOT NULL,
  filename   VARCHAR(190) NOT NULL,
  data       LONGBLOB NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_owner (owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
