<?php
session_start();
require 'db_connect.php';

// Get new arrivals (books added in the last 30 days)
$new_arrivals = [];
$new_arrivals_query = "
    SELECT b.book_id, b.title, b.author, b.image_path 
    FROM books b 
    WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    ORDER BY b.created_at DESC 
    LIMIT 5
";
$new_arrivals_result = $conn->query($new_arrivals_query);
if ($new_arrivals_result) {
    $new_arrivals = $new_arrivals_result->fetch_all(MYSQLI_ASSOC);
}

// Get most popular books (most borrowed)
$most_popular = [];
$most_popular_query = "
    SELECT b.book_id, b.title, b.author, b.image_path, COUNT(br.request_id) as borrow_count
    FROM books b 
    LEFT JOIN book_requests br ON b.book_id = br.book_id 
    WHERE br.status IN ('accepted', 'returned')
    GROUP BY b.book_id 
    ORDER BY borrow_count DESC 
    LIMIT 5
";
$most_popular_result = $conn->query($most_popular_query);
if ($most_popular_result) {
    $most_popular = $most_popular_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Online Library System</title>
  <link rel="shortcut icon" href="logo.webp" type="image/x-icon">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include 'header.php'; ?>
  
  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <h1>Welcome to the Sujatha Vidyalaya Online Library</h1>
      <p>Explore thousands of books across all categories, available anytime.</p>

      <!-- Search Bar inside hero -->
      <div class="hero-search">
  <form method="GET" action="books.php">
    <div class="search-wrapper">
      <input type="text" name="search" placeholder="Search for books, authors, or subjects..." />
      <button type="submit" class="search-btn">Search</button>
    </div>
  </form>
</div>


    </div>
  </section>

  <!-- Browse Categories Button -->
  <section class="browse-section">
    <p class="browse-note">Not sure what to search? Start by exploring categories below.</p>
    <div class="browse-btn-wrapper">
      <a href="books.php" class="btn">Browse Categories</a>
    </div>
  </section>

  <!-- New Arrivals Section -->
  <section id="new-arrivals" class="new-arrivals-section">
    <h2>New Arrivals</h2>
    <div class="book-grid">
      <?php if (empty($new_arrivals)): ?>
        <p>No new books added recently.</p>
      <?php else: ?>
        <?php foreach ($new_arrivals as $book): ?>
          <div class="book-card">
            <?php if (!empty($book['image_path'])): ?>
              <img src="<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            <?php else: ?>
              <img src="images/bookshelf.jpeg" alt="Default book image">
            <?php endif; ?>
            <p><strong><?php echo htmlspecialchars($book['title']); ?></strong><br>by <?php echo htmlspecialchars($book['author']); ?></p>
            <a href="book_details.php?id=<?php echo $book['book_id']; ?>" class="read-btn">View Details</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Most Popular -->
  <section class="most-popular">
    <h2>Most Borrowed Books</h2>
    <div class="book-grid">
      <?php if (empty($most_popular)): ?>
        <p>No popular books yet.</p>
      <?php else: ?>
        <?php foreach ($most_popular as $book): ?>
          <div class="book-card">
            <?php if (!empty($book['image_path'])): ?>
              <img src="<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            <?php else: ?>
              <img src="images/bookshelf.jpeg" alt="Default book image">
            <?php endif; ?>
            <p><strong><?php echo htmlspecialchars($book['title']); ?></strong><br>by <?php echo htmlspecialchars($book['author']); ?></p>
            <a href="book_details.php?id=<?php echo $book['book_id']; ?>" class="read-btn">View Details</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- About Section -->
  <section class="about-section">
    <p>Our mission is to make knowledge accessible to all through a modern digital library experience.</p>
  </section>
<?php include 'footer.php'; ?>
  <script src="js/script.js"></script>
</body>
</html>