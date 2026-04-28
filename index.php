<?php
session_start();
include("includes/db.php");

// Fetch all courses
$courses = $conn->query("SELECT * FROM course");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDDEMY - Excellence in Education</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/logo44.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/logo44.png">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <style>
        /* Navbar */
        .custom-navbar {
            background-color: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .custom-navbar.scrolled {
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.3);
        }
        .custom-navbar .nav-link {
            color: #FFD700 !important;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        .custom-navbar .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: #FFD700;
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .custom-navbar .nav-link:hover::after {
            width: 80%;
        }
        .custom-navbar .nav-link:hover {
            color: #fff !important;
        }
        .custom-navbar .btn-golden {
            background-color: #FFD700;
            color: #000;
            border-radius: 25px;
            padding: 8px 25px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .custom-navbar .btn-golden:hover {
            background-color: #fff;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }
        
        /* Footer */
        .custom-footer {
            background-color: #000;
            color: #FFD700;
        }
        .text-golden {
            color: #FFD700 !important;
        }

/* ==================== TESTIMONIALS SECTION ==================== */
.testimonials-section {
    background: linear-gradient(160deg, #0a0a0a 0%, #111 60%, #1a1400 100%);
    position: relative;
    overflow: hidden;
}

.testimonials-section::before {
    content: '';
    position: absolute;
    top: -200px;
    right: -200px;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(255,215,0,0.07) 0%, transparent 70%);
    pointer-events: none;
}

.testimonials-section .section-title {
    color: #fff;
}

.testimonials-section .section-subtitle {
    color: rgba(255,255,255,0.6);
}

/* Overall Rating */
.overall-rating {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    background: rgba(255,215,0,0.08);
    border: 1px solid rgba(255,215,0,0.25);
    border-radius: 50px;
    padding: 10px 28px;
    flex-wrap: wrap;
    justify-content: center;
}

.rating-big {
    font-size: 2rem;
    font-weight: 800;
    color: #FFD700;
    line-height: 1;
}

.rating-stars-big i {
    color: #FFD700;
    font-size: 1.1rem;
}

.rating-count {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.55);
}

/* ✅ Responsive Grid Layout */
.testi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

/* Featured Card */
.testi-featured {
    grid-column: span 2;
}
@media (max-width: 991px) {
    .testi-featured {
        grid-column: span 1;
    }
}

/* Card */
.testi-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,215,0,0.15);
    border-radius: 20px;
    padding: 26px 24px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    transition: all 0.35s ease;
    position: relative;
    overflow: hidden;
}
.testi-card::before {
    content: '\201C';
    position: absolute;
    top: -10px;
    right: 20px;
    font-size: 8rem;
    color: rgba(255,215,0,0.06);
    font-family: Georgia, serif;
    line-height: 1;
    pointer-events: none;
}
.testi-card:hover {
    background: rgba(255,215,0,0.07);
    border-color: rgba(255,215,0,0.45);
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.4);
}
.testi-featured {
    background: rgba(255,215,0,0.06);
    border-color: rgba(255,215,0,0.3);
}
.testi-featured:hover {
    background: rgba(255,215,0,0.1);
}

