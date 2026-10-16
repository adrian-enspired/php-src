--TEST--
Modules: a member's ::class folds in a constant expression
--DESCRIPTION--
A member's `::class` is a compile-time name literal ("M\C"), in constant expressions too (class
constants, top-level const). Identity is not gated, so it also works for an internal member.
--FILE--
<?php
module M {
    class C { const X = "well hello there"; }
    internal class D {}
}

class A {
    const B = M\C::class;         // -> "M\C"
    const I = M\D::class;         // internal member: identity, folds to "M\D"
}
const TOP = M\C::class;

echo A::B, "\n";
echo A::I, "\n";
echo TOP, "\n";

// The chained constant walk composes: A::B is "M\C", then ::X reads C's constant.
echo A::B::X, "\n";
?>
--EXPECT--
M\C
M\D
M\C
well hello there
