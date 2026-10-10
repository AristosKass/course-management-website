<?php
// Copy to config.local.php and fill in your local database settings.
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'course_portal');
define('DB_USER', 'course_app');
define('DB_PASS', 'replace-with-your-local-database-password');

// Email is optional. Enable only with a configured mail server and real recipients.
define('CONTACT_EMAIL', 'tutor@example.test');
define('MAIL_ENABLED', false);
