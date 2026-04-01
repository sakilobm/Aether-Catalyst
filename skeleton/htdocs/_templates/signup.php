<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — <?= htmlspecialchars(get_config('project_title', 'App')) ?></title>
    <link rel="stylesheet" href="<?= get_config('base_path') ?>assets/css/index.css">
    <link rel="stylesheet" href="<?= get_config('base_path') ?>assets/css/toastv3.css">
</head>
<body>
    <div class="ball" id="ball"></div>

    <div class="auth-page">
        <div class="auth-card">
            <h2>Create Account</h2>
            <p style="margin-bottom: 24px;">Join <?= htmlspecialchars(get_config('project_title', 'us')) ?> today</p>

            <form id="signup-form" novalidate>
                <div class="form-group">
                    <label for="signup-username">Username</label>
                    <input type="text" id="signup-username" name="username" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="email_address" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="signup-phone">Phone</label>
                    <input type="tel" id="signup-phone" name="phone" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="password" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                    Create Account
                </button>
            </form>

            <p style="margin-top:20px;text-align:center;font-size:14px;">
                Already have an account? <a href="/login">Sign in</a>
            </p>
        </div>
    </div>

    <div class="toast-panel" id="toast-container"></div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/toastv3.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/ball.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/apis.js"></script>
</body>
</html>
