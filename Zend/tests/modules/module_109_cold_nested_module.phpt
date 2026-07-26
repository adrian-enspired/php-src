--TEST--
Modules (C): a COLD nested-module backing is stamped UNKNOWN and gated once the parent loads
--DESCRIPTION--
When a nested module (Outer::Inner) is compiled COLD — its parent Outer's manifest not yet loaded —
the backing class's visibility (from Outer's `internal module Inner;` claim) is unknowable, so it is
stamped UNKNOWN instead of defaulting to public. Once Outer loads, the access gate's ancestor walk
resolves the nested module's internal-ness from Outer's registry: a public member of an internal
nested module is hidden from outside the parent. Without this the cold backing would bake public and
leak the member.
--FILE--
<?php
$dir = __DIR__ . '/mod109';
@mkdir($dir);

// Inner is required FIRST (cold): Outer is not loaded, so Inner's backing visibility is unknown.
file_put_contents("$dir/inner.php", <<<'PHP'
<?php
module Outer::Inner {
    public class Widget { public function w(): string { return "widget"; } }
}
PHP);

file_put_contents("$dir/outer.php", <<<'PHP'
<?php
module Outer {
    public class Pub {}
    internal module Inner;   // Inner is internal to Outer
}
PHP);

require "$dir/inner.php";   // COLD nested backing -> stamped UNKNOWN
require "$dir/outer.php";   // Outer loads and claims Inner internal

// Widget is public *within* Inner, but Inner is internal to Outer, so from outside Outer the
// member is gated (a public member of an internal nested module is hidden past the parent).
try { new Outer::Inner::Widget(); echo "LEAK\n"; } catch (\Error $e) { echo "gated\n"; }
// The public sibling in Outer is reachable.
echo (new Outer::Pub()) instanceof Outer::Pub ? "pub-ok\n" : "x\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod109';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
gated
pub-ok
