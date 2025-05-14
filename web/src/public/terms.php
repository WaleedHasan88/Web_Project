<?php
require_once __DIR__ . '/../includes/header.php';

// Define terms sections
$termsSections = [
    'acceptance' => [
        'title' => 'Acceptance of Terms',
        'content' => 'By accessing and using the Erbil Polytechnic University portal, you agree to be bound by these Terms of Use and all applicable laws and regulations.'
    ],
    'accounts' => [
        'title' => 'User Accounts',
        'content' => 'To access certain features of the portal, you must maintain an active account. You are responsible for:',
        'items' => [
            'Maintaining the confidentiality of your account credentials',
            'All activities that occur under your account',
            'Notifying us immediately of any unauthorized use'
        ]
    ],
    'acceptable_use' => [
        'title' => 'Acceptable Use',
        'content' => 'You agree not to:',
        'items' => [
            'Use the portal for any illegal purpose',
            'Share your account credentials with others',
            'Attempt to gain unauthorized access to any part of the portal',
            'Interfere with or disrupt the portal\'s operation'
        ]
    ],
    'academic_integrity' => [
        'title' => 'Academic Integrity',
        'content' => 'Users must:',
        'items' => [
            'Provide accurate academic information',
            'Maintain academic honesty',
            'Follow university policies and procedures'
        ]
    ],
    'intellectual_property' => [
        'title' => 'Intellectual Property',
        'content' => 'All content on the portal is protected by copyright and other intellectual property rights. Users may not:',
        'items' => [
            'Copy or reproduce portal content without permission',
            'Modify or create derivative works',
            'Use content for commercial purposes'
        ]
    ],
    'termination' => [
        'title' => 'Termination',
        'content' => 'We reserve the right to:',
        'items' => [
            'Suspend or terminate accounts for violations',
            'Modify or discontinue services',
            'Update these terms at any time'
        ]
    ],
    'contact' => [
        'title' => 'Contact Information',
        'content' => 'For questions about these Terms of Use, please contact:',
        'contact_info' => [
            'email' => 'legal@epu.edu',
            'phone' => '+1234567890'
        ]
    ]
];
?>

<section class="terms-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg" data-aos="fade-up">
                    <div class="card-body p-5">
                        <h1 class="text-center mb-4">Terms of Use</h1>
                        
                        <div class="content">
                            <?php foreach ($termsSections as $key => $section): ?>
                                <div class="terms-section mb-4" data-aos="fade-up">
                                    <h2 class="mb-3">
                                        <i class="fas fa-gavel text-primary me-2"></i>
                                        <?php echo htmlspecialchars($section['title']); ?>
                                    </h2>
                                    
                                    <p class="mb-3"><?php echo htmlspecialchars($section['content']); ?></p>

                                    <?php if (isset($section['items'])): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($section['items'] as $item): ?>
                                                <li class="list-group-item bg-transparent">
                                                    <i class="fas fa-check text-primary me-2"></i>
                                                    <?php echo htmlspecialchars($item); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <?php if (isset($section['contact_info'])): ?>
                                        <div class="contact-info mt-3">
                                            <?php foreach ($section['contact_info'] as $method => $value): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-<?php echo $method === 'email' ? 'envelope' : 'phone'; ?> text-primary me-2"></i>
                                                    <?php echo htmlspecialchars($value); ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>