--TEST--
Modules: a module member's name used as a value is an ordinary (undefined) constant
--DESCRIPTION--
Members have ordinary namespaced names, so "M\Widget" in value position is a constant lookup like
any other, and "M\f" / "M::Nope" are constants of the module's backing class. Accesses through
the member's name ("M\Widget::K", "M\Widget::class") resolve as for any class.
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
foreach (['M\Widget', 'M\f', 'M::Nope'] as $expr) {
    try { var_dump(eval("return $expr;")); } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
// ... but chains through it still resolve, as does a real module constant
var_dump(M\C, M\Widget::K, M\Widget::s(), M\Widget::class);
// constant() and constant expressions agree
try { constant('M\Widget'); } catch (Error $e) { echo $e->getMessage(), "\n"; }
class Q { const X = M\Widget::class; }
var_dump(Q::X);
?>
--EXPECT--
Undefined constant "M\Widget"
Undefined constant "M\f"
Undefined constant M::Nope
string(1) "c"
string(1) "k"
string(1) "s"
string(8) "M\Widget"
Undefined constant "M\Widget"
string(8) "M\Widget"
