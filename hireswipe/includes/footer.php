</main>
<footer class="footer">© <?= date('Y') ?> <?= APP_NAME ?> · Swipe right on your next job or your next hire</footer>
<?php if (!empty($pageScripts)) foreach ($pageScripts as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
