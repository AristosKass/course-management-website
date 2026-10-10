<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_tutor();
require_post();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id > 0) {
    $stmt = db()->prepare('DELETE FROM announcements WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    set_flash('ok', 'Η ανακοίνωση διαγράφηκε.');
}
redirect_to('announcements.php');
