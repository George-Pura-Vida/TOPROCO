<?php
declare(strict_types=1);

const FIRST_ADMIN_SETUP = 'ad63caba504a1c05ee0fcddff54c15bd39be845ebb4a7f92a099cd2a8177acd2 1794393468';

const PALETTES = [
    'ziva' => ['Smaragd a jantar', '#fbf8f0', '#fffcf6', '#f7e8d1', '#20352c', '#155e52', '#08654b', '#f7b83d'],
    'modra' => ['Modrá a měď', '#f4f8ff', '#ffffff', '#e1edff', '#1d2d46', '#16468a', '#1756b3', '#faad68'],
    'tepla' => ['Malina a broskev', '#fff7f4', '#ffffff', '#fce6db', '#3d2634', '#842556', '#98295c', '#ffad85'],
    'tmava' => ['Půlnoc a tyrkys', '#132335', '#1a3046', '#1c354b', '#f2f7ff', '#9de3ff', '#69d8c3', '#ffd377'],
];
const COLORS = [
    'bg' => 'Pozadí stránky', 'paper' => 'Pozadí karet', 'sand' => 'Pozadí sekcí',
    'text' => 'Text a nadpisy', 'link' => 'Odkazy', 'button' => 'Hlavní tlačítka',
    'active' => 'Aktivní prvky a vedlejší tlačítka',
];
const TYPE = [
    'font-body' => ['Velikost textu', 15, 21, 16, 1, 'px'],
    'heading-scale' => ['Velikost nadpisů', 85, 115, 100, 5, '%'],
    'body-weight' => ['Tloušťka textu', 400, 600, 400, 100, ''],
    'heading-weight' => ['Tloušťka nadpisů', 400, 700, 500, 100, ''],
];

function defaults(array $palette): array
{
    return array_combine(array_keys(COLORS), array_slice($palette, 1))
        + array_map(static fn(array $field): int => $field[3], TYPE);
}

