<?php
require_once __DIR__ . '/../includes/header.php';

// Define application process steps
$applicationSteps = [
    [
        'step' => '1. Submit Application',
        'description' => 'Complete the online application form with all required documents.',
        'delay' => 100
    ],
    [
        'step' => '2. Academic Records',
        'description' => 'Submit transcripts and standardized test scores.',
        'delay' => 200
    ],
    [
        'step' => '3. Letters of Recommendation',
        'description' => 'Provide letters from academic or professional references.',
        'delay' => 300
    ],
    [
        'step' => '4. Interview',
        'description' => 'Selected candidates will be invited for an interview.',
        'delay' => 400
    ]
];

// Define admission requirements
$admissionRequirements = [
    [
        'program' => 'Undergraduate',
        'gpa' => '3.0+',
        'test_scores' => 'SAT/ACT',
        'additional' => '2 Letters of Recommendation'
    ],
    [
        'program' => 'Graduate',
        'gpa' => '3.5+',
        'test_scores' => 'GRE/GMAT',
        'additional' => '3 Letters of Recommendation, Research Proposal'
    ]
];
?>

<section class="admissions-section py-5">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-down">Admissions</h2>

        <!-- Application Process -->
        <div class="row mb-5">
            <div class="col-lg-6">
                <h3 class="mb-4" data-aos="fade-right">Application Process</h3>
                <div class="timeline">
                    <?php foreach ($applicationSteps as $step): ?>
                    <div class="card mb-3" data-aos="fade-up" data-aos-delay="<?php echo htmlspecialchars($step['delay']); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($step['step']); ?></h5>
                            <p class="card-text">
                                <?php echo htmlspecialchars($step['description']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                <img src="../assets/images/admission.jpg"
                     alt="Students at campus"
                     class="img-fluid rounded shadow"
                />
            </div>
        </div>

        <!-- Admission Requirements -->
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="text-center mb-4" data-aos="fade-up">
                    Admission Requirements
                </h3>
                <div class="table-responsive" data-aos="fade-up" data-aos-delay="200">
                    <table class="table table-bordered table-hover shadow-sm">
                        <thead class="table-primary">
                            <tr>
                                <th>Program</th>
                                <th>GPA</th>
                                <th>Test Scores</th>
                                <th>Additional Requirements</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admissionRequirements as $requirement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($requirement['program']); ?></td>
                                <td><?php echo htmlspecialchars($requirement['gpa']); ?></td>
                                <td><?php echo htmlspecialchars($requirement['test_scores']); ?></td>
                                <td><?php echo htmlspecialchars($requirement['additional']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="row mt-5">
            <div class="col-12 text-center" data-aos="fade-up">
                <div class="cta-section p-5 bg-light rounded shadow-sm">
                    <h3 class="mb-3">Ready to Start Your Journey?</h3>
                    <p class="lead mb-4">Take the first step towards your future by applying to our university.</p>
                    <a href="register.php" class="btn btn-primary btn-lg">
                        Apply Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

