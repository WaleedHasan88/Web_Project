<?php
require_once __DIR__ . '/../includes/header.php';
?>

<section class="registration-section py-5">
    <div class="container">
        <h2 class="text-center mb-4" data-aos="fade-down">University Registration</h2>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow" data-aos="fade-up">
                    <div class="card-body p-4">
                        <form action="process_registration.php" method="POST" enctype="multipart/form-data">
                            <!-- Personal Information -->
                            <h4 class="mb-3">Personal Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="dob" class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" id="dob" name="dob" required>
                            </div>

                            <!-- Academic Information -->
                            <h4 class="mb-3 mt-4">Academic Information</h4>
                            <div class="mb-3">
                                <label for="program" class="form-label">Program of Interest *</label>
                                <select class="form-select" id="program" name="program" required>
                                    <option value="">Select Program</option>
                                    <option value="undergraduate">Undergraduate</option>
                                    <option value="graduate">Graduate</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="major" class="form-label">Intended Major *</label>
                                <input type="text" class="form-control" id="major" name="major" required>
                            </div>

                            <div class="mb-3">
                                <label for="gpa" class="form-label">GPA *</label>
                                <input type="number" class="form-control" id="gpa" name="gpa" step="0.01" min="0" max="4.0" required>
                            </div>

                            <!-- Documents Upload -->
                            <h4 class="mb-3 mt-4">Required Documents</h4>
                            <div class="mb-3">
                                <label for="transcript" class="form-label">Academic Transcript *</label>
                                <input type="file" class="form-control" id="transcript" name="transcript" accept=".pdf,.doc,.docx" required>
                            </div>

                            <div class="mb-3">
                                <label for="testScores" class="form-label">Test Scores (SAT/ACT/GRE/GMAT) *</label>
                                <input type="file" class="form-control" id="testScores" name="testScores" accept=".pdf,.doc,.docx" required>
                            </div>

                            <div class="mb-3">
                                <label for="recommendation" class="form-label">Letters of Recommendation *</label>
                                <input type="file" class="form-control" id="recommendation" name="recommendation" accept=".pdf,.doc,.docx" required>
                            </div>

                            <!-- Additional Information -->
                            <h4 class="mb-3 mt-4">Additional Information</h4>
                            <div class="mb-3">
                                <label for="statement" class="form-label">Personal Statement *</label>
                                <textarea class="form-control" id="statement" name="statement" rows="4" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="extracurricular" class="form-label">Extracurricular Activities</label>
                                <textarea class="form-control" id="extracurricular" name="extracurricular" rows="3"></textarea>
                            </div>

                            <!-- Terms and Submit -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the terms and conditions and confirm that all information provided is accurate *
                                </label>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">Submit Application</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 