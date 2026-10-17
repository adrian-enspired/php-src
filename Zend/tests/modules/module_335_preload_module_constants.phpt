--TEST--
Modules: module constants survive preloading (inline, member-file, attributed), still gated
--DESCRIPTION--
Preloading keeps classes and functions but not constants. Module constants a preload script
declared are stored on their module's backing class at the end of preloading and re-declared in
every request, with their visibility and attributes.
--SKIPIF--
<?php
if (!extension_loaded('Zend OPcache')) die('skip requires opcache');
if (substr(PHP_OS, 0, 3) == 'WIN') die('skip not for Windows');
?>
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE');
$dir = __DIR__ . '/m335_tmp';
@mkdir($dir);
file_put_contents("$dir/def.php", <<<'PHP'
<?php
namespace { #[\Attribute] class Tag { function __construct(public string $v) {} } }
namespace V { module M {
    public const OPEN = 'open';
    internal const SHUT = 'shut';
    #[\Tag("t")] public const TAGGED = [1, 2];
    public FILEC;
    public class Reader { static function read() { return module::SHUT . '/' . module::FILEC; } }
} }
PHP);
file_put_contents("$dir/mem.php", "<?php\nmodule V\\M;\nconst FILEC = 'file';\n");
file_put_contents("$dir/preload.php", "<?php\nrequire __DIR__ . '/def.php';\nrequire __DIR__ . '/mem.php';\n");
file_put_contents("$dir/run.php", <<<'PHP'
<?php
echo V\M\OPEN, ' ', V\M\FILEC, ' ', V\M\Reader::read(), ' ', implode(',', V\M\TAGGED), "\n";
try { echo V\M\SHUT; } catch (Error $e) { echo $e->getMessage(), "\n"; }
echo (new ReflectionConstant('V\M\TAGGED'))->getAttributes()[0]->newInstance()->v, "\n";
var_dump((new ReflectionModule('V\M'))->getSymbolVisibility('SHUT'));
PHP);
echo shell_exec("$php -n -d opcache.enable_cli=1 -d opcache.preload=" . escapeshellarg("$dir/preload.php") . ' ' . escapeshellarg("$dir/run.php") . ' 2>&1');
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m335_tmp';
foreach (['def', 'mem', 'preload', 'run'] as $f) { @unlink("$dir/$f.php"); }
@rmdir($dir);
?>
--EXPECT--
open file shut/file 1,2
Cannot access internal module constant V\M\SHUT from outside its module
t
string(8) "internal"
