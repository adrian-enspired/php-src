--TEST--
Modules (B): "Full\Name as Alias" gives a same-tail member a distinct module handle
--DESCRIPTION--
Two members share the simple tail "Widget" but live in different namespaces. Without `as`
they'd both want the handle Shop::Widget (a collision). `C\D\Widget as Gizmo` gives the second
the handle Shop::Gizmo instead; each still projects its own namespace name. The member files
declare a plain `class Widget` — the alias is applied from the manifest's claim.
--FILE--
<?php
$dir = __DIR__ . '/as_tmp';
@mkdir($dir);

file_put_contents("$dir/manifest.php", <<<'PHP'
<?php
module Shop {
    public A\B\Widget;          // -> Shop::Widget, projects A\B\Widget
    public C\D\Widget as Gizmo; // same tail "Widget", aliased -> Shop::Gizmo, projects C\D\Widget
}
PHP);
file_put_contents("$dir/w1.php", "<?php\nmodule Shop;\nnamespace A\\B;\nclass Widget { public function who(): string { return 'ab'; } }\n");
file_put_contents("$dir/w2.php", "<?php\nmodule Shop;\nnamespace C\\D;\nclass Widget { public function who(): string { return 'cd'; } }\n");

require "$dir/manifest.php";
require "$dir/w1.php";
require "$dir/w2.php";

// First member: canonical Shop::Widget, projection A\B\Widget.
echo (new Shop::Widget)->who(), "\n";      // ab
echo (new \A\B\Widget)->who(), "\n";       // ab (projection)
echo (new Shop::Widget)::class, "\n";      // Shop::Widget

// Second member: aliased to Shop::Gizmo, projection C\D\Widget.
echo (new Shop::Gizmo)->who(), "\n";       // cd
echo (new \C\D\Widget)->who(), "\n";       // cd (projection)
echo (new Shop::Gizmo)::class, "\n";       // Shop::Gizmo

// Both coexist — no collision.
var_dump(class_exists('Shop::Widget'), class_exists('Shop::Gizmo'));
?>
--CLEAN--
<?php
$dir = __DIR__ . '/as_tmp';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
ab
ab
Shop::Widget
cd
cd
Shop::Gizmo
bool(true)
bool(true)
