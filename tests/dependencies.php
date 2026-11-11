<?php
require_once dirname(__DIR__) . '/system/libs/dependencies.cls.php';
function verify($ok, $message) { if (!$ok) throw new RuntimeException($message); }
class FixtureDependencies extends dependencies
{
    public $calls = 0;
    public $corrupt = false;
    public $fail = false;
    protected function download(string $url): string
    {
        $this->calls++;
        if ($this->fail) throw new RuntimeException('Simulated network failure');
        if ($this->corrupt) return 'corrupt download';
        $base = self::catalog()['phpmailer']['base'];
        verify(strpos($url, $base) === 0, 'Unexpected download URL');
        return file_get_contents(dirname(__DIR__) . '/resources/plugins/vendor/phpmailer/phpmailer/' . substr($url, strlen($base)));
    }
}
$root = sys_get_temp_dir() . '/melwin-dependencies-' . bin2hex(random_bytes(8));
mkdir($root);
$manager = ($argv[1] ?? '') === '--live' ? new dependencies($root) : new FixtureDependencies($root);
try {
    verify($manager->status('phpmailer') === 'Saknas', 'Incorrect missing status');
    try { $manager->install('../unknown'); throw new LogicException('Unknown package accepted'); }
    catch (RuntimeException $e) { verify($e->getMessage() === 'Okänt beroende.', 'Unexpected catalog error'); }
    if ($manager instanceof FixtureDependencies) {
        foreach (['corrupt', 'fail'] as $flag) {
            $manager->$flag = true;
            try { $manager->install('phpmailer'); throw new LogicException('Bad download accepted'); }
            catch (RuntimeException $e) {}
            verify(!is_dir($root . '/phpmailer'), 'Incomplete download activated');
            verify(count(array_filter(glob($root . '/.dependency-*'), 'is_dir')) === 0, 'Staging files retained');
            $manager->$flag = false;
        }
    }
    $manager->install('phpmailer');
    verify($manager->status('phpmailer') === 'Installerad', 'Install failed');
    $manager->install('phpmailer'); // Must not download or overwrite an installed package.
    define('PLUGINPATH', $root . '/');
    require dirname(__DIR__) . '/system/libs/mail.cls.php';
    verify(new mail instanceof PHPMailer\PHPMailer\PHPMailer, 'Direct source loader failed');
    file_put_contents($root . '/phpmailer/LICENSE', 'modified');
    verify($manager->status('phpmailer') === 'Behöver åtgärdas', 'Corruption not detected');
    try { $manager->install('phpmailer'); throw new LogicException('Existing package overwritten'); }
    catch (RuntimeException $e) { verify(strpos($e->getMessage(), 'avviker') !== false, 'Wrong repair guidance'); }
    verify(file_get_contents($root . '/phpmailer/LICENSE') === 'modified', 'Existing files changed');
    echo "PASS: dependency installation, integrity, retry, existing files and direct mail loading\n";
} finally {
    // Delete only files in this test's explicitly created directory.
    foreach (dependencies::catalog()['phpmailer']['files'] as $file => $hash) {
        if (is_file($root . '/phpmailer/' . $file)) unlink($root . '/phpmailer/' . $file);
    }
    if (is_dir($root . '/phpmailer/src')) rmdir($root . '/phpmailer/src');
    if (is_dir($root . '/phpmailer')) rmdir($root . '/phpmailer');
    if (is_file($root . '/.dependency-install.lock')) unlink($root . '/.dependency-install.lock');
    rmdir($root);
}

// Exercise the existing-site UI's permission and CSRF gates without network access.
function isSuperUser() { return $GLOBALS['test_superuser']; }
require dirname(__DIR__) . '/system/modules/system_update_tool/system_update_tool.php';
$tool = (new ReflectionClass('system_update_tool'))->newInstanceWithoutConstructor();
$render = new ReflectionMethod('system_update_tool', 'render_dependencies_section');
$GLOBALS['test_superuser'] = false;
ob_start(); $render->invoke($tool); $html = ob_get_clean();
verify($html === '', 'Non-superuser sees install controls');
$GLOBALS['test_superuser'] = true;
$_SESSION['melwin_dependency_token'] = 'valid-token';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['dependency_action' => 'install_dependency', 'dependency_token' => 'invalid', 'dependency' => 'phpmailer'];
ob_start(); $render->invoke($tool); $html = ob_get_clean();
verify(strpos($html, 'Ogiltig begäran') !== false, 'CSRF request accepted');
verify(strpos($html, 'name="dependency_token" value="valid-token"') !== false, 'Missing form token');
echo "PASS: superuser and CSRF gates\n";

