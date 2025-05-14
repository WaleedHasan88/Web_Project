<?php
require_once __DIR__ . '/../includes/header.php';

// Define research centers data
$researchCenters = [
    [
        'title' => 'Center for AI & Machine Learning',
        'description' => 'Advancing artificial intelligence and machine learning technologies.',
        'link' => '#'
    ],
    [
        'title' => 'Biomedical Research Institute',
        'description' => 'Pioneering research in medical sciences and biotechnology.',
        'link' => '#'
    ],
    [
        'title' => 'Environmental Studies Center',
        'description' => 'Research focused on sustainability and environmental conservation.',
        'link' => '#'
    ]
];

// Define research highlights
$researchHighlights = [
    'Advanced Research Centers',
    'Industry Partnerships',
    'Global Collaboration Network'
];
?>

<section class="research-section py-5">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-down">
            Research Excellence
        </h2>

        <!-- Research Overview -->
        <div class="row mb-5">
            <div class="col-lg-6" data-aos="fade-right">
                <img src="../assets/images/research.jpg"
                     alt="Research Lab"
                     class="img-fluid rounded shadow"
                />
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                <h3 class="mb-4">Scientific Research Center</h3>
                <p class="lead">
                    The Vision of the Scientific Research Center of EPU is to create
                    an outstanding platform of support for EPU researchers, staff and
                    students as well as people outside of the EPU. The Center aims to
                    provide all the necessary equipment, space and environment
                    required in quality research in Kurdistan region.
                </p>
                <ul class="list-group mt-4">
                    <?php foreach ($researchHighlights as $index => $highlight): ?>
                    <li class="list-group-item" data-aos="fade-left" data-aos-delay="<?php echo ($index + 3) * 100; ?>">
                        ✓ <?php echo htmlspecialchars($highlight); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Research Centers -->
        <h3 class="text-center mb-4" data-aos="fade-up">Research Centers</h3>
        <div class="row g-4">
            <?php foreach ($researchCenters as $index => $center): ?>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($center['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($center['description']); ?></p>
                        <a href="<?php echo htmlspecialchars($center['link']); ?>" 
                           class="btn btn-outline-primary">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

