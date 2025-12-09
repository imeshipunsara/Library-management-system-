<?php
session_start();
require 'db_connect.php';

// Only allow if logged in as librarian/admin (optional check)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'librarian') {
    // comment this line if you want open access
    // header("Location: login.php");
    // exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $password = $_POST['password'];

    // hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO librarians (username, email, password, name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $name);

    if ($stmt->execute()) {
        $message = "✅ New librarian added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Librarian</title>
  <link rel="stylesheet" href="css/auth.css">
  <style>
    body {
      background: #f1f5f2;
      font-family: Arial, sans-serif;
    }
    .form-container {
      max-width: 400px;
      margin: 60px auto;
      background: #fff;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    h2 {
      margin-bottom: 20px;
      color: #2e4d37;
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-size: 14px;
    }
    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #3d5e4a;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    button:hover {
      background: #538768;
    }
    .msg {
      margin-bottom: 15px;
      color: darkred;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Add New Librarian</h2>
    <?php if (!empty($message)) echo "<p class='msg'>$message</p>"; ?>
    <form method="POST">
      <label>Username</label>
      <input type="text" name="username" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Full Name</label>
      <input type="text" name="name" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <button type="submit">Add Librarian</button>
    </form>
  </div>
</body>
</html>
