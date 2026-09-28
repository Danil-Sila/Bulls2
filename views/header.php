<?php

$menu = ['index.php' => 'Главная',];
?>

<!doctype html>
<html lang="ru">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?= htmlspecialchars($title) ?> — Bulls Ctrl</title>
		<link rel="stylesheet" href="vendor/bootstrap.min.css">
		<link rel="stylesheet" href="css/app.css">
	</head>
	<body class="bg-body-tertiary">
		<nav class="navbar navbar-expand-md bg-primary" data-bs-theme="dark">
			<div class="container-xl">
				<a class="navbar-brand fw-semibold" href="index.php">Bulls Ctrl</a>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse"
				data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Меню">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="mainNav">
					<ul class="navbar-nav">
						<?php foreach ($menu as $file => $label): ?>
							<li class="nav-item">
								<a class="nav-link<?= $file === $page ? ' active' : '' ?>" href="<?= $file ?>"><?= htmlspecialchars($label) ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</nav>
	<main class="container-xl py-3 py-md-4">

