</main>

<script src="vendor/bootstrap.bundle.min.js"></script>
<script src="api_js/api.js"></script>
<?php foreach ($scripts ?? [] as $script): ?>
	<script src="api_js/<?= $script ?>"></script>
<?php endforeach; ?>
</body>
</html>
