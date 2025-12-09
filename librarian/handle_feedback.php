<?php
session_start();
require '../db_connect.php';

// Get all feedback with user details
$feedback_list = [];
$result = $conn->query("
    SELECT f.*, u.name as user_name, u.grade 
    FROM feedback f 
    JOIN users u ON f.user_id = u.user_id 
    ORDER BY f.submitted_at DESC
");
if ($result) {
    $feedback_list = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Handle Feedback</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="lib.css">
</head>
<body>
<?php include 'librarian_header.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h2>Handle Feedback</h2>
  </div>

  <div class="container3">
    <h2>📬 User Feedback</h2>

    <?php if (empty($feedback_list)): ?>
      <p style="text-align: center; padding: 20px;">No feedback submitted yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>User Name</th>
            <th>Grade</th>
            <th>Submitted On</th>
            <th>Feedback Message</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($feedback_list as $feedback): ?>
          <tr>
            <td data-label="User Name"><?php echo htmlspecialchars($feedback['user_name']); ?></td>
            <td data-label="Grade"><?php echo htmlspecialchars($feedback['grade']); ?></td>
            <td data-label="Submitted On"><?php echo date('Y-m-d H:i', strtotime($feedback['submitted_at'])); ?></td>
            <td data-label="Feedback Message" class="message"><?php echo htmlspecialchars($feedback['message']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

</body>
</html>