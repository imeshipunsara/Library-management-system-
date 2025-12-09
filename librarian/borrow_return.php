<?php
session_start();
require '../db_connect.php';

// Check if there's an alert message from a previous request
$alert_message = '';
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

// Handle accept request with return date
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_request'])) {
    $request_id = intval($_POST['request_id']);
    $return_date = $_POST['return_date'];
    
    // Update the request status and set return date
    $stmt = $conn->prepare("UPDATE book_requests SET status = 'accepted', issue_date = CURDATE(), return_date = ? WHERE request_id = ?");
    $stmt->bind_param("si", $return_date, $request_id);
    
    if ($stmt->execute()) {
        // Decrease available copies
        $book_id = intval($_POST['book_id']);
        $conn->query("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = $book_id");
        
        $_SESSION['alert'] = 'Book request accepted successfully!';
    } else {
        $_SESSION['alert'] = 'Error accepting request: ' . $conn->error;
    }
    
    header("Location: borrow_return.php");
    exit();
}

// Handle decline request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['decline_request'])) {
    $request_id = intval($_POST['request_id']);
    
    $stmt = $conn->prepare("UPDATE book_requests SET status = 'declined' WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    
    if ($stmt->execute()) {
        $_SESSION['alert'] = 'Book request declined!';
    } else {
        $_SESSION['alert'] = 'Error declining request: ' . $conn->error;
    }
    
    header("Location: borrow_return.php");
    exit();
}

// Inside mark_returned section

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_returned'])) {
    $request_id = intval($_POST['request_id']);
    $book_id = intval($_POST['book_id']);
    
    // Calculate penalty if any
    $penalty = 0;
    $request_info = $conn->query("SELECT return_date FROM book_requests WHERE request_id = $request_id")->fetch_assoc();
    $return_date = $request_info['return_date'];
    
    // Get today's date
    $today = date('Y-m-d');
    
    if (strtotime($today) > strtotime($return_date)) {
        $days_late = floor((strtotime($today) - strtotime($return_date)) / (60 * 60 * 24));
        $penalty = $days_late * 50; // Rs. 50 per day
    }
    
    // Update the request status and set actual return date and penalty
    $stmt = $conn->prepare("UPDATE book_requests 
                            SET status = 'returned', 
                                actual_return_date = CURDATE(), 
                                penalty = ? 
                            WHERE request_id = ?");
    $stmt->bind_param("ii", $penalty, $request_id);   // ✅ FIXED (was "di")
    
    if ($stmt->execute()) {
        // Increase available copies
        $conn->query("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = $book_id");
        
        $_SESSION['alert'] = 'Book marked as returned! Penalty applied: Rs. ' . number_format($penalty, 2);
    } else {
        $_SESSION['alert'] = 'Error marking book as returned: ' . $conn->error;
    }
    
    header("Location: borrow_return.php");
    exit();
}


// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Get pending requests
$pending_requests = [];
$pending_query = "
    SELECT br.*, u.name as user_name, u.grade, b.title, b.author, b.image_path, b.published_year 
    FROM book_requests br 
    JOIN users u ON br.user_id = u.user_id 
    JOIN books b ON br.book_id = b.book_id 
    WHERE br.status = 'pending'
";

