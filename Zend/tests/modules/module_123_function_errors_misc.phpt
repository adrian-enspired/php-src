--TEST--
Module functions: reserved shapes, attribute limits, unfulfilled claims, resolution fallbacks
--FILE--
<?php
function lint(string $code): string {
    $f = tempnam(sys_get_temp_dir(), 'mfe') . '.php';
    file_put_contents($f, "<?php\n" . $code);
    $out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n -l ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    return $out;
}

// `static function` in the block stays reserved (rejected), unchanged
echo str_contains(
    lint("module M { public static function f(): int { return 1; } }\n"),
    'Module-level static functions are not supported') ? "reject ok: static function\n" : "FAIL 1\n";

// only public/internal are module visibilities
echo str_contains(
    lint("module M { protected function f() {} }\n"),
    'syntax error') ? "reject ok: protected function\n" : "FAIL 2\n";

// attributes on a member-file module constant point at the inline form
echo str_contains(
    lint("module M;\n#[MyAttr]\nconst K = 1;\n"),
    'Attributes are not supported on a module constant declared in a membership file')
    ? "reject ok: attributed member-file const\n" : "FAIL 3\n";

// a claimed-but-undefined function is undefined at the call, not before
module Claims { public ghost; }
try { Claims::ghost(); echo "LEAK\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

// module_014 companion: global fallback still wins where no module function exists,
// in both inline bodies and member files
$dir = __DIR__ . '/mod123_tmp';
@mkdir($dir);
file_put_contents($dir . '/m.php', "<?php\nmodule GfA { public t; }\n");
file_put_contents($dir . '/f.php', <<<'PHP'
<?php
module GfA;
function t(): int { return strlen("xyz") + count([1]); }   // both resolve global via fallback
PHP);
require $dir . '/m.php';
require $dir . '/f.php';
var_dump(GfA::t());
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod123_tmp';
@unlink($dir . '/m.php');
@unlink($dir . '/f.php');
@rmdir($dir);
?>
--EXPECTF--
reject ok: static function
reject ok: protected function
reject ok: attributed member-file const
Call to undefined method Claims::ghost()
int(4)
