<?php
require_once __DIR__ . '/../includes/header.php';

// Define privacy policy sections
$privacyPolicySections = [
    'information_collection' => [
        'title' => 'Information We Collect',
        'content' => 'We collect information that you provide directly to us, including:',
        'items' => [
            'Personal identification information (name, email address, student ID)',
            'Academic information (courses, grades, enrollment status)',
            'Account credentials'
        ]
    ],
    'information_usage' => [
        'title' => 'How We Use Your Information',
        'content' => 'We use the information we collect to:',
        'items' => [
            'Provide and maintain our services',
            'Process your academic records',
            'Send you important updates and notifications',
            'Improve our services'
        ]
    ],
    'security' => [
        'title' => 'Information Security',
        'content' => 'We implement appropriate security measures to protect your personal information, including:',
        'items' => [
            'Encryption of sensitive data',
            'Regular security assessments',
            'Access controls and authentication'
        ]
    ],
    'sharing' => [
        'title' => 'Information Sharing',
        'content' => 'We do not sell or share your personal information with third parties except:',
        'items' => [
            'When required by law',
            'With your explicit consent',
            'For academic purposes within the university'
        ]
    ],
    'rights' => [
        'title' => 'Your Rights',
        'content' => 'You have the right to:',
        'items' => [
            'Access your personal information',
            'Correct inaccurate information',
            'Request deletion of your information',
            'Opt-out of communications'
        ]
    ],
    'contact' => [
        'title' => 'Contact Us',
        'content' => 'If you have any questions about this Privacy Policy, please contact us at:',
        'contact_info' => [
            'email' => 'privacy@epu.edu',
            'phone' => '+1234567890'
        ]
    ]
];
?>

<section class="privacy-section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-lg" data-aos="fade-up">
                    <div class="card-body p-5">
                        <h1 class="text-center mb-4">Privacy Policy</h1>
                        
                        <div class="content">
                            <?php foreach ($privacyPolicySections as $key => $section): ?>
                                <div class="policy-section mb-4" data-aos="fade-up">
                                    <h2 class="mb-3"><?php echo htmlspecialchars($section['title']); ?></h2>
                                    
                                    <?php if (isset($section['content'])): ?>
                                        <p><?php echo htmlspecialchars($section['content']); ?></p>
                                    <?php endif; ?>

                                    <?php if (isset($section['items'])): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($section['items'] as $item): ?>
                                                <li class="list-group-item bg-transparent">
                                                    <i class="fas fa-check-circle text-primary me-2"></i>
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

