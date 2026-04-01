<?php

/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║        Aether Catalyst — Project Automation & Blueprinting           ║
 * ║──────────────────────────────────────────────────────────────────────║
 * ║  Usage: php forge.php [command] [name]                               ║
 * ║  Commands:                                                           ║
 * ║    (none)           Interactive project setup                        ║
 * ║    make:controller   Generate a new API controller closure           ║
 * ║    make:model        Generate a new ORM class (libs/app/)            ║
 * ║    make:env          Create .env and config.json from template       ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    die("Aether Catalyst must be run from the command line.\n");
}

// ─── Direct Command Router ───────────────────────────────────────────────────

$command = $argv[1] ?? null;
$arg1    = $argv[2] ?? null;

if ($command) {
    switch ($command) {
        case 'make:controller':
            if (!$arg1) die("Usage: php forge.php make:controller [name]\n");
            generateController($arg1);
            exit(0);
        case 'make:model':
            if (!$arg1) die("Usage: php forge.php make:model [name]\n");
            generateModel($arg1);
            exit(0);
        case 'make:env':
            generateEnv();
            exit(0);
    }
}

// ─── Standard Forge (Interactive Setup) ───────────────────────────────────────

echo "\033[36m" . "Aether Catalyst — Evolutionary Framework Forge\n" . "\033[0m";

$title = ask('Project Title', 'New Project');
$slug  = slugify($title);
$user  = ask('Database User', 'root');
$pass  = askSecret('Database Pass');
$db    = ask('Database Name', $slug);

$target = __DIR__ . '/projects/' . $slug;
if (is_dir($target)) die("Project already exists at $target\n");

// 1. Scaffold
@mkdir($target, 0755, true);
copyDir(__DIR__ . '/skeleton', $target);
removeDir($target . '/new_framework_extracted'); // Clean

// 2. Generate .env and config.json
$config = [
    'db_server' => 'localhost',
    'db_username' => $user,
    'db_password' => $pass,
    'db_name' => $db,
    'project_title' => $title,
    'base_path' => "/$slug/htdocs/",
];
file_put_contents($target . '/project/config.json', json_encode($config, JSON_PRETTY_PRINT));

$dotenv = "DB_HOST=localhost\nDB_USER=$user\nDB_PASS=$pass\nDB_NAME=$db\n";
file_put_contents($target . '/.env', $dotenv);

// 3. Auto-Composer (Automation Upgrade)
echo "\nInstalling dependencies via Composer...\n";
chdir($target . '/htdocs');
passthru('composer install --no-interaction --quiet');

echo "\n\033[32mProject '$title' created successfully!\033[0m\n";

// ─── Blueprint Functions ──────────────────────────────────────────────────────

/** Generate controller closure stub */
function generateController(string $name): void
{
    $path = "libs/api/app/{$name}.php";
    $stub = <<<PHP
<?php
// Closure-based API endpoint: POST /api/app/{$name}
\${basename(__FILE__, '.php')} = function () {
    if (\$this->paramsExists(['key'])) {
        \$this->response(\$this->json(['status' => 'success', 'data' => \$this->_request['key']]), 200);
    } else {
        \$this->response(\$this->json(['error' => 'Missing key']), 400);
    }
};
PHP;
    if (file_put_contents($path, $stub)) {
        echo "Created Controller: $path\n";
    }
}

/** Generate ORM Model stub */
function generateModel(string $name): void
{
    $class = ucfirst($name);
    $table = strtolower($name) . 's';
    $path = "libs/app/{$class}.class.php";
    $stub = <<<PHP
<?php
include_once __DIR__ . '/../traits/SQLGetterSetter.trait.php';

class {$class} {
    use SQLGetterSetter;
    public int \$id;
    public string \$table = '{$table}';
    public \$conn;

    public function __construct(int \$id) {
        \$this->id = \$id;
        \$this->conn = Database::getConnection();
    }

    public static function create(array \$data): bool {
        // Logic handled by SQLGetterSetter or manual PDO
        return true;
    }
}
PHP;
    if (file_put_contents($path, $stub)) {
        echo "Created Model: $path\n";
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ask(string $q, string $d = ''): string {
    echo "  $q [$d]: ";
    $i = trim(fgets(STDIN));
    return $i ?: $d;
}

function askSecret(string $q): string {
    echo "  $q: ";
    return trim(fgets(STDIN)); // Simple CLI fallback
}

function slugify(string $t): string {
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $t));
}

function copyDir($s, $d) {
    @mkdir($d);
    foreach (scandir($s) as $f) {
        if ($f == '.' || $f == '..') continue;
        $sp = "$s/$f"; $dp = "$d/$f";
        is_dir($sp) ? copyDir($sp, $dp) : copy($sp, $dp);
    }
}

function removeDir($d) {
    if (!is_dir($d)) return;
    foreach (scandir($d) as $f) {
        if ($f == '.' || $f == '..') continue;
        $p = "$d/$f";
        is_dir($p) ? removeDir($p) : unlink($p);
    }
    rmdir($d);
}
