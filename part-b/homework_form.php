<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_tutor();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$hw = [
  'goals' => "",
  'deliverables' => "",
  'due_date' => "",
  'spec_file_path' => "",
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT id, goals, deliverables, due_date, spec_file_path FROM homeworks WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        set_flash('err', 'Η εργασία δεν βρέθηκε.');
        redirect_to('homework.php');
    }
    $hw = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goals = trim($_POST['goals'] ?? '');
    $deliverables = trim($_POST['deliverables'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');

    if ($goals === '' || $deliverables === '' || $due_date === '') {
        $error = 'Συμπλήρωσε στόχους, παραδοτέα και ημερομηνία παράδοσης.';
    } else {
        $spec_file_path = $hw['spec_file_path'];
        if (!empty($_FILES['spec_file']) && $_FILES['spec_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            list($newPath, $err) = save_upload('spec_file', UPLOADS_HW_DIR);
            if ($err) {
                $error = $err;
            } else {
                $spec_file_path = $newPath;
            }
        } elseif (!$isEdit) {
            $error = 'Πρέπει να επιλέξεις αρχείο εκφώνησης.';
        }

        if ($error === '') {
            if ($isEdit) {
                $stmt = db()->prepare('UPDATE homeworks SET goals=?, deliverables=?, due_date=?, spec_file_path=? WHERE id=?');
                $stmt->bind_param('ssssi', $goals, $deliverables, $due_date, $spec_file_path, $id);
                $stmt->execute();
                $stmt->close();
                set_flash('ok', 'Η εργασία ενημερώθηκε.');
            } else {
                $stmt = db()->prepare('INSERT INTO homeworks (goals, deliverables, due_date, spec_file_path) VALUES (?,?,?,?)');
                $stmt->bind_param('ssss', $goals, $deliverables, $due_date, $spec_file_path);
                $stmt->execute();
                $newId = $stmt->insert_id;
                $stmt->close();

                // Auto-create announcement for new homework
                $ann_date = date('Y-m-d');
                $subject = 'Υποβλήθηκε η εργασία ' . $newId;
                $body = 'Η ημερομηνία παράδοσης της εργασίας είναι ' . $due_date;
                $is_homework = 1;
                $stmt = db()->prepare('INSERT INTO announcements (ann_date, subject, body, is_homework, homework_id) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssii', $ann_date, $subject, $body, $is_homework, $newId);
                $stmt->execute();
                $stmt->close();

                set_flash('ok', 'Η εργασία προστέθηκε και δημιουργήθηκε αυτόματα ανακοίνωση.');
            }
            redirect_to('homework.php');
        }
    }

    $hw['goals'] = $goals;
    $hw['deliverables'] = $deliverables;
    $hw['due_date'] = $due_date;
}

$pageTitle = $isEdit ? 'Επεξεργασία εργασίας' : 'Προσθήκη εργασίας';
$showTopLink = false;
include __DIR__ . '/includes/header.php';
?>

<div class="card">
  <h2><?php echo h($pageTitle); ?></h2>
  <div class="h2-body">
    <?php if ($error): ?>
      <p><b style="color:red;">Σφάλμα:</b> <?php echo h($error); ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <div class="form-row">
        <label for="goals">Στόχοι (ένας ανά γραμμή)</label>
        <textarea id="goals" name="goals" required><?php echo h($hw['goals']); ?></textarea>
      </div>

      <div class="form-row">
        <label for="spec_file">Εκφώνηση (upload)</label>
        <input type="file" id="spec_file" name="spec_file" <?php echo $isEdit ? '' : 'required'; ?>>
        <?php if ($isEdit && !empty($hw['spec_file_path'])): ?>
          <div class="small-note">Τρέχον αρχείο: <?php echo h($hw['spec_file_path']); ?></div>
        <?php endif; ?>
      </div>

      <div class="form-row">
        <label for="deliverables">Παραδοτέα (ένα ανά γραμμή)</label>
        <textarea id="deliverables" name="deliverables" required><?php echo h($hw['deliverables']); ?></textarea>
      </div>

      <div class="form-row">
        <label for="due_date">Ημερομηνία παράδοσης</label>
        <input type="text" id="due_date" name="due_date" value="<?php echo h($hw['due_date']); ?>" placeholder="π.χ. 12/5/2009" required>
      </div>

      <button type="submit">Αποθήκευση</button>
      <a class="inline-link" href="homework.php" style="margin-left:10px;">Ακύρωση</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