/* Top Row */
.testi-top {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.testi-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.95rem;
    flex-shrink: 0;
    color: #111;
}
/* Avatar Colors */
.testi-av-1 { background: linear-gradient(135deg, #FFD700, #ffed4e); }
.testi-av-2 { background: linear-gradient(135deg, #f4a261, #e76f51); color: #fff; }
.testi-av-3 { background: linear-gradient(135deg, #a8dadc, #457b9d); color: #fff; }
.testi-av-4 { background: linear-gradient(135deg, #b7e4c7, #40916c); color: #fff; }
.testi-av-5 { background: linear-gradient(135deg, #e9c46a, #f4a261); }
.testi-av-6 { background: linear-gradient(135deg, #c77dff, #7b2fff); color: #fff; }

.testi-meta h5 {
    margin: 0 0 3px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #fff;
}
.testi-city {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.45);
    margin-right: 8px;
}
.testi-course-tag {
    display: inline-block;
    background: rgba(255,215,0,0.12);
    color: #FFD700;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 9px;
    border-radius: 20px;
    border: 1px solid rgba(255,215,0,0.2);
}

/* Stars */
.testi-stars {
    display: flex;
    align-items: center;
    gap: 3px;
}
.testi-stars i {
    color: #FFD700;
    font-size: 0.9rem;
}
.testi-date {
    margin-left: auto;
    font-size: 0.72rem;
    color: rgba(255,255,255,0.3);
}

/* Review Text */
.testi-text {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.8);
    line-height: 1.75;
    margin: 0;
    flex-grow: 1;
}
.testi-text strong {
    color: #FFD700;
    font-weight: 600;
}
.testi-featured .testi-text {
    font-size: 0.95rem;
}

/* Result Badge */
.testi-result {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,215,0,0.1);
    border: 1px solid rgba(255,215,0,0.25);
    color: #FFD700;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 6px 14px;
    border-radius: 50px;
    width: fit-content;
}

/* View More Button */
.btn-view-more {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #FFD700, #ffed4e);
    color: #111;
    font-weight: 700;
    font-size: 1rem;
    padding: 16px 40px;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 8px 30px rgba(255,215,0,0.3);
}
.btn-view-more:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(255,215,0,0.5);
}
.btn-view-more i {
    transition: transform 0.3s ease;
}
.btn-view-more:hover i {
    transform: translateX(5px);
}

    </style>
</head>
<body>

<!-- ✅ Navbar -->
<nav class="navbar navbar-expand-lg shadow-sm fixed-top custom-navbar" id="mainNav">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="assets/images/logo44.png" alt="MEDDEMY Logo" class="logo-img me-2" style="height:40px;">
      <span class="fw-bold text-golden">MEDDEMY</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" 
            aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <!-- <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li> -->
        <li class="nav-item"><a class="nav-link" href="login.php">AFNS</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">NCAT</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">BSN</a></li>
        <li class="nav-item"><a class="nav-link" href="books.php">Books</a></li>
        <li class="nav-item"><a class="nav-link" href="announcements.php">Posts</a></li>

        <li class="nav-item"><a class="nav-link" href="students/materials.php">Free Materials</a></li>
        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
        <li class="nav-item">
          <a class="btn btn-golden ms-2 px-3 fw-bold" href="register.php">Register</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- ✅ Hero Section -->
<section class="hero-section">
  <div class="hero-overlay"></div>
  <div class="container">
    <div class="row align-items-center min-vh-100">
      <div class="col-lg-6 hero-content">
        <h3 class="hero-title">Crack Your Entry Test with Confidence at <span class="text-golden">MEDDEMY</span></h3>
        <p class="hero-subtitle">Expert-led live and recorded classes designed to help you succeed.</p>
        <div class="hero-buttons">
          <a href="register.php" class="btn btn-hero-primary">Get Started</a>
          <a href="#courses" class="btn btn-hero-secondary">View Courses</a>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <i class="fas fa-users"></i>
            <div>
              <h3 class="counter" data-target="1000">1000+</h3>
              <p>Students</p>
            </div>
          </div>
          <div class="stat-item">
            <i class="fas fa-video"></i>
            <div>
              <h3 class="counter" data-target="300">300+</h3>
              <p>Live Sessions</p>
            </div>
          </div>
          <div class="stat-item">
            <i class="fas fa-award"></i>
            <div>
              <h3 class="counter" data-target="98">98%</h3>
              <p>Success Rate</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-image-wrapper">
          <img src="assets/images/learn123.png" alt="Student Learning" class="img-fluid hero-image">
          <div class="floating-card card-1">
            <i class="fas fa-graduation-cap"></i>
            <p>Expert Instructors</p>
          </div>
          <div class="floating-card card-2">
            <i class="fas fa-certificate"></i>
            <p>Certified Courses</p>
          </div>
          <div class="floating-card card-3">
            <i class="fas fa-book-open"></i>
            <p>Comprehensive Content</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="scroll-indicator">
    <i class="fas fa-chevron-down"></i>
  </div>
</section>



<!-- ✅ Courses Section (UPDATED) -->
<section class="courses-section py-5" id="courses">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">Our Courses</span>
      <h2 class="section-title">Explore Our Course Catalog</h2>
      <p class="section-subtitle">Comprehensive curriculum designed for board exam excellence</p>
    </div>
    
    <div class="row g-4">
      <?php while($row = $courses->fetch_assoc()) { 
        $discount = ($row['price'] > 0) ? round((($row['price'] - $row['discount_price']) / $row['price']) * 100) : 0;
      ?>
        <div class="col-lg-3 col-md-6 col-sm-6">
          <a href="login.php" class="course-box-link">
            <div class="course-box">
              
              <!-- Course Image -->
              <div class="course-img">
                <?php if($discount > 0): ?>
                  <span class="course-img-badge"><?php echo $discount; ?>% OFF</span>
                <?php endif; ?>
                <img src="assets/images/<?php echo $row['image']; ?>" 
                     alt="<?php echo htmlspecialchars($row['title']); ?>"
                     loading="lazy">
              </div>

              <!-- Card Body -->
              <div class="course-body">
                <h3 class="course-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                <p class="course-desc"><?php echo htmlspecialchars(substr($row['description'], 0, 90)) . '...'; ?></p>
                
                <!-- Price -->
                <div class="course-price">
                  <span class="old-price">Rs. <?php echo number_format($row['price']); ?></span>
                  <span class="new-price">Rs. <?php echo number_format($row['discount_price']); ?></span>
                  <?php if($discount > 0): ?>
                    <span class="discount-badge"><?php echo $discount; ?>% OFF</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="course-footer">
                <span class="course-cta">Explore Course <i class="fas fa-arrow-right"></i></span>
              </div>
            </div>
          </a>
        </div>
      <?php } ?>
    </div>
  </div>
</section>




<!-- ✅ Stats Section -->
<section class="stats-section py-5 text-center">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-3 col-6">
        <div class="stat-box">
          <div class="stat-icon">
            <i class="fas fa-user-graduate"></i>
          </div>
          <h2 class="stat-number counter" data-target="1000">1000</h2>
          <p class="stat-label">Students Enrolled</p>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-box">
          <div class="stat-icon">
            <i class="fas fa-broadcast-tower"></i>
          </div>
          <h2 class="stat-number counter" data-target="300">300</h2>
          <p class="stat-label">Live Sessions</p>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-box">
          <div class="stat-icon">
            <i class="fas fa-trophy"></i>
          </div>
          <h2 class="stat-number counter" data-target="98">98</h2>
          <p class="stat-label">Success Rate</p>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-box">
          <div class="stat-icon">
            <i class="fas fa-book"></i>
          </div>
          <h2 class="stat-number counter" data-target="5">5</h2>
          <p class="stat-label">Courses Available</p>
        </div>
      </div>
    </div>
  </div>
</section> 


<!-- ✅ Learning Features Section -->
<section class="features-section py-5" id="features">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">Learning Features</span>
      <h2 class="section-title">Everything You Need to Excel</h2>
      <p class="section-subtitle">Comprehensive tools and resources designed for your success</p>
    </div>
    
    <div class="row align-items-center">
      <div class="col-md-6">
        <div class="row g-3">
          <div class="col-12">
            <a href="login.php" class="feature-card-link">
              <div class="card feature-card">
                <div class="card-body d-flex align-items-center">
                  <span class="feature-icon">🎥</span>
                  <div>
                    <h5 class="card-title mb-1">HD VIDEO LECTURES</h5>
                    <p class="card-text">Crystal-clear recordings by your favorite instructors, available 24/7</p>
                  </div>
                  <i class="fas fa-arrow-right feature-arrow"></i>
                </div>
              </div>
            </a>
          </div>
          <div class="col-12">
            <a href="login.php" class="feature-card-link">
              <div class="card feature-card">
                <div class="card-body d-flex align-items-center">
                  <span class="feature-icon">📝</span>
                  <div>
                    <h5 class="card-title mb-1">INTERACTIVE QUIZZES</h5>
                    <p class="card-text">Test your knowledge with instant feedback and detailed explanations</p>
                  </div>
                  <i class="fas fa-arrow-right feature-arrow"></i>
                </div>
              </div>
            </a>
          </div>
          <div class="col-12">
            <a href="login.php" class="feature-card-link">
              <div class="card feature-card">
                <div class="card-body d-flex align-items-center">
                  <span class="feature-icon">📡</span>
                  <div>
                    <h5 class="card-title mb-1">LIVE SESSIONS</h5>
                    <p class="card-text">Real-time interaction with instructors and doubt-clearing sessions</p>
                  </div>
                  <i class="fas fa-arrow-right feature-arrow"></i>
                </div>
              </div>
            </a>
          </div>
          <div class="col-12">
            <a href="students/materials.php" class="feature-card-link">
              <div class="card feature-card">
                <div class="card-body d-flex align-items-center">
                  <span class="feature-icon">📂</span>
                  <div>
                    <h5 class="card-title mb-1">PRACTICE ASSIGNMENT</h5>
                    <p class="card-text">Apply your knowledge through carefully designed practical tasks</p>
                  </div>
                  <i class="fas fa-arrow-right feature-arrow"></i>
                </div>
              </div>
            </a>
          </div>
          <div class="col-12">
            <a href="announcements.php" class="feature-card-link">
              <div class="card feature-card">
                <div class="card-body d-flex align-items-center">
                  <span class="feature-icon">📰</span>
                  <div>
                    <h5 class="card-title mb-1">POSTS</h5>
                    <p class="card-text">Stay updated with daily posts and insights shared regularly.</p>
                  </div>
                  <i class="fas fa-arrow-right feature-arrow"></i>
                </div>
              </div>
            </a>
          </div>
          
        </div>
      </div>
      
  <div class="col-md-6">
        <div class="features-image-wrapper">
          <img src="assets/images/learn111.png" alt="Learning Features" class="img-fluid features-image">
          <div class="feature-badge badge-1">
            <i class="fas fa-mobile-alt"></i>
            <span>Learn Anywhere</span>
          </div>
          <div class="feature-badge badge-2">
            <i class="fas fa-infinity"></i>
            <span>Unlimited Access</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
      

<!-- ✅ Testimonials Section -->
<section class="testimonials-section py-5 " id="testimonials">
  <div class="container">

    <div class="section-header text-center mb-5">
      <span class="section-tag">Student Success Stories</span>
      <h2 class="section-title">Real Students. Real Results.</h2>
      <p class="section-subtitle">Thousands of students trusted MEDDEMY — here's what they have to say</p>

      <!-- Overall Rating Bar -->
      <div class="overall-rating mt-4">
        <div class="rating-big">4.9</div>
        <div class="rating-stars-big">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          <i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
        </div>
        <div class="rating-count">Based on 1,200+ verified reviews</div>
      </div>
    </div>

    <!-- Review Cards Grid -->
    <div class="testi-grid">

      <!-- Card 1 - Featured Large -->
      <div class="testi-card testi-featured">
        <div class="testi-top">
          <div class="testi-avatar testi-av-1">AK</div>
          <div class="testi-meta">
            <h5>Fatima Zahra</h5>
            <span class="testi-city"><i class="fas fa-map-marker-alt"></i> Lahore</span>
            <span class="testi-course-tag">AFNS Preparation</span>
          </div>
          <div class="testi-verified"><i class="fas fa-check-circle"></i> Verified</div>
        </div>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          <i class="fas fa-star"></i><i class="fas fa-star"></i>
          <span class="testi-date">March 2025</span>
        </div>
        <p class="testi-text">
          "Maine MEDDEMY se AFNS ki complete preparation ki aur pehli attempt mein select ho gayi! 
          Sir Shehraz ki classes itni detailed hain ke concept ekdum clear ho jata hai. 
          <strong>Merit list mein 3rd position aayi — yeh sab MEDDEMY ki wajah se possible hua.</strong>"
        </p>
        <div class="testi-result">
          <i class="fas fa-trophy"></i> AFNS Selected — 3rd Merit Position
        </div>
      </div>

      <!-- Card 2 -->
      <div class="testi-card">
        <div class="testi-top">
          <div class="testi-avatar testi-av-2">MA</div>
          <div class="testi-meta">
            <h5>Muhammad Ali</h5>
            <span class="testi-city"><i class="fas fa-map-marker-alt"></i> Karachi</span>
            <span class="testi-course-tag">NCAT Course</span>
          </div>
          <div class="testi-verified"><i class="fas fa-check-circle"></i> Verified</div>
        </div>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          <i class="fas fa-star"></i><i class="fas fa-star"></i>
          <span class="testi-date">Feb 2025</span>
        </div>
        <p class="testi-text">
          "NCAT ka test bht tough tha lekin MEDDEMY ke quizzes aur MCQs ne itni practice karwa di ke 
          actual test easy laga. Live sessions mein instructor se seedha sawaal pooch sakte hain — 
          yeh feature zabardast hai!"
        </p>
        <div class="testi-result">
          <i class="fas fa-star"></i> NCAT Score: 87/100
        </div>
      </div>

      
      <!-- Card 4 -->
      <div class="testi-card">
        <div class="testi-top">
          <div class="testi-avatar testi-av-4">UR</div>
          <div class="testi-meta">
            <h5>Usman Raza</h5>
            <span class="testi-city"><i class="fas fa-map-marker-alt"></i> Faisalabad</span>
            <span class="testi-course-tag">AFNS Preparation</span>
          </div>
          <div class="testi-verified"><i class="fas fa-check-circle"></i> Verified</div>
        </div>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          <i class="fas fa-star"></i><i class="fas fa-star"></i>
          <span class="testi-date">Dec 2024</span>
        </div>
        <p class="testi-text">
          "Pehle 2 baar AFNS test fail hua. Phir ek dost ne MEDDEMY recommend kiya. 
          3rd attempt mein finally selected ho gaya! 
          Instructor ka method aur mock tests — dono ne game change kar diya."
        </p>
        <div class="testi-result">
          <i class="fas fa-medal"></i> Selected on 3rd Attempt
        </div>
      </div>

      <!-- Card 5 -->
      <div class="testi-card">
        <div class="testi-top">
          <div class="testi-avatar testi-av-5">SB</div>
          <div class="testi-meta">
            <h5>Sana Baig</h5>
            <span class="testi-city"><i class="fas fa-map-marker-alt"></i> Multan</span>
            <span class="testi-course-tag">NCAT Course</span>
          </div>
          <div class="testi-verified"><i class="fas fa-check-circle"></i> Verified</div>
        </div>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          <i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
          <span class="testi-date">Nov 2024</span>
        </div>
        <p class="testi-text">
          "Mobile pe bhi classes access ho jaati hain, kahin bhi kahin bhi padh sako. 
          Ghar ke kaam ke saath preparation manage karna mushkil tha — MEDDEMY ne 
          flexible schedule ki wajah se possible kar diya."
        </p>
        <div class="testi-result">
          <i class="fas fa-mobile-alt"></i> Studied on Mobile — Passed!
        </div>
      </div>

      <!-- Card 6 -->
      <div class="testi-card">
        <div class="testi-top">
          <div class="testi-avatar testi-av-6">HM</div>
          <div class="testi-meta">
            <h5>Hamza Mirza</h5>
            <span class="testi-city"><i class="fas fa-map-marker-alt"></i> Peshawar</span>
            <span class="testi-course-tag">BSN Nursing</span>
          </div>
          <div class="testi-verified"><i class="fas fa-check-circle"></i> Verified</div>
        </div>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          <i class="fas fa-star"></i><i class="fas fa-star"></i>
          <span class="testi-date">Oct 2024</span>
        </div>
        <p class="testi-text">
          "Peshawar mein koi acha coaching center nahi tha. Online MEDDEMY join kiya 
          aur Lahore ke students ke saath compete kiya — result same aya! 
          <strong>Geography ab barrier nahi rahi.</strong>"
        </p>
        <div class="testi-result">
          <i class="fas fa-map-pin"></i> From Peshawar — Top Rank
        </div>
      </div>

    </div><!-- end testi-grid -->

    <!-- View More Button -->
    <div class="text-center mt-5">
      <a href="register.php" class="btn-view-more">
        Join 1,200+ Happy Students <i class="fas fa-arrow-right"></i>
      </a>
    </div>

  </div>
</section>



<!-- ✅ About Section -->
<section class="about-section py-5" id="about">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">About Us</span>
      <h2 class="section-title">Empowering Students Across Pakistan</h2>
      <p class="section-subtitle">Quality education that breaks barriers and builds futures</p>
    </div>
    
    <div class="row align-items-center mb-5">
      <div class="col-md-6 about-image-col">
        <div class="about-image-wrapper">
          <img src="assets/images/ceo1.png" alt="Founder" class="img-fluid about-image">
          <div class="about-badge">
            <i class="fas fa-star"></i>
            <span>Founded 2020</span>
          </div>
        </div>
      </div>
      <div class="col-md-6 about-content">
        <h3 class="text-golden mb-3">Meet Our Visionary Founder</h3>
        <h4 class="mb-3">Shehraz - CEO & Founder</h4>
        <p class="lead mb-4">Meddemy was born from a simple yet powerful vision: to make quality education accessible to every student in Pakistan, regardless of their geographic location or economic background.</p>
        <p>Under the leadership of CEO Shehraz, we've built more than just an online learning platform. We've created a community of excellence where students receive structured lessons, comprehensive past paper practice, and interactive support that truly makes a difference.</p>
        <div class="mission-values mt-4">
          <div class="value-item">
            <i class="fas fa-bullseye text-golden"></i>
            <div>
              <h5>Our Mission</h5>
              <p>To democratize quality education and help every student achieve their academic dreams</p>
            </div>
          </div>
          <div class="value-item">
            <i class="fas fa-heart text-golden"></i>
            <div>
              <h5>Our Values</h5>
              <p>Excellence, accessibility, innovation, and unwavering commitment to student success</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ✅ Success Growth Section -->
<!-- <section class="success-growth-section py-5">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 order-md-2">
        <div class="growth-image-wrapper">
          <img src="assets/images/rate1.png" alt="Success Rate Growth" class="img-fluid growth-image">
        </div>
      </div>
      
      <div class="col-md-6 order-md-1">
        <div class="growth-content">
          <span class="section-tag">Our Track Record</span>
          <h2 class="text-golden mb-4">MEDDEMY Institute: Success Rate Growth</h2>
          <p class="lead mb-4">
            Over the past five years, MEDDEMY has consistently improved its success rate, 
            reaching an impressive <strong>97%</strong> in 2025. This remarkable growth reflects 
            our unwavering commitment to high-quality education, structured learning methodologies, 
            and student-focused support systems.
          </p>
          
          <div class="growth-timeline">
            <div class="timeline-item">
              <div class="timeline-year">2021</div>
              <div class="timeline-bar">
                <div class="timeline-progress" style="width: 60%;" data-percentage="60"></div>
              </div>
              <div class="timeline-percentage">60%</div>
            </div>
            <div class="timeline-item">
              <div class="timeline-year">2022</div>
              <div class="timeline-bar">
                <div class="timeline-progress" style="width: 72%;" data-percentage="72"></div>
              </div>
              <div class="timeline-percentage">72%</div>
            </div>
            <div class="timeline-item">
              <div class="timeline-year">2023</div>
              <div class="timeline-bar">
                <div class="timeline-progress" style="width: 84%;" data-percentage="84"></div>
              </div>
              <div class="timeline-percentage">84%</div>
            </div>
            <div class="timeline-item">
              <div class="timeline-year">2024</div>
              <div class="timeline-bar">
                <div class="timeline-progress" style="width: 91%;" data-percentage="91"></div>
              </div>
              <div class="timeline-percentage">91%</div>
            </div>
            <div class="timeline-item highlight">
              <div class="timeline-year">2025</div>
              <div class="timeline-bar">
                <div class="timeline-progress golden" style="width: 97%;" data-percentage="97"></div>
              </div>
              <div class="timeline-percentage">97%</div>
            </div>
          </div>
          
          <div class="growth-quote">
            <i class="fas fa-quote-left"></i>
            <p>"Every percentage point represents hundreds of students achieving their dreams. That's what drives us forward."</p>
            <span>- Shehraz, CEO</span>
          </div>
        </div>
      </div>
    </div>
  </div> -->


    
<!-- ✅ Why Choose Us Section -->
<section class="why-choose-section py-5">
  <div class="container">
    <div class="section-header text-center mb-5">
      <span class="section-tag">Why Choose MEDDEMY</span>
      <h2 class="section-title">Your Success is Our Priority</h2>
    </div>
    
    <div class="row g-4">
      <div class="col-lg-3 col-md-6">
        <div class="why-card">
          <div class="why-icon">
            <i class="fas fa-dollar-sign"></i>
          </div>
          <h4>Affordable Learning</h4>
          <p>Quality education at prices that won't break the bank. Education should be a right, not a privilege.</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="why-card">
          <div class="why-icon">
            <i class="fas fa-chalkboard-teacher"></i>
          </div>
          <h4>Expert Instructors</h4>
          <p>Learn from Pakistan's finest educators who are passionate about your success.</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="why-card">
          <div class="why-icon">
            <i class="fas fa-clock"></i>
          </div>
          <h4>Flexible Schedule</h4>
          <p>Study at your own pace, anytime, anywhere. We fit into your life, not the other way around.</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <div class="why-card">
          <div class="why-icon">
            <i class="fas fa-headset"></i>
          </div>
          <h4>24/7 Support</h4>
          <p>Questions? We're always here. Get help whenever you need it.</p>
        </div>
      </div>
    </div>
  </div>
</section>






<!-- ✅ CTA Section -->
<section class="cta-section py-5">
  <div class="container">
    <div class="cta-card">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2 class="cta-title">Ready to Transform Your Academic Journey?</h2>
          <p class="cta-text">Join thousands of successful students who chose MEDDEMY for their board exam preparation</p>
        </div>
        <div class="col-md-4 text-md-end">
          <a href="register.php" class="btn btn-cta">Enroll Now <i class="fas fa-rocket"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ✅ WhatsApp Floating Button -->
<a href="https://wa.me/923062661027" class="whatsapp-btn" target="_blank">
  <img src="assets/images/whatsapp.png" alt="WhatsApp">
  <span class="whatsapp-tooltip">Need Help? Chat with us!</span>
</a>

<!-- ✅ Footer -->
<footer class="custom-footer py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="footer-brand mb-3">
          <img src="assets/images/logo1.jpeg" alt="MEDDEMY" style="height: 50px;" class="mb-3">
          <h4 class="text-golden">MEDDEMY</h4>
          <p>Empowering students across Pakistan with quality, affordable education for board exam excellence.</p>
        </div>
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-tiktok"></i></a>
          <a href="https://www.youtube.com/@MEDDEMY"><i class="fab fa-youtube"></i></a>
          <a href="#"><i class="fab fa-linkedin"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-6">
        <h5 class="text-golden mb-3">Quick Links</h5>
        <ul class="footer-links">
          <li><a href="#about">About Us</a></li>
          <li><a href="#courses">Courses</a></li>
          <li><a href="#features">Features</a></li>
          <li><a href="login.php">Login</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6">
        <h5 class="text-golden mb-3">Support</h5>
        <ul class="footer-links">
          <li><a href="#">Help Center</a></li>
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6">
        <h5 class="text-golden mb-3">Contact Info</h5>
        <ul class="footer-contact">
          <li><i class="fas fa-phone"></i> +92 306 2661027</li>
          <li><i class="fas fa-envelope"></i> info@meddemy.com</li>
          <li><i class="fas fa-map-marker-alt"></i> Lahore, Pakistan</li>
        </ul>
      </div>
    </div>
    <hr class="my-4" style="border-color: rgba(255,215,0,0.3);">
    <div class="text-center">
      <p class="mb-0">&copy; <?php echo date("Y"); ?> MEDDEMY. All rights reserved. Made with <i class="fas fa-heart text-danger"></i> for students across Pakistan</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>