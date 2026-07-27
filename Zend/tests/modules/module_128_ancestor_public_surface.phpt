--TEST--
Modules: a public surface may not name a non-public type of an ENCLOSING module
--DESCRIPTION--
Because a nested module can access its ancestors' internals, it could otherwise name one
in a public signature — and a public member of a public nested module is reachable from
outside the whole tree, so that would publish a type the ancestor declared internal. The
public-surface check therefore covers the module's own members AND every ancestor's. A
sibling or unrelated module is still skipped (it may be cold, so undecidable here) and is
left to the runtime gate.
--FILE--
<?php
module M {
    internal class Secret {}
    public   class Fine {}
    public module N {
        public class Api {
            // Legal: ancestor PUBLIC type in a public signature.
            public function ok(): \M::Fine { return new \M::Fine(); }
            // Legal: ancestor INTERNAL type, but not on the public surface.
            internal function hidden(): \M::Secret { return new \M::Secret(); }
        }
    }
}
echo "compiled: ", get_class((new M::N::Api)->ok()), "\n";
?>
--EXPECT--
compiled: M::Fine
