--TEST--
Modules (C1): coupled member-file naming, namespace-relative references, use-aliases, module-first
--DESCRIPTION--
Decision C, increment 1 (naming + ordering). `module X\Y\Z;` seeds the file's base namespace; a
member is canonically M::<sub>\<tail> and projects M\<sub>\<tail>; references inside a member file
resolve namespace-relatively (projection form); `use` aliases resolve to their imported FQN; a
membership file must be module-first. Claims are still B-style in C1, so only ROOT members are
exercised for visibility; the namespaced member is checked for naming/identity (it is
internal-by-default until C2 reworks claims).
--SKIPIF--
<?php if (!function_exists('shell_exec')) die('skip shell_exec required'); ?>
--FILE--
<?php
$dir = __DIR__ . '/mod097';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace X\Y;
module Z {
    public C;
    public D;
    public U;
}
PHP);
file_put_contents("$dir/C.php", <<<'PHP'
<?php
module X\Y\Z;
class C {
    public function tag(): string { return "C"; }
    public function makeD(): object { return new D(); }   // bare ref -> projection X\Y\Z\D
}
PHP);
file_put_contents("$dir/D.php", "<?php\nmodule X\\Y\\Z;\nclass D { public function tag(): string { return 'D'; } }\n");
file_put_contents("$dir/Ext.php", "<?php\nnamespace Ext;\nclass Thing { public function who(): string { return 'ext'; } }\n");
file_put_contents("$dir/U.php", <<<'PHP'
<?php
module X\Y\Z;
use Ext\Thing as T;
class U {
    public function make(): object { return new T(); }    // use-alias -> Ext\Thing
}
PHP);
file_put_contents("$dir/K.php", "<?php\nmodule X\\Y\\Z;\nnamespace N;\nclass K {}\n");

require "$dir/manifest.php";
require "$dir/Ext.php";
require "$dir/D.php";
require "$dir/C.php";
require "$dir/U.php";
require "$dir/K.php";

echo "-- root member naming + sibling reference --\n";
var_dump(class_exists('X\Y\Z::C'));                 // canonical
var_dump(class_exists('X\Y\Z\C'));                   // projection
echo (new X\Y\Z::C)->tag(), "\n";                    // C
echo (new X\Y\Z::C)::class, "\n";                    // X\Y\Z::C  (canonical reported)
echo (new X\Y\Z::C)->makeD()->tag(), "\n";           // D  (bare ref resolved via projection)
echo (new \X\Y\Z\C)->tag(), "\n";                    // C  (reached via projection)

echo "-- use alias resolves to the imported FQN --\n";
echo (new X\Y\Z::U)->make()->who(), "\n";            // ext
echo (new X\Y\Z::U)->make()::class, "\n";            // Ext\Thing

echo "-- namespaced member: canonical + projection resolve, gated internal in C1 --\n";
var_dump(class_exists('X\Y\Z::N\K'));                // canonical (carries the sub-namespace)
var_dump(class_exists('X\Y\Z\N\K'));                  // projection
try { new \X\Y\Z\N\K(); echo "NOT GATED\n"; }
catch (\Error $e) { echo str_contains($e->getMessage(), 'internal module member') ? "gated\n" : "$e\n"; }

echo "-- membership file must be module-first --\n";
$f = tempnam(sys_get_temp_dir(), 'mf') . '.php';
file_put_contents($f, "<?php\nnamespace A\\B;\nmodule X\\Y\\Z;\nclass C {}\n");
$out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n -l ' . escapeshellarg($f) . ' 2>&1');
@unlink($f);
echo (stripos($out, 'must be the file') !== false) ? "rejected\n" : "NOT REJECTED: $out\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod097';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
-- root member naming + sibling reference --
bool(true)
bool(true)
C
X\Y\Z::C
D
C
-- use alias resolves to the imported FQN --
ext
Ext\Thing
-- namespaced member: canonical + projection resolve, gated internal in C1 --
bool(true)
bool(true)
gated
-- membership file must be module-first --
rejected
