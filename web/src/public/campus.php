<?php
require_once __DIR__ . '/../includes/header.php';

// Define statistics
$campusStats = [
    [
        'icon' => 'fas fa-user-graduate',
        'count' => '15,000+',
        'label' => 'Satisfied Students',
        'bg_class' => 'bg-primary',
        'delay' => 100
    ],
    [
        'icon' => 'fas fa-chalkboard-teacher',
        'count' => '800+',
        'label' => 'Qualified Teachers',
        'bg_class' => 'bg-success',
        'delay' => 200
    ],
    [
        'icon' => 'fas fa-graduation-cap',
        'count' => '200+',
        'label' => 'Honor & Awards Win',
        'bg_class' => 'bg-info',
        'delay' => 300
    ],
    [
        'icon' => 'fas fa-book',
        'count' => '400+',
        'label' => 'Departments',
        'bg_class' => 'bg-warning',
        'delay' => 400
    ]
];

// Define facilities
$facilities = [
    [
        'title' => 'Modern Libraries',
        'description' => '24/7 access to digital and physical resources',
        'delay' => 100
    ],
    [
        'title' => 'Sports Complex',
        'description' => 'Olympic-size pool, gym, and multiple sports courts',
        'delay' => 200
    ],
    [
        'title' => 'Student Housing',
        'description' => 'Comfortable dormitories with modern amenities',
        'delay' => 300
    ],
    [
        'title' => 'Dining Facilities',
        'description' => 'Multiple cafeterias serving international cuisine',
        'delay' => 400
    ]
];

// Define student activities
$studentActivities = [
    [
        'title' => 'Student Clubs',
        'description' => 'Join various academic, cultural, and recreational clubs.',
        'link' => '#',
        'button_text' => 'View Clubs',
        'delay' => 100
    ],
    [
        'title' => 'Sports Teams',
        'description' => 'Participate in competitive sports at various levels.',
        'link' => '#',
        'button_text' => 'Sports Programs',
        'delay' => 200
    ],
    [
        'title' => 'Events & Festivals',
        'description' => 'Annual cultural festivals and academic events.',
        'link' => '#',
        'button_text' => 'Event Calendar',
        'delay' => 300
    ]
];

// Define carousel images
$carouselImages = [
    ['src' => '../assets/images/cmpus.jpg', 'alt' => 'Campus View 1'],
    ['src' => '../assets/images/campus1.jpg', 'alt' => 'Campus View 2'],
    ['src' => '../assets/images/campus2.JPG', 'alt' => 'Campus View 3']
];
?>

<main>
    <section class="campus-section py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-down">Campus Life</h2>

            <!-- Statistics -->
            <div class="row mb-5">
                <?php foreach ($campusStats as $stat): ?>
                <div class="col-md-3 mb-4" data-aos="fade-up" data-aos-delay="<?php echo htmlspecialchars($stat['delay']); ?>">
                    <div class="stat-box <?php echo htmlspecialchars($stat['bg_class']); ?> shadow-sm rounded p-4">
                        <i class="<?php echo htmlspecialchars($stat['icon']); ?>"></i>
                        <h3 class="counter"><?php echo htmlspecialchars($stat['count']); ?></h3>
                        <p><?php echo htmlspecialchars($stat['label']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Facilities and Carousel -->
            <div class="row mb-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <h3 class="mb-4">Campus Facilities</h3>
                    <ul class="list-group">
                        <?php foreach ($facilities as $facility): ?>
                        <li class="list-group-item" data-aos="fade-up" data-aos-delay="<?php echo htmlspecialchars($facility['delay']); ?>">
                            <h5><?php echo htmlspecialchars($facility['title']); ?></h5>
                            <p class="mb-0"><?php echo htmlspecialchars($facility['description']); ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                    <div id="campusCarousel" class="carousel slide shadow rounded" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <?php foreach ($carouselImages as $index => $image): ?>
                            <button type="button" data-bs-target="#campusCarousel" 
                                    data-bs-slide-to="<?php echo $index; ?>" 
                                    class="<?php echo $index === 0 ? 'active' : ''; ?>">
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner rounded">
                            <?php foreach ($carouselImages as $index => $image): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($image['src']); ?>" 
                                     class="d-block w-100" 
                                     alt="<?php echo htmlspecialchars($image['alt']); ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#campusCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#campusCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Student Activities -->
            <h3 class="text-center mb-4" data-aos="fade-up">Student Activities</h3>
            <div class="row g-4">
                <?php foreach ($studentActivities as $activity): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo htmlspecialchars($activity['delay']); ?>">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($activity['title']); ?></h5>
                            <p class="card-text">
                                <?php echo htmlspecialchars($activity['description']); ?>
                            </p>
                            <a href="<?php echo htmlspecialchars($activity['link']); ?>" 
                               class="btn btn-outline-primary">
                                <?php echo htmlspecialchars($activity['button_text']); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

