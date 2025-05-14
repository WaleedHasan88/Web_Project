<?php
require_once '../includes/header.php';

// Define about content
$aboutContent = [
    'history' => [
        'title' => 'Our History',
        'content' => 'The EPU is the offshoot of Foundation of Technical Education,
                    which was founded by Kurdistan Parliament in 1993 under the name
                    of Foundation of Technical Institutes. It started working
                    effectively to run the Technical Institutes in 1996. Formerly,
                    these institutes were run by Foundation of Technical Education
                    in Baghdad. In 2004, its name changed to Foundation of Technical
                    Education and later on it became Erbil Polytechnic University by
                    KRG in 2012.'
    ],
    'mission' => [
        'title' => 'Our Mission',
        'content' => 'To provide world-class education, foster groundbreaking
                    research, and prepare students to be leaders in their chosen
                    fields while contributing to the global community.'
    ]
];
?>

<main>
    <section class="about-section py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-down">
                About Erbil Polytechnic University
            </h2>
            
            <div class="row">
                <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
                    <?php foreach ($aboutContent as $section): ?>
                    <div class="mb-4">
                        <h3 class="mb-3"><?php echo htmlspecialchars($section['title']); ?></h3>
                        <p class="lead">
                            <?php echo htmlspecialchars($section['content']); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                    <div class="image-container shadow rounded overflow-hidden">
                        <img
                            src="../assets/images/campus.jpg"
                            alt="University Campus"
                            class="img-fluid w-100 h-100 object-fit-cover"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?> 