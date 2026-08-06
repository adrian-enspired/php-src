--TEST--
Modules: Reflection bypasses `internal` for construction, exactly as it does `private`
--DESCRIPTION--
An internal class cannot be `new`'d from outside its module -- ordinary syntax, both
literal and dynamic (`new $class`), is denied. ReflectionClass::newInstance() and
newInstanceArgs() are unaffected by the module gate, matching how they already bypass
a private/protected constructor. This is intended, not a gap: it is the one guaranteed
seam a test runner can rely on to construct an internal type without cooperation from
the module itself.
--FILE--
<?php
module M {
    internal class N {
        internal function f(): int { return 42; }
    }
}

echo "literal new: ";
try { new M::N(); echo "LEAKED\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

echo "dynamic new: ";
$class = 'M::N';
try { new $class(); echo "LEAKED\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

echo "ReflectionClass::newInstance: ";
$obj = (new ReflectionClass('M::N'))->newInstance();
echo get_class($obj), "\n";

echo "ReflectionClass::newInstanceArgs: ";
$obj2 = (new ReflectionClass('M::N'))->newInstanceArgs([]);
echo get_class($obj2), "\n";

echo "ReflectionClass::newInstanceWithoutConstructor: ";
$obj3 = (new ReflectionClass('M::N'))->newInstanceWithoutConstructor();
echo get_class($obj3), "\n";
?>
--EXPECT--
literal new: Cannot access internal module member "M::N" from outside its module
dynamic new: Cannot access internal module member "M::N" from outside its module
ReflectionClass::newInstance: M::N
ReflectionClass::newInstanceArgs: M::N
ReflectionClass::newInstanceWithoutConstructor: M::N
