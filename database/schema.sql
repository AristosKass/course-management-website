-- Demo schema for a new, empty course_portal database.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ------------------------------------------------------
-- ΠΙΝΑΚΕΣ
-- ------------------------------------------------------

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(60) NOT NULL,
  last_name VARCHAR(60) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('tutor','student') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ann_date DATE NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  is_homework TINYINT(1) NOT NULL DEFAULT 0,
  homework_id INT NULL,
  INDEX idx_ann_date (ann_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  file_path VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE homeworks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  goals TEXT NOT NULL,
  spec_file_path VARCHAR(255) NOT NULL,
  deliverables TEXT NOT NULL,
  due_date VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_email VARCHAR(120) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------
-- DEMO ΔΕΔΟΜΕΝΑ
-- ------------------------------------------------------
INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES
('Demo', 'Tutor', 'tutor@example.test', '$2y$10$3OJHYmTw78WOxOc6HjApOeOu5r6UDNtFhSTuZ2bVgy8LMJFWS7zjO', 'tutor'),
('Demo', 'Student A', 'student.a@example.test', '$2y$10$zpulNtSl34UPpfrd33X3ROlc0wWLH/rMwIxJAGTNSy68oLKnAOIgu', 'student'),
('Demo', 'Student B', 'student.b@example.test', '$2y$10$hBH4bB/KUlHkW.M8DLMxMuRg1X8BW4gPjE4fr4LpZLB51i5KuBLUm', 'student');

INSERT INTO documents (title, description, file_path) VALUES
('HTML', 'Σύντομη εισαγωγή στη δομή μιας ιστοσελίδας με ετικέτες (tags) και παραδείγματα.', 'downloads/file1.doc'),
('CSS', 'Σύντομη εισαγωγή στη μορφοποίηση και στο layout (selectors, χρώματα, περιθώρια).', 'downloads/file2.doc');

INSERT INTO homeworks (goals, spec_file_path, deliverables, due_date) VALUES
('Δημιουργία στατικού ιστοχώρου με HTML/CSS\nΧρήση κοινού stylesheet\nΣύνδεση των σελίδων με υπερσυνδέσμους', 'downloads/ergasia1.doc',
'Γραπτή αναφορά σε Word\nΠαρουσίαση σε PowerPoint', '12/5/2009'),
('Μετατροπή σε δυναμικό ιστοχώρο με PHP και MySQL\nΠιστοποίηση χρηστών και ρόλοι Tutor/Student\nCRUD λειτουργίες για Tutor', 'downloads/ergasia2.doc',
'Κώδικας PHP/HTML/CSS\nΑρχείο βάσης δεδομένων .sql\nΜικρή αναφορά (Word)', '15/5/2009');

INSERT INTO announcements (ann_date, subject, body, is_homework, homework_id) VALUES
('2008-12-12', 'Έναρξη μαθημάτων', 'Τα μαθήματα αρχίζουν την Δευτέρα 17/12/2008.', 0, NULL),
('2008-12-15', 'Ανάρτηση εργασίας', 'Η 1η εργασία έχει ανακοινωθεί στην ιστοσελίδα «Εργασίες».', 1, 1);
