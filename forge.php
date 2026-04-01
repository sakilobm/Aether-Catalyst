<?php

/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║             forge.php — Custom PHP Framework Project CLI             ║
 * ║──────────────────────────────────────────────────────────────────────║
 * ║  Usage: php forge.php                                                ║
 * ║  What it does:                                                        ║
 * ║    1. Prompts for Project Name, DB credentials, base path            ║
 * ║    2. Copies the skeleton/ directory to projects/{name}/             ║
 * ║    3. Writes a secure config.json (git-ignored)                       ║
 * ║    4. Creates the MySQL database and runs base.sql                   ║
 * ║    5. Generates an AI Sub-Prompt for project-specific code           ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    die("forge.php must be run from the command line.\n");
}

// ─── ANSI Color Helpers ───────────────────────────────────────────────────────

function clr(string $text, string $color): string
{
    $colors = [
        'red'    => "\033[31m",
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'cyan'   => "\033[36m",
        'white'  => "\033[97m",
        'bold'   => "\033[1m",
        'reset'  => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function info(string $msg): void    { echo clr("  i  $msg\n", 'cyan'); }
function success(string $msg): void { echo clr("  +  $msg\n", 'green'); }
function warn(string $msg): void    { echo clr("  !  $msg\n", 'yellow'); }
function fwError(string $msg): void { echo clr("  x  $msg\n", 'red'); }

function ask(string $prompt, string $default = ''): string
{
    $display = $default
        ? clr("{$prompt} [{$default}]", 'white')
        : clr($prompt, 'white');
    echo "\n  {$display}: ";
    $input = trim(fgets(STDIN));
    return $input !== '' ? $input : $default;
}

function askSecret(string $prompt): string
{
    echo "\n  " . clr($prompt, 'white') . ": ";
    if (PHP_OS_FAMILY !== 'Windows') {
        system('stty -echo');
        $input = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $input = trim(fgets(STDIN));
    }
    return $input;
}

function confirm(string $prompt): bool
{
    echo "\n  " . clr("{$prompt} [y/N]", 'yellow') . ": ";
    $input = strtolower(trim(fgets(STDIN)));
    return in_array($input, ['y', 'yes'], true);
}

// ─── Banner ────────────────────────────────────────────────────────────────────

echo clr("\n+================================================+\n", 'cyan');
echo clr("|    Custom PHP Framework  -  forge.php          |\n", 'cyan');
echo clr("+================================================+\n", 'cyan');

// ─── Phase 1: Gather Inputs ───────────────────────────────────────────────────

echo clr("\n  STEP 1 -- Project Information\n", 'bold');

$projectTitle = ask('Project Title (e.g. "Blog Engine")', '');
while (empty($projectTitle)) {
    fwError('Project title cannot be empty.');
    $projectTitle = ask('Project Title');
}

// Derive slug: "My Blog Engine" -> "my_blog_engine"
$projectSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($projectTitle)));
$projectSlug = trim($projectSlug, '_');
info("Slug: {$projectSlug}");

echo clr("\n  STEP 2 -- Database Credentials\n", 'bold');

$dbHost = ask('Database Host',     'localhost');
$dbPort = ask('Database Port',     '3306');
$dbUser = ask('Database Username', 'root');
$dbPass = askSecret('Database Password');
$dbName = ask('Database Name',     $projectSlug);

echo clr("\n  STEP 3 -- Server Paths\n", 'bold');

$basePath   = ask('Base Path (URL prefix, e.g. /my_project/htdocs/)', "/{$projectSlug}/htdocs/");
$uploadPath = ask('File Upload Path (absolute server path)',           "/var/www/uploads/{$projectSlug}/");

echo clr("\n  STEP 4 -- Project Meta\n", 'bold');

$metaDesc   = ask('Meta Description', 'A web app built with the Custom PHP Framework.');
$metaAuthor = ask('Meta Author',      'Your Name');

// ─── Phase 2: Scaffolding ─────────────────────────────────────────────────────

echo clr("\n  STEP 5 -- Scaffolding Project\n", 'bold');

$skeletonDir = __DIR__ . '/skeleton';
$targetDir   = __DIR__ . '/projects/' . $projectSlug;

if (!is_dir($skeletonDir)) {
    fwError("Skeleton directory not found at: {$skeletonDir}");
    fwError("Ensure skeleton/ exists alongside forge.php.");
    exit(1);
}

if (is_dir($targetDir)) {
    fwError("Project already exists: {$targetDir}");
    if (!confirm("Overwrite it?")) {
        info("Aborted.");
        exit(0);
    }
    removeDir($targetDir);
}

info("Copying skeleton -> {$targetDir}");
copyDir($skeletonDir, $targetDir);
success("Project directory created.");

