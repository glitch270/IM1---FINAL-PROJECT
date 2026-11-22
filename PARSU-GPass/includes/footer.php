<?php
/**
 * Global Footer Template
 * Expects optional array: $customJs
 */
$role = $_SESSION['account_role'] ?? null;
?>
    <?php if ($role): ?>
                </main>
                <footer class="app-footer">
                    <div class="footer-container">
                        <p>&copy; 2026 Partido State University - Online Vehicle Gate Pass System. All rights reserved.</p>
                    </div>
                </footer>
            </div><!-- /.app-main-content -->
        </div><!-- /.app-wrapper -->
    <?php else: ?>
        <footer class="public-footer">
            <div class="footer-container">
                <p>&copy; 2026 Partido State University - Online Vehicle Gate Pass System. All rights reserved.</p>
            </div>
        </footer>
    <?php endif; ?>

    <!-- Core Scripts -->
    <script src="<?= BASE_URL ?>/assets/js/notifications.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>

    <?php if (isset($customJs) && is_array($customJs)): ?>
        <?php foreach ($customJs as $jsFile): ?>
            <script src="<?= BASE_URL ?>/assets/js/<?= e($jsFile) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

