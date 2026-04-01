<nav class="core-nav" id="core-nav">
    <div class="nav__brand">
        <a href="/"><?= htmlspecialchars(get_config('project_title', 'Framework')) ?></a>
    </div>
    <ul class="nav__links" id="nav-links">
        <li><a href="/" class="nav-item <?= Session::currentScript() === 'index' ? 'active' : '' ?>">Home</a></li>
        <li><a href="/posts" class="nav-item <?= Session::currentScript() === 'posts' ? 'active' : '' ?>">Posts</a></li>
        <?php if (Session::isAuthenticated()): ?>
        <li><a href="/admin" class="nav-item">Dashboard</a></li>
        <li><a href="/?logout=1" class="nav-item">Logout</a></li>
        <?php else: ?>
        <li><a href="/login" class="nav-item">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>
