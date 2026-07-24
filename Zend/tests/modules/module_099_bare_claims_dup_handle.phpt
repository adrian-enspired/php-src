--TEST--
Modules (C): bare (default-public) sub-path claims; duplicate handle is a clear error
--DESCRIPTION--
Claim visibility is optional and defaults to `public`, so `C;` / `N\K;` / `O\K as OK;` are
public claims. A member's flat handle must be unique in the module; two claims resolving to the
same handle without `as` are a clear compile error (not a later class-alias redeclaration).
--SKIPIF--
<?php if (!function_exists('shell_exec')) die('skip shell_exec required'); ?>
--FILE--
<?php
$dir = __DIR__ . '/mod099';
@mkdir($dir);
file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace X\Y;
module Z {
    C;            // bare -> public; handle X\Y\Z::C
    N\K;          // bare -> public; handle X\Y\Z::K
    O\K as OK;    // bare -> public; handle X\Y\Z::OK
}
PHP);
file_put_contents("$dir/c.php",  "<?php\nmodule X\\Y\\Z;\nclass C { public function w(): string { return 'c'; } }\n");
file_put_contents("$dir/nk.php", "<?php\nmodule X\\Y\\Z;\nnamespace N;\nclass K { public function w(): string { return 'nk'; } }\n");
file_put_contents("$dir/ok.php", "<?php\nmodule X\\Y\\Z;\nnamespace O;\nclass K { public function w(): string { return 'ok'; } }\n");
require "$dir/manifest.php"; require "$dir/c.php"; require "$dir/nk.php"; require "$dir/ok.php";

echo "-- bare claims default to public --\n";
echo (new X\Y\Z::C)->w(), "\n";     // c
echo (new X\Y\Z::K)->w(), "\n";     // nk  (handle for N\K)
echo (new X\Y\Z::OK)->w(), "\n";    // ok  (as handle for O\K)

echo "-- duplicate handle is a clear compile error --\n";
$f = tempnam(sys_get_temp_dir(), 'dup') . '.php';
file_put_contents($f, "<?php\nnamespace X\\Y;\nmodule Z2 {\n    N\\K;\n    O\\K;\n}\n");   // both -> handle K, no `as`
$out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n -l ' . escapeshellarg($f) . ' 2>&1');
@unlink($f);
echo str_contains($out, 'Duplicate module member handle') ? "rejected\n" : "NOT REJECTED: $out\n";

echo "-- a claim handle colliding with an inline member is the same error --\n";
$f2 = tempnam(sys_get_temp_dir(), 'dup2') . '.php';
file_put_contents($f2, "<?php\nnamespace X\\Y;\nmodule Z3 {\n    public class K {}\n    N\\K;\n}\n");   // inline K + claim handle K
$out2 = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n -l ' . escapeshellarg($f2) . ' 2>&1');
@unlink($f2);
echo str_contains($out2, 'Duplicate module member handle') ? "rejected\n" : "NOT REJECTED: $out2\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod099';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
-- bare claims default to public --
c
nk
ok
-- duplicate handle is a clear compile error --
rejected
-- a claim handle colliding with an inline member is the same error --
rejected
