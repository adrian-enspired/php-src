--TEST--
Modules: the ancestor test respects the "::" boundary — a shared name prefix is not containment
--DESCRIPTION--
The ancestor-or-self test is a prefix comparison over canonical module paths, so it must
only match on a "::" boundary. Module "X::Foo" is NOT an ancestor of the unrelated module
"X::Foober" even though its path is a leading substring, and top-level "M" is not an
ancestor of "MX". Without the boundary check these would silently gain access to each
other's internals. Only one byte need be tested: a module path is identifiers joined by
"::", and an identifier cannot contain ':'.
--FILE--
<?php
module X {
    public module Foo {
        internal class Secret { public static function tag(): string { return "foo-secret"; } }
        public class Api {
            public static function own(): string { return module::Secret::tag(); }
            // Reaching a name-prefix-sharing sibling must be denied.
            public static function reachFoober(): string {
                try { return \X::Foober::Secret::tag(); }
                catch (\Error $e) { return "denied"; }
            }
        }
    }
    public module Foober {
        internal class Secret { public static function tag(): string { return "foober-secret"; } }
        public class Api {
            public static function own(): string { return module::Secret::tag(); }
            // The dangerous direction: a LONGER path whose prefix matches a shorter one.
            public static function reachFoo(): string {
                try { return \X::Foo::Secret::tag(); }
                catch (\Error $e) { return "denied"; }
            }
        }
    }
}

module M {
    internal class Secret { public static function tag(): string { return "m-secret"; } }
}
module MX {
    public class Probe {
        public static function reachM(): string {
            try { return \M::Secret::tag(); }
            catch (\Error $e) { return "denied"; }
        }
    }
}

echo "X::Foo    own            : ", X::Foo::Api::own(), "\n";
echo "X::Foober own            : ", X::Foober::Api::own(), "\n";
echo "X::Foober -> X::Foo      : ", X::Foober::Api::reachFoo(), "\n";
echo "X::Foo    -> X::Foober   : ", X::Foo::Api::reachFoober(), "\n";
echo "MX        -> M           : ", MX::Probe::reachM(), "\n";
?>
--EXPECT--
X::Foo    own            : foo-secret
X::Foober own            : foober-secret
X::Foober -> X::Foo      : denied
X::Foo    -> X::Foober   : denied
MX        -> M           : denied
