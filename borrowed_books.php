<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's borrowed books
$borrowed_books = [];
$result = $conn->query("
    SELECT br.*, b.title, b.author, b.image_path 
    FROM book_requests br 
    JOIN books b ON br.book_id = b.book_id 
    WHERE br.user_id = $user_id AND br.status IN ('accepted', 'returned')
    ORDER BY br.request_date DESC
");
if ($result) {
    $borrowed_books = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Borrowed Books</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
  <h2>My Borrowed Books</h2>

  <table id="borrowedBooksTable">
    <thead>
      <tr>
        <th>Book</th>
        <th>Borrow Date</th>
        <th>Due Date</th>
        <th>Return Date</th>
        <th>Status</th>
        <th>Penalty</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($borrowed_books)): ?>
        <tr>
          <td colspan="6" style="text-align: center;">You haven't borrowed any books yet.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($borrowed_books as $book): 
    $status = '';
    $status_class = '';
    $penalty_display = 0;

    if ($book['status'] == 'accepted') {
        if (strtotime($book['return_date']) < time()) {
            $status = 'Overdue';
            $status_class = 'status-not-returned';
            // calculate live penalty
            $days_late = floor((time() - strtotime($book['return_date'])) / (60 * 60 * 24));
            $penalty_display = $days_late * 50;
        } else {
            $status = 'Borrowed';
            $status_class = 'status-not-returned';
        }
    } else if ($book['status'] == 'returned') {
        $status = 'Returned';
        $status_class = 'status-returned';
        $penalty_display = $book['penalty']; // use stored penalty
        if ($book['penalty'] > 0) {
            $status .= ' (Late)';
        }
    }
?>
<tr>
  <td>
    <div style="display: flex; align-items: center;">
      <?php if (!empty($book['image_path'])): ?>
        <img src="<?php echo htmlspecialchars($book['image_path']); ?>" alt="Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
      <?php else: ?>
        <img src="images/bookshelf.jpeg" alt="Default Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
      <?php endif; ?>
      <div>
        <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
        by <?php echo htmlspecialchars($book['author']); ?>
      </div>
    </div>
  </td>
  <td><?php echo date('M j, Y', strtotime($book['issue_date'])); ?></td>
  <td><?php echo date('M j, Y', strtotime($book['return_date'])); ?></td>
  <td>
    <?php if ($book['actual_return_date']): ?>
      <?php echo date('M j, Y', strtotime($book['actual_return_date'])); ?>
    <?php else: ?>
      -
    <?php endif; ?>
  </td>
  <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
  <td class="penalty">Rs. <?php echo number_format($penalty_display, 2); ?></td>
</tr>
<?php endforeach; ?>

      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
</body>
</html>