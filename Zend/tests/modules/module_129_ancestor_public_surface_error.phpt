--TEST--
Modules: naming an enclosing module's internal type on a public surface is a compile error
--FILE--
<?php
module M {
    internal class Secret {}
    public module N {
        public class Api {
            public function leak(): \M::Secret { return new \M::Secret(); }
        }
    }
}
?>
--EXPECTF--
Fatal error: the return type of M::N::Api::leak() references "M::Secret", which is not a public member of its module; a module's public surface may not expose internal or unclaimed types of its own module or of an enclosing one (declare a public supertype instead) in %s on line %d
