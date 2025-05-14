    </div><!-- End content-wrapper -->
    <footer class="bg-dark text-white py-3 ">
        <div class="container">
            <div class="row d-flex justify-content-between align-items-center">
                <div class="col-md-6">
                    &copy; <?php echo date('Y'); ?> Erbil Polytechnic University. All rights reserved.
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo SITE_URL; ?>/privacy.php" class="text-white me-3">Privacy Policy</a>
                    <a href="<?php echo SITE_URL; ?>/terms.php" class="text-white">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?php echo SITE_URL; ?>/../assets/js/script.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 800,
        });
    </script>
    <?php if (isset($additionalScripts)) echo $additionalScripts; ?>
</body>
</html> 