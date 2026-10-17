--TEST--
Modules: ancestor access via the recorded nesting; the deferred return check is cached only once final
--DESCRIPTION--
A nested module reaches its ancestors' internals; a top-level module that merely sits in an
ancestor's namespace does not. The access gate reads the nesting recorded on the accessor's class
(no lookup for "parent" and "top-level"), and walks the backing classes beyond that. A passing
deferred return-type check is remembered for the request only once every named type resolved.
--FILE--
<?php
namespace {
module X {
    internal class H {}
    public module Y {
        public class K { static function f() { return get_class(new \X\H); } }
        public module W {
            public class D { static function f() { return get_class(new \X\H); } }
        }
        public M2;
    }
    public Late;
}
}
namespace X {
    module Z {   // top-level module X\Z, unrelated to X
        public class K2 { static function f() { return get_class(new \X\H); } }
    }
}
namespace {
eval('module X\Y; class M2 { static function f() { return get_class(new \X\H); } }');

echo "nested:           ", X\Y\K::f(), "\n";
echo "nested depth 2:   ", X\Y\W\D::f(), "\n";
echo "nested, file:     ", X\Y\M2::f(), "\n";
try { X\Z\K2::f(); echo "LEAK\n"; } catch (Error $e) { echo "top-level X\\Z:    ", $e->getMessage(), "\n"; }

// Deferred return check: Late isn't declared yet, so the first pass isn't final.
module R { public class Api { public function get(): ?Late { $x = null; return $x; } } }
$a = new R\Api;
var_dump($a->get());
eval('module R; class Late {}');   // unclaimed: internal
try { $a->get(); echo "LEAK\n"; } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECTF--
nested:           X\H
nested depth 2:   X\H
nested, file:     X\H
top-level X\Z:    Cannot access internal module member "X\H" from outside its module
NULL
the return type of R\Api::get() references "R\Late", which is not reachable from outside the module: %s
