<?php
$menu = [
	'index.php'		=> 'Главная',
	'bulls.php'		=> 'Быки',
	'sales.php'		=> 'Продажи',
	'Справочники'	=> [
		'contractors.php' => 'Контрагенты',
		'buyers.php'	  => 'Покупатели',
		'packagings.php'  => 'Упаковки',
	],
];
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
						<?php foreach ($menu as $key => $item): ?>
							<?php if (is_array($item)): ?>
								<li class="nav-item dropdown">
									<a class="nav-link dropdown-toggle<?= isset($item[$page]) ? ' active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= htmlspecialchars($key) ?></a>
									<ul class="dropdown-menu">
										<?php foreach ($item as $file => $label): ?>
											<li>
												<a class="dropdown-item<?= $file === $page ? ' active' : '' ?>" href="<?= $file ?>"><?= htmlspecialchars($label) ?></a>
											</li>
										<?php endforeach; ?>
									</ul>
								</li>
							<?php else: ?>
								<li class="nav-item">
									<a class="nav-link<?= $key === $page ? ' active' : '' ?>" href="<?= $key ?>"><?= htmlspecialchars($item) ?></a>
								</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</nav>
	<main class="container-xl py-3 py-md-4">

