<?php 

session_start();

require_once 'classes/PasswordGenerator.php';

$isLoggedIn = isset($_SESSION['user_id']);
$error = '';

//Login for demo purposes
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['login'])){
        if($_POST['username'] == 'test' && $_POST['password'] == 'test'){
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'test';
            $isLoggedIn = true;
        }else{
            $error = "Invalid login";
        }
    
    }elseif (isset($_POST['logout'])){
        session_destroy();
        header("Location: index.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Manager - Demo </title>
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
    <p>No saved passwords yet.</p>

    <?php endif; ?>
    
</body>
</html>