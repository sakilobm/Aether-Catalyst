<div class="error-container">
    <h1>Page Not Found</h1>
    <p><?= isset($errorMsg) ? htmlspecialchars($errorMsg) : 'The requested page could not be found.' ?></p>
    <a href="/" class="btn-primary">Go Home</a>
</div>