// Synthetic packages exercise module discovery, nested dependencies and upgrades.
class GraphDependencies extends dependencies
{
    public $fail = false;
    public $failAt = '';
    public $downloaded = [];
    protected function download(string $url): string {
        if ($this->fail || ($this->failAt !== '' && basename($url) === $this->failAt)) throw new RuntimeException('Offline');
        $this->downloaded[] = $url;
        return basename($url);
    }
}
$fixture = sys_get_temp_dir() . '/melwin-dependency-graph-' . bin2hex(random_bytes(8));
mkdir($fixture . '/modules/system/config', 0700, true);
mkdir($fixture . '/modules/addon/config', 0700, true);
mkdir($fixture . '/plugins');
$release = function ($body, $require = []) {
    return ['base' => 'https://example.com/' . $body . '/', 'files' => ['nested/lib/' . $body => hash('sha256', $body)], 'require' => $require];
};
$config = ['require' => ['main' => '1.0.0'], 'packages' => [
    'main' => ['name' => 'Main', 'source' => 'https://example.com/main', 'releases' => ['1.0.0' => $release('main-old')]],
    'child' => ['name' => 'Child', 'source' => 'https://example.com/child', 'releases' => ['1.0.0' => $release('child', ['leaf' => '1.0.0'])]],
    'leaf' => ['name' => 'Leaf', 'source' => 'https://example.com/leaf', 'releases' => ['1.0.0' => $release('leaf')]],
]];
$write = function () use (&$config, $fixture) {
    file_put_contents($fixture . '/modules/system/config/dependencies.json', json_encode($config));
};
$removeFixture = function ($directory) use (&$removeFixture, $fixture) {
    if (strpos($directory, $fixture) !== 0) throw new LogicException('Outside fixture');
    foreach (scandir($directory) as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $directory . '/' . $name;
        if (is_dir($path) && !is_link($path)) $removeFixture($path); else unlink($path);
    }
    rmdir($directory);
};
try {
    $write();
    $manager = new GraphDependencies($fixture . '/plugins', $fixture . '/modules');
    $manager->install('main');
    verify($manager->status('main') === 'Installerad', 'Initial release failed');
    $config['packages']['main']['releases']['1.1.0'] = $release('main-new', ['child' => '1.0.0']);
    $write();
    verify($manager->status('main') === 'Uppdatering tillgänglig', 'Upgrade not detected');
    ob_start(); $manager->render('token', 'action', 'csrf'); $html = ob_get_clean();
    verify(strpos($html, '>Uppdatera</button>') !== false, 'Upgrade control missing');
    $manager->fail = true;
    try { $manager->install('main'); throw new LogicException('Failed upgrade accepted'); }
    catch (RuntimeException $e) {}
    verify(file_get_contents($fixture . '/plugins/main/nested/lib/main-old') === 'main-old', 'Failed upgrade changed installation');
    $manager->fail = false;
    $manager->failAt = 'main-new';
    try { $manager->install('main'); throw new LogicException('Failed main upgrade accepted'); }
    catch (RuntimeException $e) {}
    verify($manager->status('main') === 'Uppdatering tillgänglig' && is_file($fixture . '/plugins/main/nested/lib/main-old'), 'Failed main upgrade damaged old version');
    verify(count(array_filter(glob($fixture . '/plugins/.dependency-*'), 'is_dir')) === 0, 'Failed upgrade retained staging');
    $manager->failAt = '';
    // Remove already installed children in the isolated fixture to verify order.
    $removeFixture($fixture . '/plugins/child');
    $removeFixture($fixture . '/plugins/leaf');
    $manager->downloaded = [];
    $manager->install('main');
    verify(array_map('basename', $manager->downloaded) === ['leaf', 'child', 'main-new'], 'Wrong nested dependency order');
    verify($manager->status('main') === 'Installerad', 'Upgrade failed');
    $backups = glob($fixture . '/plugins/.dependency-backup-main-*');
    verify(count($backups) === 1 && is_file($backups[0] . '/nested/lib/main-old'), 'Upgrade backup missing');
    file_put_contents($fixture . '/modules/addon/config/dependencies.json', json_encode(['require' => ['main' => '1.1.0']]));
    verify(dependencies::catalog($fixture . '/modules')['main']['minimum'] === '1.1.0', 'Strongest module minimum not used');
    $good = $config;
    $cases = [
        function () use (&$config) { $config['packages']['leaf']['releases']['1.0.0']['require'] = ['main' => '1.0.0']; },
        function () use (&$config) { $config['packages']['main']['releases']['1.1.0']['require'] = ['unknown' => '1.0.0']; },
        function () use (&$config) { $config['packages']['main']['releases']['1.1.0']['require'] = ['child' => '9.0.0']; },
        function () use (&$config) { $config['packages']['main']['releases']['1.1.0']['files'] = ['../escape' => str_repeat('a', 64)]; },
    ];
    foreach ($cases as $change) {
        $config = $good;
        $change(); $write();
        $count = count($manager->downloaded);
        try { $manager->install('main'); throw new LogicException('Invalid graph accepted'); }
        catch (RuntimeException $e) {}
        verify(count($manager->downloaded) === $count, 'Invalid graph downloaded files');
    }
    echo "PASS: module manifests, minimum versions, nested dependencies, upgrades, backups and invalid graphs\n";
} finally { $removeFixture($fixture); }
