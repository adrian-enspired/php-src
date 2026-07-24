--TEST--
Modules (C): static access (const/method/prop) to a sub-namespaced member; module:: self-reference
--DESCRIPTION--
A sub-namespaced member X\Y\Z::N\K is reachable for static access by its member name (X\Y\Z::K)
and by its canonical (X\Y\Z::N\K). From inside the module, module::K and module::Hidden reach
siblings module-relative (by member name), including an internal one. An internal member's static
surface is gated from outside.
--FILE--
<?php
$dir = __DIR__ . '/mod100';
@mkdir($dir);
file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace X\Y;
module Z {
    N\K;
    internal N\Hidden;
}
PHP);
file_put_contents("$dir/k.php", <<<'PHP'
<?php
module X\Y\Z;
namespace N;
class K {
    public const TAG = 'tag';
    public static int $count = 7;
    public static function run(): string { return 'run'; }
    public static function selfConst(): string { return module::K::TAG; }
    public static function reachHidden(): string { return module::Hidden::SECRET; }
}
PHP);
file_put_contents("$dir/hidden.php", "<?php\nmodule X\\Y\\Z;\nnamespace N;\nclass Hidden { public const SECRET = 'shh'; }\n");
require "$dir/manifest.php"; require "$dir/k.php"; require "$dir/hidden.php";

echo "-- static access by member name --\n";
echo X\Y\Z::K::TAG, "\n";        // tag
echo X\Y\Z::K::run(), "\n";      // run
echo X\Y\Z::K::$count, "\n";     // 7
echo "-- static access by canonical --\n";
echo X\Y\Z::N\K::TAG, "\n";      // tag
echo "-- module:: self-reference (reaches a sibling by member name) --\n";
echo X\Y\Z::K::selfConst(), "\n";    // tag
echo "-- module:: reaches an internal sibling from inside the module --\n";
echo X\Y\Z::K::reachHidden(), "\n";  // shh
echo "-- internal member's static surface is gated from outside --\n";
try { echo X\Y\Z::Hidden::SECRET, "\n"; echo "LEAK\n"; } catch (\Error $e) { echo "gated\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod100';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
-- static access by member name --
tag
run
7
-- static access by canonical --
tag
-- module:: self-reference (reaches a sibling by member name) --
tag
-- module:: reaches an internal sibling from inside the module --
shh
-- internal member's static surface is gated from outside --
gated