if (!empty($search)) {
    $pending_query .= " AND u.name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$pending_query .= " ORDER BY br.request_date DESC";

$result = $conn->query($pending_query);
if ($result) {
    $pending_requests = $result->fetch_all(MYSQLI_ASSOC);
}

// Get borrowed books (accepted but not returned)
$borrowed_books = [];
$borrowed_query = "
    SELECT br.*, u.name as user_name, u.grade, b.title, b.author, b.image_path, b.published_year 
    FROM book_requests br 
    JOIN users u ON br.user_id = u.user_id 
    JOIN books b ON br.book_id = b.book_id 
    WHERE br.status = 'accepted'
";

if (!empty($search)) {
    $borrowed_query .= " AND u.name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$borrowed_query .= " ORDER BY br.issue_date DESC";

$result = $conn->query($borrowed_query);
if ($result) {
    $borrowed_books = $result->fetch_all(MYSQLI_ASSOC);
}

// Get returned books
$returned_books = [];
$returned_query = "
    SELECT br.*, u.name as user_name, u.grade, b.title, b.author, b.image_path, b.published_year 
    FROM book_requests br 
    JOIN users u ON br.user_id = u.user_id 
    JOIN books b ON br.book_id = b.book_id 
    WHERE br.status = 'returned'
";

if (!empty($search)) {
    $returned_query .= " AND u.name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

$returned_query .= " ORDER BY br.actual_return_date DESC";

$result = $conn->query($returned_query);
if ($result) {
    $returned_books = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Borrow & Return Books</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="lib.css">
  <style>
    /* Search Bar Styles */
    .search-container {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background-color: #ffffff;
      padding: 15px 20px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .search-form {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    
    .search-input {
      padding: 10px 15px;
      border: 1px solid #ccd1d1;
      border-radius: 6px;
      width: 300px;
      font-size: 16px;
    }
    
    .search-btn {
      padding: 10px 20px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
    }
    
    .search-btn:hover {
      background-color: #2980b9;
    }
    
    .clear-btn {
      padding: 10px 20px;
      background-color: #95a5a6;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
    }
    
    .clear-btn:hover {
      background-color: #7f8c8d;
    }
    
    .search-info {
      font-size: 16px;
      color: #7f8c8d;
    }
  </style>
</head>
<body>
<?php include 'librarian_header.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h2>Borrow & Return Books</h2>
  </div>

  <div class="container1">
    <!-- Search Bar -->
    <div class="search-container">
      <form method="GET" class="search-form">
        <input type="text" name="search" class="search-input" placeholder="Search by student name..." 
              value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="search-btn">Search</button>
        <?php if (!empty($search)): ?>
          <a href="borrow_return.php" class="clear-btn">Clear</a>
        <?php endif; ?>
      </form>
      <?php if (!empty($search)): ?>
        <div class="search-info">
          Showing results for: "<?= htmlspecialchars($search) ?>"
        </div>
      <?php endif; ?>
    </div>

    <!-- Pending Requests Section -->
    <div class="table-section">
      <h3>Pending Book Requests</h3>
      <?php if (empty($pending_requests)): ?>
        <p>No pending requests<?= !empty($search) ? ' matching your search' : '' ?>.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Grade</th>
              <th>Book</th>
              <th>Request Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending_requests as $request): ?>
            <tr>
              <td><?php echo htmlspecialchars($request['user_name']); ?></td>
              <td><?php echo htmlspecialchars($request['grade']); ?></td>
              <td>
                <div style="display: flex; align-items: center;">
                  <?php if (!empty($request['image_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($request['image_path']); ?>" alt="Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
                  <?php else: ?>
                    <img src="../images/bookshelf.jpeg" alt="Default Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
                  <?php endif; ?>
                  <div>
                    <strong><?php echo htmlspecialchars($request['title']); ?></strong><br>
                    by <?php echo htmlspecialchars($request['author']); ?><br>
                    (<?php echo htmlspecialchars($request['published_year']); ?>)
                  </div>
                </div>
              </td>
              <td><?php echo date('M j, Y', strtotime($request['request_date'])); ?></td>
              <td>
                <form method="POST" style="display: inline;">
                  <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                  <input type="hidden" name="book_id" value="<?php echo $request['book_id']; ?>">
                  <button type="button" class="btn accept-btn" onclick="showDateSection(<?php echo $request['request_id']; ?>)">Accept</button>
                  <button type="submit" name="decline_request" class="btn decline-btn">Decline</button>
                </form>
                
                <!-- Date Picker Section for this request -->
                <div class="date-section" id="date-section-<?php echo $request['request_id']; ?>" style="display: none; margin-top: 10px;">
                  <form method="POST">
                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                    <input type="hidden" name="book_id" value="<?php echo $request['book_id']; ?>">
                    <label for="return-date-<?php echo $request['request_id']; ?>">Choose Return Date:</label>
                    <input type="date" id="return-date-<?php echo $request['request_id']; ?>" name="return_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    <button type="submit" name="accept_request" class="btn">Confirm</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Borrowed Books Table -->
    <div class="table-section">
      <h3>Borrowed Books</h3>
      <?php if (empty($borrowed_books)): ?>
        <p>No books currently borrowed<?= !empty($search) ? ' by this student' : '' ?>.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Grade</th>
              <th>Book</th>
              <th>Issue Date</th>
              <th>Return Date</th>
              <th>Days Left</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($borrowed_books as $book): 
              $days_left = floor((strtotime($book['return_date']) - time()) / (60 * 60 * 24));
              $days_left = $days_left > 0 ? $days_left : 0;
            ?>
            <tr>
              <td><?php echo htmlspecialchars($book['user_name']); ?></td>
              <td><?php echo htmlspecialchars($book['grade']); ?></td>
              <td>
                <div style="display: flex; align-items: center;">
                  <?php if (!empty($book['image_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($book['image_path']); ?>" alt="Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
                  <?php else: ?>
                    <img src="../images/bookshelf.jpeg" alt="Default Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
                  <?php endif; ?>
                  <div>
                    <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                    by <?php echo htmlspecialchars($book['author']); ?>
                  </div>
                </div>
              </td>
              <td><?php echo date('M j, Y', strtotime($book['issue_date'])); ?></td>
              <td><?php echo date('M j, Y', strtotime($book['return_date'])); ?></td>
              <td><?php echo $days_left; ?> days</td>
              <td>
                <form method="POST">
                  <input type="hidden" name="request_id" value="<?php echo $book['request_id']; ?>">
                  <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                  <button type="submit" name="mark_returned" class="return-btn">Mark as Returned</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Returned Books Table -->
    <div class="table-section">
      <h3>Returned Books</h3>
      <?php if (empty($returned_books)): ?>
        <p>No books returned yet<?= !empty($search) ? ' by this student' : '' ?>.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Book</th>
              <th>Issue Date</th>
              <th>Return Date</th>
              <th>Actual Return</th>
              <th>Status</th>
              <th>Penalty</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($returned_books as $book): 
              $return_status = strtotime($book['actual_return_date']) <= strtotime($book['return_date']) ? 
                              '<span style="color:green;">Returned on time</span>' : 
                              '<span style="color:red;">Returned late</span>';
            ?>
            <tr>
              <td><?php echo htmlspecialchars($book['user_name']); ?></td>
              <td>
                <div style="display: flex; align-items: center;">
                  <?php if (!empty($book['image_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($book['image_path']); ?>" alt="Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
                  <?php else: ?>
                    <img src="../images/bookshelf.jpeg" alt="Default Book Image" style="width: 40px; height: 60px; margin-right: 10px; object-fit: cover;">
                  <?php endif; ?>
                  <div>
                    <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                    by <?php echo htmlspecialchars($book['author']); ?>
                  </div>
                </div>
              </td>
              <td><?php echo date('M j, Y', strtotime($book['issue_date'])); ?></td>
              <td><?php echo date('M j, Y', strtotime($book['return_date'])); ?></td>
              <td><?php echo date('M j, Y', strtotime($book['actual_return_date'])); ?></td>
              <td><?php echo $return_status; ?></td>
              <td class="penalty">Rs. <?php echo number_format($book['penalty'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if (!empty($alert_message)): ?>
<script>
  // Show alert after page loads
  window.onload = function() {
      alert("localhost says\n\n<?php echo $alert_message; ?>");
  };
</script>
<?php endif; ?>

<script>
function showDateSection(requestId) {
  // Hide all date sections first
  var allDateSections = document.querySelectorAll('[id^="date-section-"]');
  allDateSections.forEach(function(section) {
    section.style.display = 'none';
  });
  
  // Show the selected date section
  var dateSection = document.getElementById('date-section-' + requestId);
  dateSection.style.display = 'block';
}
</script>

</body>
</html>