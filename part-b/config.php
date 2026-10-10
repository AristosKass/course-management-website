<?php
// Keep real credentials in config.local.php (ignored by Git) or environment variables.
if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

foreach ([
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'course_portal',
    'DB_USER' => 'course_app',
    'DB_PASS' => '',
    'CONTACT_EMAIL' => 'tutor@example.test',
    'MAIL_ENABLED' => '0',
] as $name => $default) {
    if (!defined($name)) {
        $value = getenv($name);
        define($name, $value === false ? $default : $value);
    }
}

define('SITE_CHARSET', 'utf-8');
define('SITE_TITLE_PREFIX', 'Εκπαιδευτικά Περιβάλλοντα Διαδικτύου');
define('UPLOADS_DOCS_DIR', __DIR__ . '/uploads/documents');
define('UPLOADS_HW_DIR', __DIR__ . '/uploads/homeworks');
