<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(get_config('project_title', 'My App')) ?></title>
<meta name="description" content="<?= htmlspecialchars(get_config('meta_description', '')) ?>">
<meta name="author" content="<?= htmlspecialchars(get_config('meta_author', '')) ?>">

<!-- Favicon -->
<link rel="icon" href="<?= get_config('base_path') ?>assets/images/favicon.ico" type="image/x-icon">

<!-- Core CSS (dark theme + ball cursor) -->
<link rel="stylesheet" href="<?= get_config('base_path') ?>assets/css/index.css">

<!-- Toast notification CSS -->
<link rel="stylesheet" href="<?= get_config('base_path') ?>assets/css/toastv3.css">

<!-- Page-specific CSS (auto-loaded if /assets/css/{pagename}.css exists) -->
<?php
$pageCss = $_SERVER['DOCUMENT_ROOT'] . get_config('base_path') . 'assets/css/' . Session::currentScript() . '.css';
if (file_exists($pageCss)): ?>
<link rel="stylesheet" href="<?= get_config('base_path') ?>assets/css/<?= Session::currentScript() ?>.css">
<?php endif; ?>

<!-- Open Graph -->
<meta property="og:title" content="<?= htmlspecialchars(get_config('project_title', 'My App')) ?>">
<meta property="og:type" content="website">
