<?php
use App\Core\Router;
$jsVersion = defined('ROOT_PATH') && file_exists(ROOT_PATH . '/public/js/app.js') 
    ? filemtime(ROOT_PATH . '/public/js/app.js') 
    : time();
?>
            </div><!-- .content-container -->
        </main><!-- .main-content -->

        <footer class="app-footer">
            <div class="footer-container">
                <p>&copy; <?= date('Y') ?> PropPulse PMS. All rights reserved. Self-Hosted Modern Real Estate ERP.</p>
                <div class="footer-meta">
                    <span>PHP <?= PHP_MAJOR_VERSION ?>.<?= PHP_MINOR_VERSION ?></span>
                    <span>&bull;</span>
                    <span>MySQL InnoDB</span>
                    <span>&bull;</span>
                    <span>Local Document Vault</span>
                </div>
            </div>
        </footer>
    </div><!-- .app-layout -->

    <script src="<?= Router::url('/js/app.js') ?>?v=<?= $jsVersion ?>"></script>
    <?php if (defined('ROOT_PATH') && file_exists(ROOT_PATH . '/public/js/pdf-import.js')): ?>
        <script src="<?= Router::url('/js/pdf-import.js') ?>?v=<?= filemtime(ROOT_PATH . '/public/js/pdf-import.js') ?>"></script>
    <?php endif; ?>
    <?php if (defined('TEST_MODE_AVAILABLE') && TEST_MODE_AVAILABLE && file_exists(ROOT_PATH . '/public/js/test-mode.js')): ?>
        <script src="<?= Router::url('/js/test-mode.js') ?>?v=<?= filemtime(ROOT_PATH . '/public/js/test-mode.js') ?>"></script>
    <?php endif; ?>
</body>
</html>
