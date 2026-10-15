--TEST--
Modules: review-follow-up behaviour survives an opcache file-cache hit and preloading
--DESCRIPTION--
Inline ordinary names, nested-module denial (MODULE_NESTED/GATED flags), direct `M::f()` calls,
internal module functions, relative heads, the cold deferred return check, reflection getModule()
and dynamic 'M::f' strings all depend on compile-time state. Run 1 compiles and fills the file
cache; run 2 executes from the cache only (compiler skipped); run 3 preloads the definition.
--SKIPIF--
<?php
if (!extension_loaded('Zend OPcache')) die('skip requires opcache');
if (substr(PHP_OS, 0, 3) == 'WIN') die('skip not for Windows');
?>
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE');
$dir = __DIR__ . '/m321_tmp';
@mkdir($dir);
@mkdir("$dir/cache");
file_put_contents("$dir/lib.php", <<<'PHP'
<?php
namespace N;
module M {
    public Late;
    public class C {}
    internal class H {}
    internal module In { public class G {} }
    public function f() { return 'f'; }
    internal function g() { return 'g'; }
    public module Inner2 { public class X {} }
    public class T {
        public static function viaRel() { return get_class(new Inner2::X); }
        public static function callG() { return module::g(); }
    }
}
PHP);
file_put_contents("$dir/mem.php", "<?php\nmodule N\\M;\nclass Late { public function leak(): \\N\\M::H { return new \\N\\M::H; } }\n");
$body = <<<'PHP'
function t($n, $f) { try { $r = $f(); echo "$n: ", is_object($r) ? get_class($r) : var_export($r, true), "\n"; } catch (Throwable $e) { echo "$n: ", $e->getMessage(), "\n"; } }
t('ordinary name', fn() => new N\M\C);
t('internal via ordinary', fn() => new N\M\H);
t('public of internal nested', fn() => new N\M::In::G);
t('M::f()', fn() => N\M::f());
t('M::g() internal', fn() => N\M::g());
t('module::g() inside', fn() => N\M::T::callG());
t('relative head', fn() => N\M::T::viaRel());
t('getModule', fn() => (new ReflectionClass('N\M::C'))->getModule()->getName());
t('dyn string call', fn() => ('N\M::f')());
PHP;
file_put_contents("$dir/run.php", "<?php\nspl_autoload_register(function (\$c) { if (strtolower(\$c) === 'n\\m') require __DIR__ . '/lib.php'; });\n"
    . "require __DIR__ . '/mem.php';\n$body\nt('deferred return (cold)', fn() => (new N\\M::Late)->leak());\n");
file_put_contents("$dir/run_pre.php", "<?php\n$body\n");
file_put_contents("$dir/preload.php", "<?php\nrequire __DIR__ . '/lib.php';\n");

$fc = '-n -d opcache.enable_cli=1 -d opcache.file_cache=' . escapeshellarg("$dir/cache")
    . ' -d opcache.file_cache_only=1 -d opcache.file_update_protection=0 -d opcache.validate_timestamps=0';
$r1 = shell_exec("$php $fc " . escapeshellarg("$dir/run.php") . ' 2>&1');
$r2 = shell_exec("$php $fc " . escapeshellarg("$dir/run.php") . ' 2>&1');
echo $r2;
echo "file cache hit identical: ", var_export($r1 === $r2, true), "\n";
$pre = "-n -d opcache.enable_cli=1 -d opcache.preload=" . escapeshellarg("$dir/preload.php");
$r3 = shell_exec("$php $pre " . escapeshellarg("$dir/run_pre.php") . ' 2>&1');
echo "preload identical: ", var_export($r3 === implode("\n", array_slice(explode("\n", $r1), 0, 9)) . "\n", true), "\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m321_tmp';
foreach (['lib', 'mem', 'run', 'run_pre', 'preload'] as $f) { @unlink("$dir/$f.php"); }
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$dir/cache", FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
@rmdir("$dir/cache");
@rmdir($dir);
?>
--EXPECT--
ordinary name: N\M::C
internal via ordinary: Cannot access internal module member "N\M\H" from outside its module
public of internal nested: Cannot access "N\M::In::G": module "N\M::In" is internal to its parent module
M::f(): 'f'
M::g() internal: Cannot call internal module function N\M::g() from outside its module
module::g() inside: 'g'
relative head: 'N\\M::Inner2::X'
getModule: 'N\\M'
dyn string call: 'f'
deferred return (cold): the return type of N\M::Late::leak() references "N\M::H", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
file cache hit identical: true
preload identical: true
