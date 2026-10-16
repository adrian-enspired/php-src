--TEST--
Modules: a namespace named Internal can still be imported (use X\Internal;)
--DESCRIPTION--
`Internal` is reserved as a class name, but an `Internal` alias can only name a namespace
(no class can be named Internal), so importing one keeps working. Declaring a class named
Internal is still an error.
--FILE--
<?php
namespace Amp\Internal {
    function formatStacktrace() { return 'fn'; }
    const X = 'const';
    class Helper { const K = 'class'; }
}
namespace App {
    use Amp\Internal;
    use Amp\Internal as Other;
    echo Internal\formatStacktrace(), ' ', Internal\X, ' ', Internal\Helper::K, ' ', Other\formatStacktrace(), "\n";
    $php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
    $f = __DIR__ . '/module_327_case.inc';
    file_put_contents($f, "<?php\nnamespace Foo;\nclass Internal {}\n");
    $r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
    echo preg_match('/Fatal error: (.*?) in \//', $r, $m) ? $m[1] : $r, "\n";
    @unlink($f);
}
?>
--EXPECT--
fn const class fn
Cannot use "Internal" as a class name as it is reserved
