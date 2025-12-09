<?php
session_start();
require 'db_connect.php';

// Check if book ID is provided
if (!isset($_GET['id'])) {
    header("Location: books.php");
    exit();
}

$book_id = intval($_GET['id']);

// Get book details
$stmt = $conn->prepare("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.category_id WHERE b.book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: books.php");
    exit();
}

$book = $result->fetch_assoc();

// Check if there's an alert message from a previous request
$alert_message = '';
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

// Handle book request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_book'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['alert'] = "Please login to request this book";
        header("Location: login.php");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // 🔴 Check if user already has ANY active borrow request (pending or accepted)
    $check_stmt = $conn->prepare("SELECT * FROM book_requests WHERE user_id = ? AND status IN ('pending', 'accepted')");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // User already borrowed another book
        $_SESSION['alert'] = 'You must return your currently borrowed book before requesting a new one.';
        header("Location: book_details.php?id=" . $book_id);
        exit();
    } 
    // Otherwise allow borrowing this book
    else if ($book['available_copies'] > 0) {
        $stmt = $conn->prepare("INSERT INTO book_requests (user_id, book_id, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $user_id, $book_id);
        
        if ($stmt->execute()) {
            $_SESSION['alert'] = 'Your request to borrow this book has been submitted! Waiting for librarian approval.';
            header("Location: book_details.php?id=" . $book_id);
            exit();
        } else {
            $_SESSION['alert'] = 'Error submitting request. Please try again.';
            header("Location: book_details.php?id=" . $book_id);
            exit();
        }
    } else {
        $_SESSION['alert'] = 'This book is currently not available.';
        header("Location: book_details.php?id=" . $book_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($book['title']); ?> - Book Details</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="book-detail-container">
    <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>

    <div class="book-detail-card">
      <div class="book-image">
        <?php if (!empty($book['image_path'])): ?>
          <img src="<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
        <?php else: ?>
          <img src="images/bookshelf.jpeg" alt="Default book image">
        <?php endif; ?>
      </div>

      <div class="book-info">
        <h3>Author:</h3>
        <p><?php echo htmlspecialchars($book['author']); ?></p>

        <h3>Category:</h3>
        <p><?php echo htmlspecialchars($book['category_name']); ?></p>

        <h3>Published Year:</h3>
        <p><?php echo htmlspecialchars($book['published_year']); ?></p>

        <h3>ISBN:</h3>
        <p><?php echo htmlspecialchars($book['isbn']); ?></p>

        <h3>Available Copies:</h3>
        <p><?php echo htmlspecialchars($book['available_copies']); ?></p>

        <h3>Summary:</h3>
        <p><?php echo htmlspecialchars($book['description']); ?></p>

        <?php if (isset($_SESSION['user_id'])): ?>
          <form method="POST">
            <button type="submit" name="request_book" class="request-btn" 
                    <?php echo ($book['available_copies'] <= 0) ? 'disabled' : ''; ?>>
              <?php echo ($book['available_copies'] > 0) ? 'Request to Borrow Book' : 'Not Available'; ?>
            </button>
          </form>
        <?php else: ?>
          <p style="color:red; margin-top: 15px;">Please <a href="login.php">login</a> to request this book</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="back-btn-container">
      <a href="books.php" class="back-btn">Back to Books</a>
    </div>
  </div>
  <?php include 'footer.php'; ?>

  <?php if (!empty($alert_message)): ?>
  <script>
    // Show alert after page loads
    window.onload = function() {
        alert("localhost says\n\n<?php echo $alert_message; ?>");
    };
  </script>
  <?php endif; ?>
</body>
</html>