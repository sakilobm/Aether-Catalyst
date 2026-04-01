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
        if (Session::$isError) {
            Session::loadTemplate('core/_error');
        } else {
            Session::loadTemplate(Session::currentScript());
        }
        ?>
    </main>

    <?php Session::loadTemplate('core/_footer'); ?>
    <?php Session::loadTemplate('core/_toastv3'); ?>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- GSAP for Ball cursor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <!-- FingerprintJS for session binding -->
    <script type="module">
        import FingerprintJS from 'https://openfpcdn.io/fingerprintjs/v3';
        const fp = await FingerprintJS.load();
        const result = await fp.get();
        document.cookie = `fingerprint=${result.visitorId}; path=/; SameSite=None; Secure`;
    </script>
    <!-- Framework JS -->
    <script src="<?= get_config('base_path') ?>assets/js/toastv3.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/ball.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/apis.js"></script>
</body>
</html>
