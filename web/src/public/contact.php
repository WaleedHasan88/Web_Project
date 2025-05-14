<?php
require_once '../includes/header.php';

// Define contact information
$contactInfo = [
    'address' => '123 Karkuk St, Erbil 44001',
    'phone' => '+964-750-8439-887',
    'email' => 'info@epu.edu.iq',
    'map_src' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3229.933687614856!2d44.035703115185!3d36.14315918009245!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x400724cbed206bd9%3A0xd61b6fb13fa55519!2sErbil%20Polytechnic%20University!5e0!3m2!1sen!2sus!4v1677654321890!5m2!1sen!2sus'
];

// Form handling
$message = '';
$formData = [
    'name' => '',
    'email' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => sanitize_input($_POST['name'] ?? ''),
        'email' => sanitize_input($_POST['email'] ?? ''),
        'message' => sanitize_input($_POST['message'] ?? '')
    ];
    
    if ($formData['name'] && validate_email($formData['email']) && $formData['message']) {
        // Here you would typically send an email or store in database
        $message = '<div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            Thank you for your message. We will get back to you soon!
        </div>';
        // Clear form data after successful submission
        $formData = ['name' => '', 'email' => '', 'message' => ''];
    } else {
        $message = '<div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            Please fill in all fields correctly.
        </div>';
    }
}
?>

<section class="contact-section py-5">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-down">Contact Us</h2>
        
        <?php if ($message): ?>
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="contact-form-container bg-light p-4 rounded shadow-sm">
                    <form id="contactForm" method="POST" action="" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($formData['name']); ?>"
                                   required />
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Your Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($formData['email']); ?>"
                                   required />
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" 
                                      id="message" 
                                      name="message" 
                                      rows="5" 
                                      required><?php echo htmlspecialchars($formData['message']); ?></textarea>
                            <div class="invalid-feedback">Please enter your message.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-6" data-aos="fade-left">
                <div class="contact-info bg-light p-4 rounded shadow-sm">
                    <h4 class="mb-4">Get in Touch</h4>
                    <p class="lead mb-4">Have questions? We're here to help!</p>
                    <ul class="list-unstyled contact-details mb-4">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            <?php echo htmlspecialchars($contactInfo['address']); ?>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone me-2 text-primary"></i>
                            <?php echo htmlspecialchars($contactInfo['phone']); ?>
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope me-2 text-primary"></i>
                            <?php echo htmlspecialchars($contactInfo['email']); ?>
                        </li>
                    </ul>
                    <div class="map-container rounded overflow-hidden shadow-sm">
                        <iframe src="<?php echo htmlspecialchars($contactInfo['map_src']); ?>"
                                class="w-100"
                                height="300"
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Form validation script -->
<script>
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once '../includes/footer.php'; ?> 