// ─── Phase 3: Write config.json ──────────────────────────────────────────────

echo clr("\n  STEP 6 -- Writing config.json\n", 'bold');

$config = [
    'db_server'        => $dbHost,
    'db_username'      => $dbUser,
    'db_password'      => $dbPass,
    'db_name'          => $dbName,
    'base_path'        => $basePath,
    'upload_path'      => $uploadPath,
    'upload_path_pdf'  => $uploadPath . 'pdf/',
    'project_title'    => $projectTitle,
    'meta_description' => $metaDesc,
    'meta_author'      => $metaAuthor,
    'rabbitmq_host'    => 'localhost',
    'rabbitmq_port'    => '5672',
    'rabbitmq_user'    => 'guest',
    'rabbitmq_pass'    => 'guest',
    'rabbitmq_vhost'   => '/',
];

$configPath = $targetDir . '/project/config.json';
@mkdir(dirname($configPath), 0755, true);

if (file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    success("config.json written to: {$configPath}");
    warn("This file is git-ignored. NEVER commit it.");
} else {
    fwError("Failed to write config.json!");
}

// ─── Phase 4: Database Creation ──────────────────────────────────────────────

echo clr("\n  STEP 7 -- Initializing Database\n", 'bold');

$dbCreated = false;

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $safeDb = preg_replace('/[^a-zA-Z0-9_]/', '', $dbName);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;");
    $pdo->exec("USE `{$safeDb}`;");
    success("Database `{$dbName}` created.");

    $baseSql = $targetDir . '/htdocs/db/base.sql';
    if (file_exists($baseSql)) {
        $sql = file_get_contents($baseSql);
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            static fn($s) => strlen(trim($s)) > 0
        );

        foreach ($statements as $stmt) {
            try {
                $pdo->exec($stmt . ';');
            } catch (PDOException $e) {
                warn("SQL Warning: " . $e->getMessage());
            }
        }
        success("Base schema (auth, users, session, posts) applied.");
        $dbCreated = true;
    } else {
        warn("base.sql not found -- run migrations manually.");
    }

} catch (PDOException $e) {
    warn("Database setup skipped: " . $e->getMessage());
    warn("Create the database manually and run: htdocs/db/base.sql");
}

// ─── Phase 5: Generate AI Sub-Prompt ─────────────────────────────────────────

echo clr("\n  STEP 8 -- Generating AI Sub-Prompt\n", 'bold');

$aiPrompt   = buildAiPrompt($projectTitle, $projectSlug, $basePath, $dbName, $dbCreated);
$promptPath = $targetDir . '/AI_PROMPT.md';
file_put_contents($promptPath, $aiPrompt);
success("AI sub-prompt saved to: {$promptPath}");

// ─── Done ─────────────────────────────────────────────────────────────────────

echo clr("\n+================================================+\n", 'green');
echo clr("|   Project '{$projectTitle}' is ready!          \n", 'green');
echo clr("+================================================+\n", 'green');

echo clr("\n  Next Steps:\n", 'bold');
echo "    1. cd projects/{$projectSlug}/htdocs\n";
echo "    2. composer install\n";
echo "    3. Point Apache DocumentRoot -> projects/{$projectSlug}/htdocs\n";
echo "    4. config.json already created at project/config.json\n";
echo "    5. Open AI_PROMPT.md and paste it into Claude / ChatGPT\n\n";

// ─── Helper Functions ─────────────────────────────────────────────────────────

function copyDir(string $src, string $dst): void
{
    @mkdir($dst, 0755, true);
    $items = scandir($src);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        // Skip temporary extraction folders from the source archives
        $skipFolders = ['new_fw', 'php_class', 'new_framework_extracted', 'php_class_extracted'];
        if (in_array($item, $skipFolders, true)) continue;
        $srcPath = $src . DIRECTORY_SEPARATOR . $item;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $item;
        if (is_dir($srcPath)) {
            copyDir($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
}

function removeDir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? removeDir($path) : unlink($path);
    }
    rmdir($dir);
}