function luminance(string $color): float
{
    $channels = array_map(static function (string $hex): float {
        $value = hexdec($hex) / 255;
        return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
    }, str_split(substr($color, 1), 2));
    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

function contrast(string $a, string $b): float
{
    $x = luminance($a);
    $y = luminance($b);
    return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

function readableInk(string $background): string
{
    return contrast('#ffffff', $background) >= contrast('#000000', $background) ? '#ffffff' : '#000000';
}

function validatePalette(array $input): ?array
{
    $palette = [];
    foreach (COLORS as $key => $label) {
        $value = $input[$key] ?? null;
        if (!is_string($value) || !preg_match('/\A#[0-9a-f]{6}\z/i', $value)) {
            return null;
        }
        $palette[$key] = strtolower($value);
    }
    foreach (TYPE as $key => [$label, $min, $max, $default, $step]) {
        $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max || ($value - $min) % $step !== 0) {
            return null;
        }
        $palette[$key] = $value;
    }
    return $palette;
}

function contrastIsReadable(array $palette): bool
{
    foreach (['bg', 'paper', 'sand'] as $surface) {
        foreach (['text', 'link'] as $ink) {
            if (contrast($palette[$surface], $palette[$ink]) < 4.5) {
                return false;
            }
        }
    }
    return true;
}

function cssVars(array $palette): array
{
    $vars = array_intersect_key($palette, COLORS);
    foreach (TYPE as $key => $field) {
        $vars[$key] = $key === 'heading-scale' ? (string) ($palette[$key] / 100) : $palette[$key] . $field[5];
    }
    foreach (['button', 'active'] as $key) {
        $vars[$key . '-ink'] = readableInk($palette[$key]);
    }
    return $vars;
}

function privateDirectory(bool $create = false): ?string
{
    $configured = getenv('TOPROCO_THEME_DIR');
    $home = getenv('HOME');
    if (!$home && function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
        $home = posix_getpwuid(posix_geteuid())['dir'] ?? '';
    }
    $candidate = $configured ?: ($home ? $home . '/.toproco-theme' : '');
    if ($candidate === '' || $candidate[0] !== '/') {
        return null;
    }
    $candidate = rtrim($candidate, '/');
    $parent = realpath(dirname($candidate));
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
    if ($parent === false || $documentRoot === false || is_link($candidate)) {
        return null;
    }
    $directory = $parent . '/' . basename($candidate);
    foreach ([$documentRoot, __DIR__] as $publicRoot) {
        if (str_starts_with($directory . '/', rtrim($publicRoot, '/') . '/')) {
            return null;
        }
    }
    if (in_array('public_html', explode('/', $directory), true)) {
        return null;
    }
    if ($create && !is_dir($directory)) {
        @mkdir($directory, 0700);
    }
    return is_dir($directory) ? realpath($directory) ?: null : null;
}

function savedPalette(?string $directory): array
{
    if ($directory !== null) {
        $data = @file_get_contents($directory . '/palette.json', false, null, 0, 8192);
        $decoded = $data === false ? null : json_decode($data, true);
        $palette = is_array($decoded) ? validatePalette($decoded) : null;
        if ($palette !== null && contrastIsReadable($palette)) {
            return $palette;
        }
    }
    return defaults(PALETTES['ziva']);
}

function saveJson(string $directory, string $filename, array $values): bool
{
    $temporary = @tempnam($directory, '.write-');
    if ($temporary === false || dirname($temporary) !== $directory) {
        if ($temporary !== false) {
            @unlink($temporary);
        }
        return false;
    }
    $data = json_encode($values, JSON_THROW_ON_ERROR) . "\n";
    $saved = @file_put_contents($temporary, $data) === strlen($data)
        && @rename($temporary, $directory . '/' . $filename);
    if (!$saved) {
        @unlink($temporary);
    }
    return $saved;
}

function readAdmin(?string $directory): array
{
    if ($directory === null) {
        return ['', ''];
    }
    $data = @file_get_contents($directory . '/admin.json', false, null, 0, 2048);
    if ($data !== false) {
        $account = json_decode($data, true);
        return [
            is_string($account['email'] ?? null) ? strtolower(trim($account['email'])) : '',
            is_string($account['hash'] ?? null) ? $account['hash'] : '',
        ];
    }
    return [
        strtolower(trim((string) @file_get_contents($directory . '/admin.email', false, null, 0, 320))),
        trim((string) @file_get_contents($directory . '/password.hash', false, null, 0, 1024)),
    ];
}

function hasAdminFiles(?string $directory): bool
{
    return $directory !== null && (file_exists($directory . '/admin.json') || file_exists($directory . '/password.hash'));
}

function installAdmin(string $directory, string $email, string $password): bool
{
    $lock = @fopen($directory . '/.admin-setup.lock', 'c');
    if ($lock === false) {
        return false;
    }
    try {
        if (!flock($lock, LOCK_EX)) {
            return false;
        }
        clearstatcache();
        return !hasAdminFiles($directory)
            && saveJson($directory, 'admin.json', ['email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
    } finally {
        fclose($lock);
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$directory = privateDirectory();
$palette = savedPalette($directory);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
header('X-Content-Type-Options: nosniff');
if (($_GET['css'] ?? null) === '1') {
    if ($method !== 'GET' && $method !== 'HEAD') {
        header('Allow: GET, HEAD', true, 405);
        exit;
    }
    header('Content-Type: text/css; charset=UTF-8');
    header('Cache-Control: public, max-age=0, must-revalidate');
    header('X-Robots-Tag: noindex');
    $css = ":root {\n";
    foreach (cssVars($palette) as $key => $value) {
        $css .= "  --$key: $value;\n";
    }
    $css .= "}\n";
    $etag = '"' . hash('sha256', $css) . '"';
    header('ETag: ' . $etag);
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
    } elseif ($method === 'GET') {
        echo $css;
    }
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$nonce = base64_encode(random_bytes(18));
header("Content-Security-Policy: default-src 'none'; style-src 'self' 'nonce-$nonce'; script-src 'nonce-$nonce'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'; img-src 'self'");
if ($method !== 'GET' && $method !== 'HEAD' && $method !== 'POST') {
    header('Allow: GET, HEAD, POST', true, 405);
    exit;
}
$https = isset($_SERVER['HTTPS']) && in_array(strtolower((string) $_SERVER['HTTPS']), ['on', '1'], true);
[$adminEmail, $hash] = readAdmin($directory);
$configured = $https && $directory !== null && is_writable($directory)
    && filter_var($adminEmail, FILTER_VALIDATE_EMAIL) !== false && password_get_info($hash)['algo'] !== null;
$setup = $https && ($_GET['setup'] ?? null) === '1' && !hasAdminFiles($directory);
$setupError = '';
if ($setup) {
    $setupConfig = getenv('TOPROCO_THEME_SETUP') ?: FIRST_ADMIN_SETUP;
    if (!preg_match('/\A([a-f0-9]{64}) ([0-9]+)\z/', $setupConfig, $setupParts) || time() >= (int) $setupParts[2]) {
        $setup = false;
        $setupError = 'Platnost aktivačního kódu vypršela. Účet lze nastavit na hostingu.';
    } elseif ($method === 'POST') {
        $key = $_POST['setup_key'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        if (!is_string($key) || strlen($key) > 128 || !hash_equals($setupParts[1], hash('sha256', trim($key)))) {
            http_response_code(403);
            $setupError = 'Aktivační kód se nepodařilo ověřit.';
        } elseif (!is_string($email) || strlen($email) > 254 || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)
            || !is_string($password) || strlen($password) < 16 || strlen($password) > 72 || str_contains($password, "\0")
            || $password !== ($_POST['confirm_password'] ?? null)) {
            http_response_code(422);
            $setupError = 'Zkontrolujte e-mail a shodná hesla o délce 16 až 72 bajtů.';
        } elseif (($directory = privateDirectory(true)) === null || !installAdmin($directory, strtolower(trim($email)), $password)) {
            http_response_code(500);
            $setupError = 'Účet se nepodařilo vytvořit. Pokud už je aktivní, použijte přihlášení. Jinak zkontrolujte soukromou složku na hostingu.';
        } else {
            header('Location: /vzhled.php', true, 303);
            exit;
        }
    }
}
$sessionReady = $configured && session_start([
    'name' => 'ToprocoVzhled', 'use_strict_mode' => 1, 'use_only_cookies' => 1,
    'cookie_path' => '/vzhled.php', 'cookie_secure' => true, 'cookie_httponly' => true, 'cookie_samesite' => 'Strict',
]);
$message = '';
$error = $setupError;
$authenticated = false;
if ($sessionReady) {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    $fingerprint = hash('sha256', $adminEmail . "\0" . $hash);
    $authenticated = hash_equals($fingerprint, $_SESSION['admin'] ?? '') && time() - ($_SESSION['last'] ?? 0) < 1800;
    if ($authenticated) {
        $_SESSION['last'] = time();
    } else {
        unset($_SESSION['admin'], $_SESSION['last']);
    }
    if ($method === 'POST') {
        $token = $_POST['csrf'] ?? null;
        $action = $_POST['action'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(403);
            $error = 'Platnost formuláře vypršela. Načtěte stránku a zkuste to znovu.';
        } elseif (!$authenticated && $action === 'login') {
            $email = $_POST['email'] ?? null;
            $password = $_POST['password'] ?? null;
            if (time() < ($_SESSION['retry_after'] ?? 0)) {
                http_response_code(429);
                $error = 'Počkejte chvíli před dalším pokusem.';
            } elseif (is_string($password) && strlen($password) <= 72 && password_verify($password, $hash)
                && is_string($email) && strlen($email) <= 254 && hash_equals($adminEmail, strtolower(trim($email)))) {
                session_regenerate_id(true);
                $_SESSION = ['admin' => $fingerprint, 'last' => time(), 'csrf' => bin2hex(random_bytes(32))];
                header('Location: /vzhled.php', true, 303);
                exit;
            } else {
                $_SESSION['retry_after'] = time() + 3;
                $error = 'E-mail nebo heslo nesouhlasí.';
            }
        } elseif ($authenticated && $action === 'logout') {
            $_SESSION = [];
            session_destroy();
            setcookie(session_name(), '', ['expires' => 1, 'path' => '/vzhled.php', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
            header('Location: /vzhled.php', true, 303);
            exit;
        } elseif ($authenticated && $action === 'save') {
            $submitted = validatePalette($_POST);
            if ($submitted === null) {
                http_response_code(422);
                $error = 'Zkontrolujte vybrané barvy a hodnoty písma.';
            } elseif (!contrastIsReadable($submitted)) {
                http_response_code(422);
                $palette = $submitted;
                $error = 'Zvyšte kontrast textu a odkazů vůči všem třem pozadím na alespoň 4,5 : 1.';
            } elseif (!saveJson($directory, 'palette.json', $submitted)) {
                http_response_code(500);
                $palette = $submitted;
                $error = 'Nastavení se nepodařilo uložit. Zkontrolujte práva soukromé složky na hostingu.';
            } else {
                $_SESSION['saved'] = true;
                header('Location: /vzhled.php', true, 303);
                exit;
            }
        } else {
            http_response_code(403);
            $error = 'Pro uložení vzhledu se přihlaste.';
        }
    }
    if ($authenticated && !empty($_SESSION['saved'])) {
        $message = 'Vzhled je uložený pro všechny návštěvníky.';
        unset($_SESSION['saved']);
    }
}
?>
<!doctype html>
<html lang="cs">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin · TOPROCO</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/vzhled-admin.css">
</head>
<body>
<main class="admin">
    <a href="/" class="back">← TOPROCO</a>
    <h1><?= $authenticated ? 'Vzhled webu' : 'Admin' ?></h1>
    <?php if ($error !== ''): ?><p class="notice error" role="alert"><?= escape($error) ?></p><?php endif ?>
    <?php if ($message !== ''): ?><p class="notice" role="status"><?= escape($message) ?></p><?php endif ?>
    <?php if ($setup): ?>
        <p>Pomocí jednorázového kódu si nastavte e-mail a heslo správce.</p>
        <form action="/vzhled.php?setup=1" method="post" class="login">
            <label for="setup-key">Aktivační kód</label>
            <input type="password" id="setup-key" name="setup_key" maxlength="128" autocomplete="off" required>
            <label for="setup-email">E-mail správce</label>
            <input type="email" id="setup-email" name="email" maxlength="254" autocomplete="username" required>
            <label for="setup-password">Nové heslo</label>
            <input type="password" id="setup-password" name="password" minlength="16" maxlength="72" autocomplete="new-password" required>
            <label for="confirm-password">Potvrďte heslo</label>
            <input type="password" id="confirm-password" name="confirm_password" minlength="16" maxlength="72" autocomplete="new-password" required>
            <button>Aktivovat správce</button>
        </form>
    <?php elseif (!$sessionReady): ?>
        <p>Administraci vzhledu je potřeba aktivovat v nastavení hostingu. Web zatím používá dostupné barevné schéma.</p>
    <?php elseif (!$authenticated): ?>
        <p>Přihlaste se e-mailem a heslem správce.</p>
        <form method="post" class="login">
            <input type="hidden" name="csrf" value="<?= escape($_SESSION['csrf']) ?>">
            <label for="email">E-mail správce</label>
            <input type="email" id="email" name="email" maxlength="254" autocomplete="username" required>
            <label for="password">Heslo správce</label>
            <input type="password" id="password" name="password" maxlength="72" autocomplete="current-password" required>
            <button name="action" value="login">Přihlásit se</button>
        </form>
    <?php else: ?>
        <p>Začněte hotovým schématem nebo si nastavte vlastní barvy a písmo. Změny se projeví po uložení.</p>
        <div class="workspace">
        <form method="post" id="palette">
            <input type="hidden" name="csrf" value="<?= escape($_SESSION['csrf']) ?>">
            <fieldset>
                <legend>Kompletní schéma</legend>
                <label for="preset">Vyberte výchozí barvy a písmo</label>
                <select id="preset">
                    <option value="">Vlastní nastavení</option>
                    <?php foreach (PALETTES as $key => [$name]): ?>
                        <option value="<?= escape($key) ?>"><?= escape($name) ?></option>
                    <?php endforeach ?>
                </select>
            </fieldset>
            <fieldset>
                <legend>Barvy</legend>
                <?php foreach (COLORS as $key => $label): ?>
                    <label class="color-row">
                        <?= escape($label) ?>
                        <span class="value" data-color="<?= escape($key) ?>" aria-hidden="true"><?= escape($palette[$key]) ?></span>
                        <input type="color" name="<?= escape($key) ?>" value="<?= escape($palette[$key]) ?>">
                    </label>
                <?php endforeach ?>
            </fieldset>
            <fieldset>
                <legend>Písmo</legend>
                <?php foreach (TYPE as $key => [$label, $min, $max, $default, $step, $unit]): ?>
                    <label class="type-row">
                        <?= escape($label) ?>
                        <span class="value" data-type="<?= escape($key) ?>" aria-hidden="true"><?= $palette[$key] . $unit ?></span>
                        <input type="range" name="<?= escape($key) ?>" min="<?= $min ?>" max="<?= $max ?>" step="<?= $step ?>" value="<?= $palette[$key] ?>">
                    </label>
                <?php endforeach ?>
            </fieldset>
            <button name="action" value="save">Uložit vzhled webu</button>
        </form>
        <aside>
            <h2>Živý náhled</h2>
            <style nonce="<?= escape($nonce) ?>">
                #preview {
                    <?php foreach (cssVars($palette) as $key => $value): ?>--<?= $key ?>: <?= $value ?>;<?php endforeach ?>
                }
            </style>
            <div class="preview" id="preview">
                <div class="preview-hero">
                    <span class="preview-active">Aktivní položka</span>
                    <p class="preview-heading">Dobré plány začínají rozhovorem.</p>
                    <p>Takto bude vypadat běžný text a <a href="#preview">odkaz v úvodní sekci</a>.</p>
                    <span class="preview-button">Prozkoumat aplikace ↓</span>
                </div>
                <div class="preview-card">
                    <strong>Můj obchod</strong>
                    <p>Ukázka textu a <a href="#preview">odkazu na kartě</a>.</p>
                </div>
            </div>
            <p id="contrast" class="notice" role="status"></p>
        </aside>
        </div>
        <form method="post" class="logout">
            <input type="hidden" name="csrf" value="<?= escape($_SESSION['csrf']) ?>">
            <button name="action" value="logout">Odhlásit se</button>
        </form>
        <script nonce="<?= escape($nonce) ?>">
        const presets = <?= json_encode(array_map('defaults', PALETTES), JSON_THROW_ON_ERROR | JSON_HEX_TAG) ?>;
        const form = document.querySelector('#palette');
        const preset = document.querySelector('#preset');
        const preview = document.querySelector('#preview');
        const contrastNote = document.querySelector('#contrast');
        const colors = <?= json_encode(array_keys(COLORS), JSON_THROW_ON_ERROR) ?>;
        const typography = <?= json_encode(array_map(static fn(array $field): string => $field[5], TYPE), JSON_THROW_ON_ERROR) ?>;

        function luminance(hex) {
            const [r, g, b] = hex.slice(1).match(/../g).map(channel => {
                const value = parseInt(channel, 16) / 255;
                return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
            });
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
        }

        function ratio(a, b) {
            const values = [luminance(a), luminance(b)];
            return (Math.max(...values) + 0.05) / (Math.min(...values) + 0.05);
        }

        function renderPreview() {
            const palette = Object.fromEntries(new FormData(form));
            for (const key of colors) {
                preview.style.setProperty('--' + key, palette[key]);
                form.querySelector('[data-color="' + key + '"]').textContent = palette[key];
            }
            for (const [key, unit] of Object.entries(typography)) {
                const value = palette[key];
                preview.style.setProperty('--' + key, key === 'heading-scale' ? value / 100 : value + unit);
                form.querySelector('[data-type="' + key + '"]').textContent = value + unit;
            }
            for (const key of ['button', 'active']) {
                const ink = ratio('#ffffff', palette[key]) >= ratio('#000000', palette[key]) ? '#ffffff' : '#000000';
                preview.style.setProperty('--' + key + '-ink', ink);
            }
            const minimum = Math.min(...['bg', 'paper', 'sand'].flatMap(surface =>
                ['text', 'link'].map(ink => ratio(palette[surface], palette[ink]))));
            contrastNote.classList.toggle('error', minimum < 4.5);
            contrastNote.textContent = minimum < 4.5
                ? 'Text nebo odkazy mají na některém pozadí nízký kontrast. Upravte barvy před uložením.'
                : 'Text i odkazy mají dostatečný kontrast na všech třech pozadích.';
            preset.value = Object.keys(presets).find(name =>
                Object.entries(presets[name]).every(([key, value]) => palette[key] === String(value))) || '';
        }

        preset.addEventListener('change', () => {
            if (!presets[preset.value]) return;
            for (const [key, value] of Object.entries(presets[preset.value])) {
                form.elements.namedItem(key).value = value;
            }
            renderPreview();
        });
        form.addEventListener('input', event => {
            if (event.target !== preset) renderPreview();
        });
        renderPreview();
        </script>
    <?php endif ?>
</main>
</body>
</html>
