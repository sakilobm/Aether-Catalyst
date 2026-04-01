<?php use Aether\Session; ?>
<!doctype html>
<html lang="en">
<head>
    <?php Session::loadTemplate('admin/_head'); ?>
</head>
<body class="dark-mode">

    <!-- Admin Sidebar -->
    <?php Session::loadTemplate('admin/_nav'); ?>

    <div class="main-panel">

        <!-- Top Navbar -->
        <nav class="admin-navbar">
            <div class="admin-navbar__left">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                    <span></span><span></span><span></span>
                </button>
                <span class="admin-navbar__title">
                    <?= htmlspecialchars(get_config('project_title', 'Admin Dashboard')) ?>
                </span>
            </div>
            <div class="admin-navbar__right">
                <span class="admin-navbar__user">
                    <?php $u = Session::getUser(); echo $u ? htmlspecialchars($u->getUsername()) : 'Admin'; ?>
                </span>
                <a href="/admin?logout=1" class="btn-logout">Logout</a>
            </div>
        </nav>

        <!-- Page Content: Buffered inheritance content injected here -->
        <div class="content">
            <?php
            if (isset($content)) {
                echo $content;
            } else {
                // Fallback for non-inheritance legacy calls
                $page = Session::getCurrentPageIdentifier();
                Session::loadTemplate('admin/' . $page, ['user' => Session::getUser()]);
            }
            ?>
        </div>

    </div><!-- /.main-panel -->

    <?php Session::loadTemplate('admin/_toastv3'); ?>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/toastv3.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/admin_dashboard.js"></script>
    <script src="<?= get_config('base_path') ?>assets/js/apis.js"></script>

</body>
</html>
