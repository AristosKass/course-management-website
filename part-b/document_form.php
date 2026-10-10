<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_tutor();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$doc = [
  'title' => '',
  'description' => '',
  'file_path' => '',
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT id, title, description, file_path FROM documents WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        set_flash('err', 'Το έγγραφο δεν βρέθηκε.');
        redirect_to('documents.php');
    }
    $doc = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || $description === '') {
        $error = 'Συμπλήρωσε τίτλο και περιγραφή.';
    } else {
        $file_path = $doc['file_path'];
        if (!empty($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            list($newPath, $err) = save_upload('file', UPLOADS_DOCS_DIR);
            if ($err) {
                $error = $err;
            } else {
                $file_path = $newPath;
            }
        } elseif (!$isEdit) {
            $error = 'Πρέπει να επιλέξεις αρχείο.';
        }

        if ($error === '') {
            if ($isEdit) {
                $stmt = db()->prepare('UPDATE documents SET title=?, description=?, file_path=? WHERE id=?');
                $stmt->bind_param('sssi', $title, $description, $file_path, $id);
                $stmt->execute();
                $stmt->close();
                set_flash('ok', 'Το έγγραφο ενημερώθηκε.');
            } else {
                $stmt = db()->prepare('INSERT INTO documents (title, description, file_path) VALUES (?,?,?)');
                $stmt->bind_param('sss', $title, $description, $file_path);
                $stmt->execute();
                $stmt->close();
                set_flash('ok', 'Το έγγραφο προστέθηκε.');
            }
            redirect_to('documents.php');
        }
    }

    $doc['title'] = $title;
    $doc['description'] = $description;
}

$pageTitle = $isEdit ? 'Επεξεργασία εγγράφου' : 'Προσθήκη εγγράφου';
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
        <label for="title">Τίτλος εγγράφου</label>
        <input type="text" id="title" name="title" value="<?php echo h($doc['title']); ?>" required>
      </div>

      <div class="form-row">
        <label for="description">Περιγραφή</label>
        <textarea id="description" name="description" required><?php echo h($doc['description']); ?></textarea>
      </div>

      <div class="form-row">
        <label for="file">Αρχείο (upload)</label>
        <input type="file" id="file" name="file" <?php echo $isEdit ? '' : 'required'; ?>>
        <?php if ($isEdit && !empty($doc['file_path'])): ?>
          <div class="small-note">Τρέχον αρχείο: <?php echo h($doc['file_path']); ?></div>
        <?php endif; ?>
      </div>

      <button type="submit">Αποθήκευση</button>
      <a class="inline-link" href="documents.php" style="margin-left:10px;">Ακύρωση</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
