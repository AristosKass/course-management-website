<?php
// Use with: php -S 127.0.0.1:8000 -t part-b part-b/router.php
// Only public pages and UI assets can be served directly.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$root = realpath(__DIR__);
$target = realpath(__DIR__ . '/' . ltrim($path, '/'));
$pages = [
    'index.php', 'login.php', 'logout.php', 'announcements.php',
    'announcement_form.php', 'announcement_delete.php', 'communication.php',
    'documents.php', 'document_form.php', 'document_delete.php',
    'homework.php', 'homework_form.php', 'homework_delete.php',
    'users.php', 'user_form.php', 'user_delete.php', 'download.php',
];

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}
if (!$target || strpos($target, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($target)) {
    http_response_code(404);
    exit('Not found');
}
$relative = substr($target, strlen($root) + 1);
if ($relative === 'style.css' ||
    (strpos($relative, 'assets/img/') === 0 && strtolower(pathinfo($relative, PATHINFO_EXTENSION)) === 'png')) {
    return false;
}
if (in_array($relative, $pages, true)) {
    require $target;
    return true;
}
http_response_code(403);
exit('Direct access is disabled. Use the application to download course files.');
