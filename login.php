<?php
session_start();
require 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['user']);
    $password = $_POST['pass'];

    // --- 1. Try USERS table ---
    $stmt = $conn->prepare("SELECT * FROM users WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = 'student';

            echo "<script>alert('Login Success (Student)'); window.location='index.php';</script>";
            exit();
        } else {
            $error = "Invalid password for student.";
        }
    } else {
        // --- 2. Try LIBRARIANS table ---
        $stmt2 = $conn->prepare("SELECT * FROM librarians WHERE username = ? LIMIT 1");
        $stmt2->bind_param("s", $username);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($result2 && $result2->num_rows === 1) {
            $lib = $result2->fetch_assoc();

            if (password_verify($password, $lib['password'])) {
                $_SESSION['librarian_id'] = $lib['librarian_id'];
                $_SESSION['name'] = $lib['name'];
                $_SESSION['role'] = 'librarian';

                echo "<script>alert('Login Success (Librarian)'); window.location='librarian/dashboard.php';</script>";
                exit();
            } else {
                $error = "Invalid password for librarian.";
            }
        } else {
            $error = "No librarian account found with that username.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sujatha Vidyalaya Online Library – Log In</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <div class="container">
    <header>
      <h1>Sujatha Vidyalaya Online Library</h1>
    </header>

    <div class="content">
      <div class="form-box">
        <h2>Log In</h2>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form action="" method="post">
          <label for="login-user">Username</label>
          <input type="text" id="login-user" name="user" required>

          <label for="login-pass">Password</label>
          <input type="password" id="login-pass" name="pass" required>

          <button type="submit" class="btn-primary">Log In</button>
        </form>
      </div>
      <div class="illustration">
        <img src="images/login.jpg" alt="Stack of Books">
      </div>
    </div>
  </div>
</body>
</html>
