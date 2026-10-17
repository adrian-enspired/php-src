--TEST--
Modules: "module::X" for a name that is no class fails when used (Class not found)
--DESCRIPTION--
Claims decide visibility, not existence: `module::X` may name an unclaimed member that isn't
loaded yet, so existence is checked when the name is used (the fetch autoloads), not at compile
time. A name that names no class is reported like any missing class.
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
    try { (new M\B)->$m(); } catch (Error $e) { echo $m, ': ', $e->getMessage(), "\n"; }
}
?>
--EXPECT--
make: Class "M\Nope" not found
konst: Class "M\Nope" not found
