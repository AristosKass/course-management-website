<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect_to('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Συμπλήρωσε login και password.';
    } else {
        $stmt = db()->prepare('SELECT id, first_name, last_name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            set_flash('ok', 'Επιτυχής πιστοποίηση.');
            redirect_to('index.php');
        } else {
            $error = 'Λάθος στοιχεία πρόσβασης.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h(site_title('Πιστοποίηση')); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div id="container">
  <div id="header">
    <h1>Πιστοποίηση</h1>
  </div>

  <div class="card" style="max-width:520px;margin:18px auto;">
    <p class="small-note">Δώσε το login (e-mail) και το password για να αποκτήσεις πρόσβαση στον ιστοχώρο.</p>

    <?php if ($error): ?>
      <div class="card" style="border-color: rgba(200,0,0,.4);">
        <b>Σφάλμα:</b> <?php echo h($error); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <?php echo csrf_field(); ?>
      <div class="form-row">
        <label for="email">Login</label>
        <input type="email" id="email" name="email" required placeholder="name@example.com" value="<?php echo h($_POST['email'] ?? ''); ?>">
      </div>
      <div class="form-row">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="password">
      </div>
      <button type="submit">Είσοδος</button>
    </form>
  </div>

  <div class="small-note" style="text-align:center;">
  </div>
</div>
</body>
</html>
