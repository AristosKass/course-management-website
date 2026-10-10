<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_tutor();
require_post();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$me = current_user();
if ($id > 0) {
    if ($me && (int)$me['id'] === $id) {
        set_flash('err', 'Δεν μπορείς να διαγράψεις τον εαυτό σου.');
    } else {
        $stmt = db()->prepare('DELETE FROM users WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        set_flash('ok', 'Ο χρήστης διαγράφηκε.');
    }
}
redirect_to('users.php');
