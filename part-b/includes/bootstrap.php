<?php
// includes/bootstrap.php

require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

// --------------------
// Helpers
// --------------------
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_to($path) {
    header('Location: ' . $path);
    exit;
}

function set_flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// --------------------
// Database
// --------------------
function db() {
    static $mysqli = null;
    if ($mysqli instanceof mysqli) {
        return $mysqli;
    }

    // Throw exceptions on errors (PHP 7.4 compatible)
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    } catch (Throwable $e) {
        http_response_code(500);
        echo '<h1>Σφάλμα σύνδεσης με τη βάση δεδομένων</h1>';
        echo '<p>Έλεγξε τα στοιχεία MySQL (DB_HOST/DB_NAME/DB_USER/DB_PASS) και ότι έχεις κάνει import το SQL.</p>';

        echo '<p>Δες τις οδηγίες ρύθμισης στο README του project.</p>';
        error_log('Database connection failed: ' . $e->getMessage());
        exit;
    }
}

// --------------------
// Auth
// --------------------
function is_logged_in() {
    return !empty($_SESSION['user']);
}

function current_user() {
    return is_logged_in() ? $_SESSION['user'] : null;
}

function require_login() {
    if (!is_logged_in()) {
        redirect_to('login.php');
    }
    // Recheck roles and deleted accounts instead of trusting stale session data.
    static $checked = false;
    if (!$checked) {
        $id = (int) $_SESSION['user']['id'];
        $stmt = db()->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$user) {
            logout_user();
            redirect_to('login.php');
        }
        $_SESSION['user'] = $user;
        $checked = true;
    }
}

function is_tutor() {
    return is_logged_in() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'tutor';
}

function require_tutor() {
    require_login();
    if (!is_tutor()) {
        http_response_code(403);
        echo '<h1>403 - Απαγορεύεται</h1><p>Η σελίδα είναι διαθέσιμη μόνο σε Tutor.</p>';
        exit;
    }
}

function login_user($userRow) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$userRow['id'],
        'email' => $userRow['email'],
        'first_name' => $userRow['first_name'],
        'last_name' => $userRow['last_name'],
        'role' => $userRow['role'],
    ];
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

// --------------------
// Files
// --------------------
function safe_filename($name) {
    $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    return $name ?: 'file';
}

function save_upload($fileField, $targetDir) {
    if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Δεν επιλέχθηκε αρχείο ή υπήρξε σφάλμα upload.'];
    }
    $original = $_FILES[$fileField]['name'];
    $tmp = $_FILES[$fileField]['tmp_name'];
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'doc', 'docx', 'txt', 'odt', 'ppt', 'pptx'], true)) {
        return [null, 'Επιτρέπονται μόνο PDF, DOC, DOCX, TXT, ODT, PPT και PPTX.'];
    }
    if ((int) $_FILES[$fileField]['size'] > 10 * 1024 * 1024) {
        return [null, 'Το αρχείο πρέπει να είναι έως 10 MB.'];
    }
    $safe = safe_filename($original);
    $unique = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safe;
    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $unique;

    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0775, true);
    }

    if (!move_uploaded_file($tmp, $dest)) {
        return [null, 'Αποτυχία αποθήκευσης αρχείου.'];
    }

    // Return relative path from project root
    $rel = 'uploads/' . basename($targetDir) . '/' . $unique;
    // basename($targetDir) gives 'documents' or 'homeworks' because targetDir ends with that folder
    return [$rel, null];
}

function site_title($pageTitle) {
    return SITE_TITLE_PREFIX . ' - ' . $pageTitle;
}

// All POST forms, including login and logout, carry a session-specific token.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function require_post() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed');
    }
}

function delete_form_id($action, $id) {
    return 'delete-' . str_replace('.', '-', $action) . '-' . (int) $id;
}

function delete_form($action, $id) {
    return '<form id="' . h(delete_form_id($action, $id)) . '" method="post" action="' . h($action) . '">'
        . csrf_field() . '<input type="hidden" name="id" value="' . (int) $id . '"></form>';
}

function delete_button($action, $id) {
    return '<button type="submit" class="inline-delete" form="' . h(delete_form_id($action, $id))
        . '" onclick="return confirm(\'Να γίνει η διαγραφή;\');">διαγραφή</button>';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $provided = $_POST['csrf_token'] ?? '';
    if (!is_string($provided) || !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $provided)) {
        http_response_code(403);
        exit('Invalid request token. Reload the form and try again.');
    }
}

