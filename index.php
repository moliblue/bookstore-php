<?php
$_title = 'Welcome to SB Online';
include 'sb_head.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_title; ?></title>
    <link rel="stylesheet" href="sb_style.css">
</head>
<body>
    <main class="container">

  <!-- Hero Section -->
<section class="hero" id="home">
    <div class="hero-content">
        <div class="hero-content">
            <h1>Discover Your Next Great Read</h1>
            <p>Explore thousands of books across every genre. From timeless classics to the latest bestsellers — all in one place.</p>
            <a href="#featured" class="btn">Browse Books</a>
        </div>
    </div>
</section>

        <!-- Featured Books -->
        <section class="featured-books">
            <h2>Featured Books</h2>
            <div class="book-grid">
                <!-- Book 1 -->
                <div class="book-card">
                    <div class="book-cover">
                        <span>Book Cover</span>
                    </div>
                    <div class="book-info">
                        <div class="book-title">The Great Gatsby</div>
                        <div class="book-author">F. Scott Fitzgerald</div>
                        <div class="book-price">$10.99</div>
                    </div>
                </div>

                <!-- Book 2 -->
                <div class="book-card">
                    <div class="book-cover">
                        <span>Book Cover</span>
                    </div>
                    <div class="book-info">
                        <div class="book-title">1984</div>
                        <div class="book-author">George Orwell</div>
                        <div class="book-price">$8.99</div>
                    </div>
                </div>

                <!-- Book 3 -->
                <div class="book-card">
                    <div class="book-cover">
                        <span>Book Cover</span>
                    </div>
                    <div class="book-info">
                        <div class="book-title">To Kill a Mockingbird</div>
                        <div class="book-author">Harper Lee</div>
                        <div class="book-price">$12.99</div>
                    </div>
                </div>

        </section>

        <!-- Call to Action -->
        <section class="hero">
            <h2>Ready to Explore More?</h2>
            <p>Browse our complete collection of books and discover your next great read.</p>
            <a href="product_view.php" style="display: inline-block; background: #e74c3c; color: white; padding: 12px 30px; border-radius: 4px; margin-top: 1rem;">View All Books</a>
        </section>

        <!-- Additional Sections -->
        <section style="margin: 4rem 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div style="text-align: center; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="color: #2c3e50; margin-bottom: 1rem;">📖 Free Shipping</h3>
                    <p>Free delivery on orders over $25. Fast and reliable shipping to your doorstep.</p>
                </div>
                
                <div style="text-align: center; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="color: #2c3e50; margin-bottom: 1rem;">⭐ Customer Reviews</h3>
                    <p>Read genuine reviews from our community of book lovers before you buy.</p>
                </div>
                
                <div style="text-align: center; padding: 2rem; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="color: #2c3e50; margin-bottom: 1rem;">🔒 Secure Payment</h3>
                    <p>Shop with confidence using our secure payment processing system.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
<?php include 'sb_foot.php'; ?>