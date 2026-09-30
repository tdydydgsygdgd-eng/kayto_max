<?php
session_start();
require "db.php";
if(isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true){
  header("Location: dashboard.php"); exit;
}
$error = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
  $username = $_POST["username"];
  $password = md5($_POST["password"]);
  $stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND password=?");
  $stmt->bind_param("ss", $username, $password);
  $stmt->execute();
  $result = $stmt->get_result();
  if($result->num_rows > 0){
    $_SESSION["logged_in"] = true;
    $_SESSION["username"] = $username;
    header("Location: dashboard.php"); exit;
  } else {
    $error = "Username atau password salah!";
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Kayto Drive</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <h1>Kayto<span> Drive</span></h1>
      <p>Masuk untuk mengakses penyimpanan Anda</p>
    </div>
    <?php if($error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn-primary">Masuk ke Akun</button>
    </form>
  </div>
</div>
</body>
</html>
