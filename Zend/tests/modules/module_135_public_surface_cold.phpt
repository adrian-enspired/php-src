--TEST--
Modules: a membership file compiled without its definition is not reported
--DESCRIPTION--
A member compiled before its module definition carries ZEND_ACC2_MODULE_VIS_UNKNOWN -- the
roster it would be checked against does not exist yet. The public-surface check must skip it
rather than treat the absent claim as "internal", or every membership file fails to lint
standalone (`php -l`, parallel-lint, IDE checks) even though it compiles correctly in a real
run once the definition is loaded.
--FILE--
<?php
$dir = __DIR__ . '/mod135_tmp';
@mkdir($dir);

// an internal member whose own public method returns its own type
file_put_contents($dir . '/member.php', <<<'PHP'
<?php
module Cold;
class Impl {
    public static function make(): Impl { return new Impl(); }
}
PHP);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module Cold { internal Impl; }
PHP);

$php = getenv('TEST_PHP_EXECUTABLE');
// standalone: definition never loaded -> must not be reported
echo "cold lint : ", trim(shell_exec(escapeshellarg($php) . ' -n -l ' . escapeshellarg($dir . '/member.php') . ' 2>&1')), "\n";

// with the definition: internal class-likes are skipped by the gate anyway
require $dir . '/manifest.php';
require $dir . '/member.php';
// observation only -- Impl is internal, so calling it from here would (correctly) be denied
echo "loaded    : ", (new ReflectionClass('Cold::Impl'))->getName(), "\n";
echo "internal  : ", (new ReflectionModule('Cold'))->getSymbolVisibility('Cold::Impl'), "\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod135_tmp';
foreach (glob($dir . '/*.php') as $f) { @unlink($f); }
@rmdir($dir);
?>
--EXPECTF--
cold lint : No syntax errors detected in %s
loaded    : Cold::Impl
internal  : internal
