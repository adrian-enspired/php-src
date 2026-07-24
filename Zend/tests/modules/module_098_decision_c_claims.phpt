--TEST--
Modules (C2): sub-path claims — flat handle + projection, `as` collision, internal gating
--DESCRIPTION--
A claim names a member by its module-relative sub-path ("public N\K;"). The member is canonically
X\Y\Z::N\K, reachable by its flat public handle X\Y\Z::K (the tail, or an `as` alias) AND by its
projection X\Y\Z\N\K. Two same-tail members from different sub-namespaces collide on the handle and
are disambiguated with `as`. Reflection reports the canonical; visibility comes from the claim.
--FILE--
<?php
$dir = __DIR__ . '/mod098';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
namespace X\Y;
module Z {
    public N\K;          // canonical X\Y\Z::N\K, handle X\Y\Z::K, projection X\Y\Z\N\K
    public O\K as OK;    // same tail K -> aliased handle X\Y\Z::OK
    internal N\Secret;   // internal, gated from outside
}
PHP);
file_put_contents("$dir/nk.php", "<?php\nmodule X\\Y\\Z;\nnamespace N;\nclass K { public function who(): string { return 'n-k'; } }\n");
file_put_contents("$dir/ok.php", "<?php\nmodule X\\Y\\Z;\nnamespace O;\nclass K { public function who(): string { return 'o-k'; } }\n");
file_put_contents("$dir/secret.php", "<?php\nmodule X\\Y\\Z;\nnamespace N;\nclass Secret {}\n");

require "$dir/manifest.php";
require "$dir/nk.php";
require "$dir/ok.php";
require "$dir/secret.php";

echo "-- N\\K reached by handle, projection, canonical --\n";
echo (new X\Y\Z::K)->who(), "\n";                     // via handle -> n-k
echo (new \X\Y\Z\N\K)->who(), "\n";                    // via projection -> n-k
echo (new X\Y\Z::K)::class, "\n";                      // canonical reported: X\Y\Z::N\K
var_dump((new X\Y\Z::K) instanceof \X\Y\Z\N\K);        // same class

echo "-- O\\K aliased to handle OK (same-tail collision resolved) --\n";
echo (new X\Y\Z::OK)->who(), "\n";                     // o-k
echo (new \X\Y\Z\O\K)->who(), "\n";                     // o-k (projection)
echo (new X\Y\Z::OK)::class, "\n";                     // X\Y\Z::O\K

echo "-- internal member gated from outside --\n";
try { new \X\Y\Z\N\Secret(); echo "LEAK\n"; } catch (\Error $e) { echo "gated\n"; }

echo "-- reflection: canonical visibility --\n";
$rm = new ReflectionModule('X\Y\Z');
echo $rm->getSymbolVisibility('X\Y\Z::N\K'), "\n";        // public
echo $rm->getSymbolVisibility('X\Y\Z::N\Secret'), "\n";   // internal
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod098';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
-- N\K reached by handle, projection, canonical --
n-k
n-k
X\Y\Z::N\K
bool(true)
-- O\K aliased to handle OK (same-tail collision resolved) --
o-k
o-k
X\Y\Z::O\K
-- internal member gated from outside --
gated
-- reflection: canonical visibility --
public
internal
