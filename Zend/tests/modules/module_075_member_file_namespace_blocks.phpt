--TEST--
Modules (B): a membership file may mix module-root members (global "namespace { }") with a sub-namespace that projects members outward
--DESCRIPTION--
A `module Shop;` membership file can wrap module-root members in a bracketed global
`namespace { }` block alongside a `namespace Sub { }` block. Root members are Shop::Widget /
Shop::Gadget; a member declared under `namespace Sub` is canonically Shop::Deep (module-rooted
on its tail) and ALSO projects its namespace name Sub\Deep. Visibility comes from the module's
claims.
--FILE--
<?php
$dir = __DIR__ . '/mfnb_tmp';
@mkdir($dir);

file_put_contents("$dir/Shop.def.php", <<<'PHP'
<?php
module Shop {
    public Widget;        // claimed public (module-root member)
    internal Gadget;      // claimed internal (module-root member)
    public Sub\Deep;      // claim keys on the tail -> Shop::Deep (projects as Sub\Deep)
}
PHP);

file_put_contents("$dir/Shop.bodies.php", <<<'PHP'
<?php
module Shop;
namespace {
    class Widget { public function tag(): string { return "widget"; } }
    class Gadget { public function tag(): string { return "gadget"; } }
}
namespace Sub {
    class Deep { public function tag(): string { return "deep"; } }
}
PHP);

spl_autoload_register(function ($name) use ($dir) {
    if (!str_contains($name, 'Shop')) return;
    require_once "$dir/Shop.def.php";       // definition first (claims in place)
    require_once "$dir/Shop.bodies.php";    // then the bodies
});

echo (new Shop::Widget)->tag(), "\n";              // root member -> Shop::Widget
echo (new Shop::Deep)->tag(), "\n";                // sub-namespace member -> Shop::Deep
echo Shop::Widget::class, "\n";                    // canonical name of the root member
echo Shop::Deep::class, "\n";                      // canonical name of the (tail) member

$rm = new ReflectionModule('Shop');
echo $rm->getSymbolVisibility('Shop::Widget'), "\n";   // public (from the claim)
echo $rm->getSymbolVisibility('Shop::Gadget'), "\n";   // internal (from the claim)
echo $rm->getSymbolVisibility('Shop::Deep'), "\n";     // public (from the claim)

// Visibility is enforced from outside per the claim, regardless of the member file shape.
try { new Shop::Gadget(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }

// A root member has no namespace -> no projection; a sub-namespace member projects outward.
var_dump(class_exists('Widget'));      // false (Shop::Widget only)
var_dump(class_exists('Sub\Deep'));    // true  (Deep's projection)
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mfnb_tmp';
@array_map('unlink', glob("$dir/*"));
@rmdir($dir);
?>
--EXPECT--
widget
deep
Shop::Widget
Shop::Deep
public
internal
public
Cannot access internal module member "Shop::Gadget" from outside its module
bool(false)
bool(true)
