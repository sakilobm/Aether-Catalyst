<?php use Aether\Session; ?>
<!doctype html>
<html lang="en">
<head>
    <?php Session::loadTemplate('core/_head'); ?>
</head>

<body>
    <!-- Custom Ball Cursor (GSAP) -->
    <div class="ball" id="ball"></div>

    <?php Session::loadTemplate('core/_nav'); ?>

    <main id="main-content">
        <?php
        // Advanced Template Inheritance:
        // Individual views are buffered and injected here as $content.
        if (isset($content)) {
            echo $content;
        } else {
            // Fallback for non-inheritance legacy calls
            Session::loadTemplate(Session::currentScript());
        }
        ?>
    </main>

    <?php Session::loadTemplate('core/_footer'); ?>
    <?php Session::loadTemplate('core/_toastv3'); ?>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/toastv3.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/ball.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/apis.js"></script>
</body>
</html>
