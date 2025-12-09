<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Librarian Dashboard</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="lib.css">
</head>
<body>
<?php 
include 'librarian_header.php'; 
require '/xampp/htdocs/LibrarySystem/db_connect.php';

// Get total number of books
$total_books_query = "SELECT COUNT(*) as total FROM books";
$total_books_result = $conn->query($total_books_query);
$total_books = $total_books_result->fetch_assoc()['total'];

// Get total active members (students)
$active_members_query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
$active_members_result = $conn->query($active_members_query);
$active_members = $active_members_result->fetch_assoc()['total'];

// Get books issued today
$today = date('Y-m-d');
$books_issued_today_query = "SELECT COUNT(*) as total FROM book_requests WHERE status = 'accepted' AND DATE(issue_date) = '$today'";
$books_issued_today_result = $conn->query($books_issued_today_query);
$books_issued_today = $books_issued_today_result->fetch_assoc()['total'];
?>
  <!-- Main content -->
  <div class="main-content" id="main">
    <div class="topbar">
      <h2>Welcome, Librarian</h2>
    </div>

    <div class="content cards-grid">
      <div class="card card-1">
        <div class="card-icon">📚</div>
        <div class="card-info">
          <h3>Total Books</h3>
          <p><?php echo $total_books; ?></p>
        </div>
      </div>

      <div class="card card-2">
        <div class="card-icon">👤</div>
        <div class="card-info">
          <h3>Active Members</h3>
          <p><?php echo $active_members; ?></p>
        </div>
      </div>

      <div class="card card-3">
        <div class="card-icon">📖</div>
        <div class="card-info">
          <h3>Books Issued Today</h3>
          <p><?php echo $books_issued_today; ?></p>
        </div>
      </div>
    </div>
  </div>

</body>
</html>