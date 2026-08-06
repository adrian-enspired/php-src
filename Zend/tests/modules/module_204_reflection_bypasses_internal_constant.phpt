--TEST--
Modules: ReflectionClassConstant::getValue() bypasses an `internal` class constant
--DESCRIPTION--
Ordinary constant access (Class::CONST) is denied for an internal class constant from
outside the module, even on an otherwise-public class. ReflectionClassConstant reads
it anyway, same family of bypass as newInstance(), invoke(), and getValue() above --
Reflection treats `internal` exactly like `private` across every member kind, not just
methods.
--FILE--
<?php
module M {
    public class Box {
        internal const SECRET = "s3cr3t";
        public const PUBLIC_ONE = "fine";
    }
}

echo "ordinary access to internal const: ";
try { echo M::Box::SECRET, "\n"; echo "LEAKED\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

echo "ordinary access to public const: ", M::Box::PUBLIC_ONE, "\n";

echo "ReflectionClassConstant::getValue on internal const: ";
$rcc = (new ReflectionClass('M::Box'))->getReflectionConstant('SECRET');
echo $rcc->getValue(), "\n";
?>
--EXPECT--
ordinary access to internal const: Cannot access internal module constant M::Box::SECRET from outside its module
ordinary access to public const: fine
ReflectionClassConstant::getValue on internal const: s3cr3t
