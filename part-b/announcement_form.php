<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_tutor();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$ann = [
  'ann_date' => date('Y-m-d'),
  'subject' => '',
  'body' => '',
  'is_homework' => 0,
  'homework_id' => null,
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT id, ann_date, subject, body, is_homework, homework_id FROM announcements WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        set_flash('err', 'Η ανακοίνωση δεν βρέθηκε.');
        redirect_to('announcements.php');
    }
    $ann = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ann_date = trim($_POST['ann_date'] ?? '');
    $subject  = trim($_POST['subject'] ?? '');
    $body     = trim($_POST['body'] ?? '');
    $is_homework = isset($_POST['is_homework']) ? 1 : 0;
    $homework_id = trim($_POST['homework_id'] ?? '');
    $homework_id = ($homework_id === '') ? null : (int)$homework_id;

    if ($ann_date === '' || $subject === '' || $body === '') {
        $error = 'Συμπλήρωσε ημερομηνία, θέμα και κείμενο.';
    } else {
        if ($isEdit) {
            if ($homework_id === null) {
                $stmt = db()->prepare('UPDATE announcements SET ann_date=?, subject=?, body=?, is_homework=?, homework_id=NULL WHERE id=?');
                $stmt->bind_param('sssii', $ann_date, $subject, $body, $is_homework, $id);
            } else {
                $stmt = db()->prepare('UPDATE announcements SET ann_date=?, subject=?, body=?, is_homework=?, homework_id=? WHERE id=?');
                $stmt->bind_param('sssiii', $ann_date, $subject, $body, $is_homework, $homework_id, $id);
            }
            $stmt->execute();
            $stmt->close();
            set_flash('ok', 'Η ανακοίνωση ενημερώθηκε.');
        } else {
            if ($homework_id === null) {
                $stmt = db()->prepare('INSERT INTO announcements (ann_date, subject, body, is_homework, homework_id) VALUES (?,?,?,? ,NULL)');
                $stmt->bind_param('sssi', $ann_date, $subject, $body, $is_homework);
            } else {
                $stmt = db()->prepare('INSERT INTO announcements (ann_date, subject, body, is_homework, homework_id) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssii', $ann_date, $subject, $body, $is_homework, $homework_id);
            }
            $stmt->execute();
            $stmt->close();
            set_flash('ok', 'Η ανακοίνωση προστέθηκε.');
        }
        redirect_to('announcements.php');
    }

    // repopulate
    $ann['ann_date'] = $ann_date;
    $ann['subject'] = $subject;
    $ann['body'] = $body;
    $ann['is_homework'] = $is_homework;
    $ann['homework_id'] = $homework_id;
}

$pageTitle = $isEdit ? 'Επεξεργασία ανακοίνωσης' : 'Προσθήκη ανακοίνωσης';
$showTopLink = false;
include __DIR__ . '/includes/header.php';
?>

<div class="card">
  <h2><?php echo h($pageTitle); ?></h2>
  <div class="h2-body">
    <?php if ($error): ?>
      <p><b style="color:red;">Σφάλμα:</b> <?php echo h($error); ?></p>
    <?php endif; ?>

    <form method="post">
      <?php echo csrf_field(); ?>
      <div class="form-row">
        <label for="ann_date">Ημερομηνία</label>
        <input type="text" id="ann_date" name="ann_date" value="<?php echo h($ann['ann_date']); ?>" placeholder="YYYY-MM-DD" required>
      </div>

      <div class="form-row">
        <label for="subject">Θέμα</label>
        <input type="text" id="subject" name="subject" value="<?php echo h($ann['subject']); ?>" required>
      </div>

      <div class="form-row">
        <label for="body">Κυρίως κείμενο</label>
        <textarea id="body" name="body" required><?php echo h($ann['body']); ?></textarea>
      </div>

      <div class="form-row">
        <label>
          <input type="checkbox" name="is_homework" <?php echo ((int)$ann['is_homework']===1) ? 'checked' : ''; ?>>
          Αφορά εργασία
        </label>
      </div>

      <div class="form-row">
        <label for="homework_id">Α/Α εργασίας (προαιρετικό)</label>
        <input type="text" id="homework_id" name="homework_id" value="<?php echo h($ann['homework_id']); ?>" placeholder="π.χ. 1">
      </div>

      <button type="submit">Αποθήκευση</button>
      <a class="inline-link" href="announcements.php" style="margin-left:10px;">Ακύρωση</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
