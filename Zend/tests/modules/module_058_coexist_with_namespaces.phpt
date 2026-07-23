--TEST--
Modules: with bracketed namespaces, a bare module between blocks is rejected (no code outside namespace {})
--DESCRIPTION--
Under Decision A a module is namespace-positioned, so it obeys the same "no code outside
namespace {}" rule as any other declaration. With bracketed namespaces it must live inside
a block — `namespace X { module Z { … } }` (module X\Z, see module_087) or the global
`namespace { module R { … } }` for a root module — not bare between blocks.
--FILE--
<?php
namespace A { class D {} }
module Xy { public class C {} }
namespace B { }
?>
--EXPECTF--
Fatal error: No code may exist outside of namespace {} in %s on line %d
