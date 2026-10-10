<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_tutor();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$u = [
  'first_name' => '',
  'last_name' => '',
  'email' => '',
  'role' => 'student',
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if (!$row) {
        set_flash('err', 'Ο χρήστης δεν βρέθηκε.');
        redirect_to('users.php');
    }
    $u = $row;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = ($_POST['role'] ?? 'student') === 'tutor' ? 'tutor' : 'student';
    $password = (string)($_POST['password'] ?? '');

    $duplicate = db()->prepare('SELECT id FROM users WHERE email=? AND id<>?');
    $duplicate->bind_param('si', $email, $id);
    $duplicate->execute();
    $exists = $duplicate->get_result()->num_rows > 0;
    $duplicate->close();

    if ($first_name === '' || $last_name === '' || $email === '') {
        $error = 'Συμπλήρωσε όνομα, επώνυμο και e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120 ||
              strlen($first_name) > 60 || strlen($last_name) > 60) {
        $error = 'Δώσε έγκυρο e-mail και μικρότερα ονόματα.';
    } elseif ($isEdit && (int) current_user()['id'] === $id && $role !== 'tutor') {
        $error = 'Δεν μπορείς να αλλάξεις τον δικό σου ρόλο από αυτή τη φόρμα.';
    } elseif ($exists) {
        $error = 'Αυτό το e-mail χρησιμοποιείται ήδη.';
    } elseif (!$isEdit && $password === '') {
        $error = 'Στην προσθήκη, το password είναι υποχρεωτικό.';
    } else {
        if ($isEdit) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('UPDATE users SET first_name=?, last_name=?, email=?, role=?, password_hash=? WHERE id=?');
                $stmt->bind_param('sssssi', $first_name, $last_name, $email, $role, $hash, $id);
            } else {
                $stmt = db()->prepare('UPDATE users SET first_name=?, last_name=?, email=?, role=? WHERE id=?');
                $stmt->bind_param('ssssi', $first_name, $last_name, $email, $role, $id);
            }
            $stmt->execute();
            $stmt->close();
            set_flash('ok', 'Ο χρήστης ενημερώθηκε.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = db()->prepare('INSERT INTO users (first_name, last_name, email, role, password_hash) VALUES (?,?,?,?,?)');
            $stmt->bind_param('sssss', $first_name, $last_name, $email, $role, $hash);
            $stmt->execute();
            $stmt->close();
            set_flash('ok', 'Ο χρήστης προστέθηκε.');
        }
        redirect_to('users.php');
    }

    $u['first_name'] = $first_name;
    $u['last_name'] = $last_name;
    $u['email'] = $email;
    $u['role'] = $role;
}

$pageTitle = $isEdit ? 'Επεξεργασία χρήστη' : 'Προσθήκη χρήστη';
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
        <label for="first_name">Όνομα</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo h($u['first_name']); ?>" required>
      </div>

      <div class="form-row">
        <label for="last_name">Επώνυμο</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo h($u['last_name']); ?>" required>
      </div>

      <div class="form-row">
        <label for="email">Loginame (e-mail)</label>
        <input type="email" id="email" name="email" value="<?php echo h($u['email']); ?>" required>
      </div>

      <div class="form-row">
        <label for="role">Ρόλος</label>
        <select id="role" name="role" style="width:100%;padding:10px 12px;border:1px solid rgba(30,90,160,.25);border-radius:10px;">
          <option value="student" <?php echo ($u['role']==='student')?'selected':''; ?>>Student</option>
          <option value="tutor" <?php echo ($u['role']==='tutor')?'selected':''; ?>>Tutor</option>
        </select>
      </div>

      <div class="form-row">
        <label for="password">Password <?php echo $isEdit ? '(άφησέ το κενό για να μην αλλάξει)' : ''; ?></label>
        <input type="password" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?>>
      </div>

      <button type="submit">Αποθήκευση</button>
      <a class="inline-link" href="users.php" style="margin-left:10px;">Ακύρωση</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
