--TEST--
Modules (C): cold projection survives an opcache cache hit (UNKNOWN + roster rebuilt from cache)
--SKIPIF--
<?php
if (!extension_loaded('Zend OPcache')) die('skip requires opcache');
if (substr(PHP_OS, 0, 3) == 'WIN') die('skip not for Windows');
?>
--FILE--
<?php
/* A member file reached COLD (by its namespaced name, before the module loads) compiles with
 * its module manifest ABSENT, so its member class is stamped MODULE_VIS_UNKNOWN and its module
 * is loaded by the directive's runtime ensure-load. This test proves that whole path still works
 * on an opcache CACHE HIT — where the compiler never runs — by loading the files from opcache's
 * file cache in a SECOND process. The UNKNOWN flag rides the persisted class entry; the manifest's
 * member roster (visibility + "@"-handle keys) rides the persisted op_array and rebuilds the
 * per-request registry; the gate then resolves visibility off the rebuilt registry. */
$php = getenv('TEST_PHP_EXECUTABLE');
$dir = __DIR__ . '/mod105_tmp';
@mkdir($dir);
$cache = $dir . '/cache';
@mkdir($cache);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
namespace Ven;
module Usr {
    public Auth\Check;      // canonical Ven\Usr::Auth\Check; member name Ven\Usr::Check
    internal Impl\Tok;      // canonical Ven\Usr::Impl\Tok; internal
}
PHP);

file_put_contents($dir . '/check.php', <<<'PHP'
<?php
module Ven\Usr;
namespace Auth;
class Check { public function t(): string { return "chk"; } }
PHP);

file_put_contents($dir . '/tok.php', <<<'PHP'
<?php
module Ven\Usr;
namespace Impl;
class Tok {}
PHP);

file_put_contents($dir . '/consumer.php', <<<'PHP'
<?php
$dir = __DIR__;
spl_autoload_register(function (string $n) use ($dir): void {
    if ($n === 'Ven\\Usr\\Auth\\Check') { require "$dir/check.php";    return; }
    if ($n === 'Ven\\Usr\\Impl\\Tok')   { require "$dir/tok.php";      return; }
    if ($n === 'Ven\\Usr')              { require "$dir/manifest.php"; return; }
});
// COLD internal FIRST: tok.php compiles manifest-less -> UNKNOWN; ensure-load + gate must gate it.
try { new Ven\Usr\Impl\Tok(); echo "LEAK"; } catch (\Error $e) { echo "gated"; }
// Public member (module is loaded now): reachable, canonical identity, member name registered.
$c = new Ven\Usr\Auth\Check();
echo "|", $c->t(), "|", $c::class, "|", (class_exists('Ven\\Usr::Check') ? 'name' : 'x'), "\n";
PHP);

$opts = '-d opcache.enable_cli=1 -d opcache.file_cache=' . escapeshellarg($cache)
      . ' -d opcache.file_cache_only=1 -d opcache.validate_timestamps=0'
      . ' -d opcache.file_update_protection=0';
$cmd = escapeshellarg($php) . ' -n ' . $opts . ' ' . escapeshellarg($dir . '/consumer.php') . ' 2>&1';

echo "run1: ", trim(shell_exec($cmd)), "\n";   // fresh compile (cold) -> populates cache
echo "run2: ", trim(shell_exec($cmd)), "\n";   // files loaded from cache; compiler skipped
?>
--CLEAN--
<?php
function rrmdir(string $d): void {
    if (!file_exists($d)) return;
    if (!is_dir($d)) { @unlink($d); return; }
    foreach (scandir($d) as $f) {
        if ($f === '.' || $f === '..') continue;
        rrmdir($d . '/' . $f);
    }
    @rmdir($d);
}
rrmdir(__DIR__ . '/mod105_tmp');
?>
--EXPECT--
run1: gated|chk|Ven\Usr::Auth\Check|name
run2: gated|chk|Ven\Usr::Auth\Check|name
