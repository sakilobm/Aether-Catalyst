<section class="dashboard-stats">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p class="stat-value"><?= Session::countAllUsers() ?></p>
    </div>
    <!-- Add more stat cards via forge.php project customization -->
</section>

<section class="dashboard-welcome">
    <h2>Welcome, <?= htmlspecialchars(Session::getUser() ? Session::getUser()->getUsername() : 'Admin') ?></h2>
    <p>Use the sidebar to manage your content.</p>
</section>
