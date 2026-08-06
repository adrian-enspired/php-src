--TEST--
Modules: Reflection bypasses an `internal` constructor on an otherwise-public class
--DESCRIPTION--
A `public class` with an `internal function __construct()` (the documented factory
pattern) refuses ordinary construction from outside the module even though the class
itself is nameable. Reflection's constructor call bypasses this the same way it
bypasses a class-level `internal` gate in module_200 -- the same fake-scope mechanism
that already lets Reflection call a private/protected constructor is what makes this
symmetrical.
--FILE--
<?php
module Billing {
    public class TaxReport {
        internal function __construct(public array $data) {}
    }
}

echo "ordinary new on a public class with an internal constructor: ";
try { new Billing::TaxReport(['a' => 1]); echo "LEAKED\n"; }
catch (\Error $e) { echo $e->getMessage(), "\n"; }

echo "ReflectionClass::newInstanceArgs: ";
$r = (new ReflectionClass('Billing::TaxReport'))->newInstanceArgs([['a' => 1]]);
echo get_class($r), ' data=', json_encode($r->data), "\n";
?>
--EXPECT--
ordinary new on a public class with an internal constructor: Cannot instantiate class Billing::TaxReport via internal constructor from outside its module
ReflectionClass::newInstanceArgs: Billing::TaxReport data={"a":1}
