--TEST--
Modules: "module::X" for a name that isn't a member of the current module fails when used
--DESCRIPTION--
Claims decide visibility, not existence: `module::X` may name an unclaimed member that isn't
loaded yet, so existence is checked when the name is used (the fetch autoloads), not at compile
time. A name that is no member at all is reported as such.
--FILE--
<?php
module M {
    public class A {}
    public class B {
        public function make() { return new module::Nope; }
        public function konst() { return module::Nope::K; }
    }
}
foreach (['make', 'konst'] as $m) {
    try { (new M::B)->$m(); } catch (Error $e) { echo $m, ': ', $e->getMessage(), "\n"; }
}
?>
--EXPECT--
make: "Nope" is not a member of module "M"
konst: "Nope" is not a member of module "M"
