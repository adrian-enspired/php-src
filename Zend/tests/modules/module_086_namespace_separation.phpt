--TEST--
Modules: a `namespace` statement supplies the module's namespace (namespace X\Y; module Z {})
--DESCRIPTION--
A definition block takes a simple, unqualified name; its namespace comes from a preceding
`namespace X\Y;`, exactly like a class/interface/enum/trait. `namespace X\Y; module Z { … }`
declares the module "X\Y\Z" — the identity the (removed) fused "module X\Y\Z { … }" produced.
Inside the module body the enclosing namespace is suppressed, so members are module-scoped
(X\Y\Z::C), not namespace-scoped.
--FILE--
<?php
namespace X\Y;

module Z {
    public class C {}
    public const V = 42;
}

// The module's FQMN is X\Y\Z.
var_dump(class_exists('X\Y\Z::C'));   // canonical "::" boundary key
var_dump(class_exists('X\Y\C'));      // NOT a plain namespace path
var_dump(class_exists('X\Y\X\Y\Z::C')); // NOT double-prefixed by the namespace

// From inside namespace X\Y, reach the root-declared module with a leading "\".
$o = new \X\Y\Z::C;
echo $o::class, "\n";
echo \X\Y\Z::V, "\n";
?>
--EXPECT--
bool(true)
bool(false)
bool(false)
X\Y\Z::C
42
