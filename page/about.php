<?php
$_title = 'About BookShop';
include '../sb_head.php';
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
        <section class="hero">
            <h1>About BookShop</h1>
            <p>Your trusted partner in the world of literature since 2010</p>
        </section>

        <!-- Our Story -->
        <section style="margin: 3rem 0;">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #2c3e50;">Our Story</h2>
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
                    BookShop began as a small neighborhood bookstore with a simple mission: to connect readers with books they'll love. 
                    What started as a single storefront has grown into a comprehensive online platform serving book lovers worldwide.
                </p>
                <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
                    We believe in the transformative power of reading and are committed to curating a diverse collection that represents 
                    voices from around the globe. Our team of passionate book enthusiasts carefully selects each title to ensure 
                    quality and relevance for our readers.
                </p>
            </div>
        </section>

        <!-- Our Mission -->
        <section style="background: white; padding: 3rem; border-radius: 8px; margin: 3rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #2c3e50;">Our Mission</h2>
            <div style="text-align: center; font-size: 1.2rem; font-style: italic; color: #7f8c8d;">
                "To inspire, educate, and connect people through the power of books, while fostering 
                a lifelong love of reading in communities everywhere."
            </div>
        </section>

        <!-- What We Offer -->
        <section style="margin: 3rem 0;">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #2c3e50;">What We Offer</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <h3 style="color: #e74c3c; margin-bottom: 1rem;">📚 Vast Selection</h3>
                    <p>Thousands of books across all genres, from classic literature to contemporary bestsellers.</p>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <h3 style="color: #e74c3c; margin-bottom: 1rem;">🌟 Curated Collections</h3>
                    <p>Hand-picked recommendations and themed collections to help you discover your next favorite read.</p>
                </div>
                
                <div style="background: white; padding: 2rem; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <h3 style="color: #e74c3c; margin-bottom: 1rem;">🚚 Fast Delivery</h3>
                    <p>Quick and reliable shipping to get your books to you as soon as possible.</p>
                </div>
            </div>
        </section>

        <!-- Our Values -->
        <section style="margin: 3rem 0;">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #2c3e50;">Our Values</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center;">
                    <h4 style="color: #2c3e50; margin-bottom: 1rem;">Quality</h4>
                    <p>We maintain high standards in book selection and customer service.</p>
                </div>
                
                <div style="text-align: center;">
                    <h4 style="color: #2c3e50; margin-bottom: 1rem;">Diversity</h4>
                    <p>We celebrate diverse authors and perspectives in our collection.</p>
                </div>
                
                <div style="text-align: center;">
                    <h4 style="color: #2c3e50; margin-bottom: 1rem;">Community</h4>
                    <p>We support local authors and literary initiatives.</p>
                </div>
                
                <div style="text-align: center;">
                    <h4 style="color: #2c3e50; margin-bottom: 1rem;">Sustainability</h4>
                    <p>We use eco-friendly packaging and support sustainable practices.</p>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section style="margin: 3rem 0;">
            <h2 style="text-align: center; margin-bottom: 2rem; color: #2c3e50;">Meet Our Team</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div>
                    <div style="width: 120px; height: 120px; background: #ecf0f1; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: #7f8c8d;">Photo</div>
                    <h4>Sarah Johnson</h4>
                    <p style="color: #7f8c8d;">Founder & CEO</p>
                </div>
                
                <div>
                    <div style="width: 120px; height: 120px; background: #ecf0f1; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: #7f8c8d;">Photo</div>
                    <h4>Michael Chen</h4>
                    <p style="color: #7f8c8d;">Head Curator</p>
                </div>
                
                <div>
                    <div style="width: 120px; height: 120px; background: #ecf0f1; border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: #7f8c8d;">Photo</div>
                    <h4>Emily Rodriguez</h4>
                    <p style="color: #7f8c8d;">Customer Service</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
<?php include '../sb_foot.php'; ?>