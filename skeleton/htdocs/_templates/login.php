<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= htmlspecialchars(get_config('project_title', 'App')) ?></title>
    <link rel="stylesheet" href="<?= get_config('base_path') ?>assets/css/index.css">
    <link rel="stylesheet" href="<?= get_config('base_path') ?>assets/css/toastv3.css">
</head>
<body>
    <div class="ball" id="ball"></div>

    <div class="auth-page">
        <div class="auth-card">
            <h2>Welcome Back</h2>
            <p style="margin-bottom: 24px;">Sign in to <?= htmlspecialchars(get_config('project_title', 'your account')) ?></p>

            <form id="login-form" novalidate>
                <div class="form-group">
                    <label for="login-user">Username or Email</label>
                    <input type="text" id="login-user" name="user" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                    Sign In
                </button>
            </form>

            <p style="margin-top:20px;text-align:center;font-size:14px;">
                Don't have an account? <a href="/signup">Register</a>
            </p>
        </div>
    </div>

    <div class="toast-panel" id="toast-container"></div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script type="module">
        import FingerprintJS from 'https://openfpcdn.io/fingerprintjs/v3';
        const fp = await FingerprintJS.load();
        const r  = await fp.get();
        document.cookie = `fingerprint=${r.visitorId}; path=/; SameSite=None; Secure`;
    </script>
    <script src="<?= get_config('base_path') ?>assets/js/toastv3.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/ball.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/apis.js"></script>
</body>
</html>
