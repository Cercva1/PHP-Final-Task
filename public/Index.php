<?php 

session_start();

require_once("../config/db.php");
require_once("../classes/User.php");
require_once("../classes/PasswordGenerator.php");
require_once("../classes/PasswordStorage.php");

$db = (new Database())->getConnection();


$error = '';
$isLoggedIn = isset($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['register'])) {
        $user = new User($db);
        $user->username = $_POST['reg_username'];
        $user->password = $_POST['reg_password'];
        
        if ($user->register()) {
            // Registration success, redirect or show message
            $error = "Registration successful! Please login.";
        } else {
            $error = "Registration failed. Username may be taken.";
        }
    }

    if (isset($_POST['login'])) {
        $user = new User($db);
        $user->username = $_POST['username'];
        $user->password = $_POST['password'];
        
        if ($user->login()) {
            // Decrypt AES key for session usage
            list($encrypted_key, $iv_encoded) = explode(':', $user->aes_key_encrypted);
            $iv = base64_decode($iv_encoded);
            $aes_key = openssl_decrypt($encrypted_key, 'aes-256-cbc', $user->password, 0, $iv);
            
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['aes_key'] = $aes_key;
            $isLoggedIn = true;
        } else {
            $error = "Invalid login credentials.";
        }
    }

    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    if (isset($_POST['save_password']) && $isLoggedIn) {
        $service = $_POST['service_name'] ?? '';
        $passwordToSave = $_POST['saved_password'] ?? '';
        $user_id = $_SESSION['user_id'];
        $aes_key = $_SESSION['aes_key'];

        if ($service && $passwordToSave && $aes_key) {
            $storage = new PasswordStorage($db);
            if ($storage->save($user_id, $service, $passwordToSave, $aes_key)) {
                $error = "Password saved successfully!";
            } else {
                $error = "Failed to save password.";
            }
        } else {
            $error = "Invalid input or session expired.";
        }
    }

}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Manager - Demo </title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: auto; padding: 20px; }
    form { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; }
    input[type=text], input[type=password], input[type=number] {
    width: 100%; padding: 8px; margin: 8px 0;
    }
    button { padding: 10px; width: 100%; }
    .flex { display: flex; gap: 20px; }
    .half { flex: 1; }
    h2 { margin-top: 0; }
    .error { color: red; }
    </style>
</head>

<body>

<h1>Password Manager</h1>

<?php if(!$isLoggedIn): ?>

<div class="flex">
    <form method="POST" action="" class="half">
      <h2>Register</h2>
      <label>Username:</label>
      <input type="text" name="reg_username" required />
      <label>Password:</label>
      <input type="password" name="reg_password" required />
      <button name="register" type="submit">Register</button>
    </form>

    <form method="POST" action="" class="half">
      <h2>Login</h2>
      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <label>Username:</label>
      <input type="text" name="username" required />
      <label>Password:</label>
      <input type="password" name="password" required />
      <button name="login" type="submit">Login</button>
    </form>
  </div>

  <?php else: ?>

    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
  <form method="POST" action="">
    <button name="logout" type="submit">Logout</button>
  </form>

   <h3>Password Generator</h3>
  <form method="POST" action="">
    <label>Password length:</label>
    <input type="number" name="length" value="9" min="4" max="50" />
    <label>Uppercase letters:</label>
    <input type="number" name="num_upper" value="3" min="0" max="50" />
    <label>Lowercase letters:</label>
    <input type="number" name="num_lower" value="2" min="0" max="50" />
    <label>Numbers:</label>
    <input type="number" name="num_digits" value="2" min="0" max="50" />
    <label>Special characters:</label>
    <input type="number" name="num_special" value="2" min="0" max="50" />
    <button name="generate" type="submit">Generate Password</button>
  </form>

   <?php
  if (isset($_POST['generate'])) {
      $generator = new PasswordGenerator();

      // Set parameters safely
      $generator->length = max(4, min(50, (int)$_POST['length']));
      $generator->numUpper = max(0, (int)$_POST['num_upper']);
      $generator->numLower = max(0, (int)$_POST['num_lower']);
      $generator->numDigits = max(0, (int)$_POST['num_digits']);
      $generator->numSpecial = max(0, (int)$_POST['num_special']);

      // Adjust length if sums exceed length
      $totalRequested = $generator->numUpper + $generator->numLower + $generator->numDigits + $generator->numSpecial;
      if ($totalRequested > $generator->length) {
          echo "<p style='color:red;'>Error: The sum of character types exceeds total length!</p>";
      } else {
          $password = $generator->generate();
          echo "<p><strong>Generated password:</strong> <code>" . htmlspecialchars($password) . "</code></p>";
      }
  }
  ?>

   <h3>Saved Passwords (demo empty)</h3>
    <form method="POST" action="">
    <label>Service Name:</label>
    <input type="text" name="service_name" required />
    <label>Password:</label>
    <input type="text" name="saved_password" required />
    <button name="save_password" type="submit">Save Password</button>
</form>

<h3>Saved Passwords</h3>
<?php
$storage = new PasswordStorage($db);
$passwords = $storage->fetchAll($_SESSION['user_id']);
if (count($passwords) === 0) {
    echo "<p>No saved passwords yet.</p>";
} else {
    echo "<ul>";
    foreach ($passwords as $entry) {
        list($encrypted_pass, $iv_b64) = explode(':', $entry['password_encrypted']);
        $iv = base64_decode($iv_b64);
        $decrypted = openssl_decrypt($encrypted_pass, 'aes-256-cbc', $_SESSION['aes_key'], 0, $iv);
        echo "<li><strong>" . htmlspecialchars($entry['service_name']) . ":</strong> <code>" . htmlspecialchars($decrypted) . "</code></li>";
    }
    echo "</ul>";
}
?>

<?php endif; ?>
    
</body>
</html>