function buildAiPrompt(string $title, string $slug, string $basePath, string $dbName, bool $dbCreated): string
{
    $date     = date('Y-m-d');
    $dbStatus = $dbCreated ? 'Created with base schema' : 'Create manually';

    // Nowdoc (single-quoted label) = zero PHP interpolation inside
    $framework = <<<'STATIC'
## Framework Context (MUST READ)

You are generating PHP code for a **custom-built PHP framework**.
Study every rule before writing code.

### 1. Bootstrapping — The Only Rule That Matters First
Every PHP page starts with **exactly this one line**:
```php
<?php
require_once 'libs/load.php';
```
This single include loads all classes, reads config.json, connects to MySQL,
and initiates the user session. Do NOT add extra require/include lines.

### 2. Core Class Reference

| Class | Static Methods | Instance Methods |
|-------|---------------|-----------------|
| `Database` | `getConnection(): mysqli` | — |
| `Session` | `isAuthenticated()`, `ensureLogin()`, `loadTemplate($name, $data=[])`, `renderPage()`, `renderPageOfAdmin()`, `renderPageLogin()`, `renderPageRegister()`, `getUser()`, `countAllUsers()`, `get($key)`, `set($key, $val)` | — |
| `UserSession` | `authenticate($user, $pass, $fp)`, `authorize($token)` | `isValid()`, `isActive()`, `removeSession()`, `deactivate()`, `getUser()` |
| `User` | `signup($user, $pass, $email, $phone)`, `login($user, $pass)` | `new User($id_or_username_or_email)`, then `->getEmail()`, `->getUsername()` etc. |
| `API` | — | `processApi()`, `isAuthenticated()`, `paramsExists($keys)`, `response($json, $code)`, `json($array)` |

### 3. Template Engine

`Session::loadTemplate($name, $data = [])` includes `_templates/{name}.php`.
Variables in `$data` are extracted into local scope inside the template.

Directory structure:
```
_templates/
├── _master.php            <- Public wrapper (Ball cursor + GSAP + toastv3)
├── _masterForAdmin.php    <- Admin wrapper (sidebar + navbar injection)
├── core/
│   ├── _head.php          <- <head> content for public pages
│   ├── _nav.php           <- Public navigation bar
│   ├── _footer.php        <- Footer
│   ├── _toastv3.php       <- Toast panel container
│   └── _error.php         <- Error state display
├── admin/
│   ├── _head.php          <- <head> content for admin
│   ├── _nav.php           <- Sidebar navigation
│   ├── _toastv3.php       <- Toast panel container
│   └── dashboard.php      <- Default admin page (loaded by current_page=dashboard)
├── login.php              <- Standalone login page
├── signup.php             <- Standalone signup page
└── index.php              <- Public homepage content
```

The `_masterForAdmin.php` auto-loads `_templates/admin/{current_page}.php`
where `current_page` comes from `$_GET['current_page']` (default: 'dashboard').

### 4. API Endpoint File Pattern

Location: `libs/api/{namespace}/{method}.php`
URL:       `POST /api/{namespace}/{method}`

The file MUST define a PHP closure assigned to a variable named after the file:

```php
<?php
// File: libs/api/post/create.php -> accessible at POST /api/post/create

${basename(__FILE__, '.php')} = function () {

    // Check auth for write operations
    if (!$this->isAuthenticated()) {
        $this->response($this->json(['message' => 'Unauthorized']), 401);
    }

    // Validate required POST params
    if ($this->paramsExists(['title', 'content'])) {

        $title   = $this->_request['title'];   // already sanitized (strip_tags + trim)
        $content = $this->_request['content'];

        // Call your App class
        $result = MyModel::create($title, $content);

        // OR return an HTML fragment for AJAX injection:
        // Session::loadTemplate('partials/my_card', ['item' => $result]);

        $this->response($this->json(['message' => 'Created', 'id' => $result]), 201);

    } else {
        $this->response($this->json(['message' => 'Bad request']), 400);
    }
};
```

### 5. App Model Class Pattern (ORM)

```php
<?php
include_once __DIR__ . '/../traits/SQLGetterSetter.trait.php';
use Carbon\Carbon;

class MyModel
{
    use SQLGetterSetter;    // provides ->getXxx(), ->setXxx(), ->delete()

    // These three MUST be set in __construct for the trait to work
    public int    $id;
    public string $table = 'my_table';
    public \mysqli $conn;

    // ── Static Factory Methods ──────────────────────────────────────────────

    public static function create(string $field1, string $field2): array
    {
        $author = Session::getUser()->getEmail();
        $db     = Database::getConnection();
        $stmt   = $db->prepare(
            "INSERT INTO `my_table` (`field1`, `field2`, `owner`, `created_at`) VALUES (?, ?, ?, NOW())"
        );
        $stmt->bind_param('sss', $field1, $field2, $author);
        if ($stmt->execute()) {
            return ['status' => 'success', 'id' => $db->insert_id];
        }
        return ['status' => 'error'];
    }

    public static function getAll(): array|false
    {
        $db     = Database::getConnection();
        $result = $db->query("SELECT * FROM `my_table` ORDER BY `created_at` DESC");
        return $result->num_rows > 0 ? iterator_to_array($result) : false;
    }

    // ── Constructor ─────────────────────────────────────────────────────────

    public function __construct(int $id)
    {
        $this->id    = $id;
        $this->conn  = Database::getConnection();
        $this->table = 'my_table';
        // Now $this->getField1() etc. all work via the trait
    }
}
```

### 6. Security Checklist
- ALWAYS: `get_config('key')` for any credential or path — never hardcode
- ALWAYS: `Session::ensureLogin()` at the top of any protected PHP page
- ALWAYS: `$this->isAuthenticated()` check in any write API endpoint
- ALWAYS: `$conn->prepare()` with `bind_param()` for user-supplied SQL values
- NEVER:  hardcode passwords, API keys, or server paths

### 7. Toast Notification System (JavaScript)
```javascript
// Types: 'success', 'error', 'warning', 'help'
showToast('success', 'Saved!',   'Post published successfully.');
showToast('error',   'Failed',   'Could not connect to server.');
showToast('warning', 'Careful',  'This will delete all records.');
showToast('help',    'Tip',      'Press Ctrl+S to save quickly.');

// Convenience aliases (same API):
toast.success('Title', 'Message body');
toast.error('Title', 'Message body');
```
Toast panel container is in every layout via `Session::loadTemplate('core/_toastv3')`.

### 8. AJAX Client (apis.js)
```javascript
// GET  /api/{namespace}/{action}
ApiClient.get('posts', 'count').then(data => console.log(data));

// POST /api/{namespace}/{action}  with FormData
ApiClient.post('post', 'create', { title: 'Hello', content: '...' })
  .then(data => toast.success('Done', data.message))
  .catch(err => toast.error('Error', err.message));
```

### 9. All config.json Keys
```json
{
    "db_server":        "...",
    "db_username":      "...",
    "db_password":      "...",
    "db_name":          "...",
    "base_path":        "/project/htdocs/",
    "upload_path":      "/var/www/uploads/",
    "upload_path_pdf":  "/var/www/uploads/pdf/",
    "project_title":    "...",
    "meta_description": "...",
    "meta_author":      "...",
    "rabbitmq_host":    "localhost",
    "rabbitmq_port":    "5672",
    "rabbitmq_user":    "guest",
    "rabbitmq_pass":    "guest",
    "rabbitmq_vhost":   "/"
}
```
Use in PHP: `get_config('db_name')`, `get_config('base_path')`, etc.
Use in templates: `<?= get_config('base_path') ?>assets/css/index.css`
STATIC;

    // Dynamic project-specific section (regular double-quoted string)
    $dynamic = "# AI Sub-Prompt: Generate Code for \"{$title}\"\n"
        . "**Generated by forge.php on {$date}**\n\n---\n\n"
        . $framework
        . "\n\n---\n\n"
        . "## YOUR TASK: Build \"{$title}\"\n\n"
        . "**Project Details:**\n"
        . "- **Title:** {$title}\n"
        . "- **Slug / DB Name:** {$slug} / {$dbName}\n"
        . "- **Base Path:** {$basePath}\n"
        . "- **Database Status:** {$dbStatus}\n\n"
        . "### Files to Generate:\n\n"
        . "#### A. `htdocs/db/{$slug}.sql`\n"
        . "SQL schema extending the base tables (auth, users, session, posts already exist).\n"
        . "Add custom tables for \"{$title}\"'s unique features.\n"
        . "Use FK references to `auth.id` for ownership. Use utf8mb4.\n\n"
        . "#### B. `libs/app/{Model}.class.php`\n"
        . "ORM model class for the core domain object of \"{$title}\".\n"
        . "Follow the pattern exactly: `use SQLGetterSetter`, static factory methods,\n"
        . "constructor sets `\$id`, `\$conn`, `\$table`.\n\n"
        . "#### C. API endpoints in `libs/api/{namespace}/`\n"
        . "Closure-based endpoints for: create, list, update, delete.\n"
        . "Authenticate writes with `\$this->isAuthenticated()`.\n\n"
        . "#### D. `_templates/index.php`\n"
        . "Dark-themed public landing page. Use CSS vars from index.css.\n"
        . "Animate with GSAP if suitable. The Ball cursor is already available.\n\n"
        . "#### E. `_templates/admin/{page}.php`\n"
        . "Admin management page. Loaded by `?current_page={page}` on `/admin`.\n"
        . "Show stats, CRUD forms, and use `showToast()` for feedback.\n\n"
        . "#### F. CSS additions (append to `assets/css/index.css`)\n"
        . "Only project-specific styles. Reuse the existing CSS variables.\n\n"
        . "#### G. JS additions (new `assets/js/{feature}.js` if needed)\n"
        . "Any AJAX calls using `ApiClient.post()` / `ApiClient.get()`.\n\n"
        . "---\n\n"
        . "**CRITICAL:** Do NOT invent new framework patterns. Use only the exact class\n"
        . "names, method signatures, and file structures documented above.\n";

    return $dynamic;
}
