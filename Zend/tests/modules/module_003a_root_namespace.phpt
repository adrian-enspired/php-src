--TEST--
Modules: a "::"-canonical nested definition may not sit under a namespace (compile-time fatal)
--DESCRIPTION--
Under Decision B a member file MAY be namespace-first (`namespace A\B; module X\Y\Z;`, see
module_091), and a simple definition block takes its namespace as a prefix (module_086). But a
"::"-canonical nested *definition* block names the exact module by its canonical path and must
stay in the root namespace.
--FILE--
<?php
namespace X\Y;
module Outer::Inner { public class C {} }
?>
--EXPECTF--
Fatal error: Module definition "Outer::Inner" must be in the root namespace in %s on line %d
