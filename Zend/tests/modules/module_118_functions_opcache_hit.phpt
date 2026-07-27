--TEST--
Module functions: canonical binding, alias keys, and gating all survive an opcache cache hit
--SKIPIF--
<?php
if (!extension_loaded('Zend OPcache')) die('skip requires opcache');
?>
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE');
$dir = __DIR__ . '/mod118_tmp';
@mkdir($dir);
$cache = $dir . '/cache';
@mkdir($cache);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module OcFn { public x; public RATE; }
PHP);

file_put_contents($dir . '/funcs.php', <<<'PHP'
<?php
module OcFn;
const RATE = 7;
const HIDDEN = 9;
function x(): int { return f() + RATE; }
function f(): int { return 1; }
PHP);

file_put_contents($dir . '/consumer.php', <<<'PHP'
<?php
require __DIR__ . '/manifest.php';
require __DIR__ . '/funcs.php';
echo "x=", OcFn::x(), " ns=", \OcFn\x(), " rate=", OcFn::RATE, "\n";
try { \OcFn\f(); echo "LEAK-F\n"; } catch (\Error $e) { echo "gated-f\n"; }
try { $v = \OcFn\HIDDEN; echo "LEAK-K\n"; } catch (\Error $e) { echo "gated-k\n"; }
PHP);

$opts = '-d opcache.enable_cli=1 -d opcache.file_cache=' . escapeshellarg($cache)
      . ' -d opcache.file_cache_only=1 -d opcache.validate_timestamps=0'
      . ' -d opcache.file_update_protection=0';
$cmd = escapeshellarg($php) . ' -n ' . $opts . ' ' . escapeshellarg($dir . '/consumer.php') . ' 2>&1';

echo "run1: ", trim(shell_exec($cmd)), "\n";   // fresh compile -> populates cache
echo "run2: ", trim(shell_exec($cmd)), "\n";   // cache hit -> compiler never runs
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod118_tmp';
if (is_dir($dir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
    @rmdir($dir);
}
?>
--EXPECT--
run1: x=8 ns=8 rate=7
gated-f
gated-k
run2: x=8 ns=8 rate=7
gated-f
gated-k
