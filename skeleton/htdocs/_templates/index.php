<section class="public-home">
    <div class="hero">
        <h1>Welcome to <span><?= htmlspecialchars(get_config('project_title', 'Your App')) ?></span></h1>
        <p class="hero__sub">Built on the Custom PHP Framework</p>
        <div class="hero__actions">
            <a href="/posts" class="btn-primary">View Posts</a>
            <?php if (!Session::isAuthenticated()): ?>
            <a href="/login" class="btn-secondary">Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>
