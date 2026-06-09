    <?php if (empty($hideFooter)): ?>
    <footer class="app-footer">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y'); ?> Sistem Reservasi Ruangan Kampus.</p>
            <p class="mb-0 text-secondary">Praktikum Pemrograman Web 1</p>
        </div>
    </footer>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('assets/js/app.js'); ?>"></script>
</body>
</html>
