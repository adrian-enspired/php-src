--TEST--
Modules: ReflectionProperty::getValue()/setValue() bypass an `internal` property
--DESCRIPTION--
Ordinary property access on an `internal` property is denied from outside the module.
ReflectionProperty is unaffected -- both reading and writing the internal property
succeed from code that is not a member of the owning module, symmetric with what
Reflection already permits for private/protected properties.
--FILE--
<?php
module M {
    internal class Box {
        internal string $value = "start";
    }
}

$obj = (new ReflectionClass('M::Box'))->newInstanceWithoutConstructor();
$rp = (new ReflectionClass('M::Box'))->getProperty('value');

echo "ordinary read: ";
try { echo $obj->value, "\n"; echo "LEAKED\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

echo "ReflectionProperty::getValue: ", $rp->getValue($obj), "\n";

$rp->setValue($obj, "changed");
echo "ReflectionProperty::getValue after setValue: ", $rp->getValue($obj), "\n";
?>
--EXPECT--
ordinary read: Cannot access internal module property M::Box::$value from outside its module
ReflectionProperty::getValue: start
ReflectionProperty::getValue after setValue: changed
