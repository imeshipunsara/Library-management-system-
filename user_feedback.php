<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        // Insert feedback into database
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        
        if ($stmt->execute()) {
            $_SESSION['alert'] = 'Feedback submitted successfully!';
        } else {
            $_SESSION['alert'] = 'Error submitting feedback. Please try again.';
        }
    } else {
        $_SESSION['alert'] = 'Please write your feedback before submitting.';
    }
    
    // Redirect to prevent form resubmission
    header("Location: user_feedback.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Feedback</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="feedback-container">
    <h2>We Value Your Feedback</h2>

    <form method="POST">
      <label for="message">Feedback / Suggestions</label>
      <textarea name="message" id="message" placeholder="Write your feedback here..." required></textarea>

      <button type="submit">Send Feedback</button>
    </form>
  </div>
  <?php include 'footer.php'; ?>

  <script>
  // Check if there's an alert to show
  <?php if (isset($_SESSION['alert'])): ?>
    window.onload = function() {
      alert("localhost says\n\n<?php echo $_SESSION['alert']; ?>");
    };
    <?php unset($_SESSION['alert']); ?>
  <?php endif; ?>
  </script>
</body>
</html>