--TEST--
Modules: a module member used as a value is an error, not its name string
--DESCRIPTION--
"M::Widget" in value position names a member, not a constant. Chains through it ("M::Widget::K",
"M::Widget::class", "new (M::Widget)") still resolve.
--FILE--
<?php
module M {
    public class Widget {
        const K = 'k';
        public static function s() { return 's'; }
    }
    public function f() { return 'f'; }
    const C = 'c';
}

// a module member used as a VALUE is not a constant
foreach (['M::Widget', 'M::f', 'M::Nope'] as $expr) {
    try { var_dump(eval("return $expr;")); } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
// ... but chains through it still resolve, as does a real module constant
var_dump(M::C, M::Widget::K, M::Widget::s(), M::Widget::class, new (M::Widget));
// constant() and constant expressions agree
try { constant('M::Widget'); } catch (Error $e) { echo $e->getMessage(), "\n"; }
class Q { const X = M::Widget::class; }
var_dump(Q::X);
?>
--EXPECT--
Undefined constant M::Widget: "M::Widget" is a member of module "M", not a constant
Undefined constant M::f: "M::f" is a member of module "M", not a constant
"Nope" is not a member of module "M"
string(1) "c"
string(1) "k"
string(1) "s"
string(9) "M::Widget"
object(M::Widget)#2 (0) {
}
Undefined constant M::Widget
string(9) "M::Widget"
