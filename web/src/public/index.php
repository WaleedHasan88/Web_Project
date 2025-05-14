<?php
require_once __DIR__ . '/../includes/header.php';

// Get current year for copyright
$currentYear = date('Y');

// Define image path
$heroImage = '../assets/images/EPU_Moodle_Banner.jpg';

// Get featured programs from database (you can implement this later)
$featuredPrograms = [
    [
        'title' => 'Information Systems Engineering',
        'description' => 'Comprehensive program focusing on information systems, software development, and digital solutions for modern business challenges.',
        'link' => '#'
    ],
    [
        'title' => 'Mechanical and Energy Engineering',
        'description' => 'Advanced program covering mechanical systems, renewable energy technologies, and sustainable energy solutions.',
        'link' => '#'
    ],
    [
        'title' => 'Civil Engineering',
        'description' => 'Professional program in structural design, construction management, and infrastructure development for modern cities.',
        'link' => '#'
    ]
];
?>

  <body>
    <header class="hero">
      <div class="overlay">
        <div class="container h-100">
          <div class="row h-100 align-items-center">
            <div class="col-12 text-center text-white">
              <h1 class="display-3 fw-bold" data-aos="fade-down" data-aos-duration="1000">
                Welcome to Erbil Polytechnic University
              </h1>
              <p class="lead" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
                Shaping Tomorrow's Leaders Through Excellence in Education
              </p>
              <a href="admissions.php" class="btn btn-primary btn-lg mt-3" 
                 data-aos="zoom-in" data-aos-duration="1000" data-aos-delay="400">
                Apply Now
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="py-5">
      <div class="container mb-5">
        <h2 class="text-center mb-5" data-aos="fade-up">Featured Programs</h2>
        <div class="row g-4">
          <?php foreach ($featuredPrograms as $index => $program): ?>
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
            <div class="card h-100">
              <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($program['title']); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($program['description']); ?></p>
                <a href="<?php echo htmlspecialchars($program['link']); ?>" class="btn btn-outline-primary">Learn More</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>   
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  </body>
