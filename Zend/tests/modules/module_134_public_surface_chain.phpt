--TEST--
Modules: the public-surface check walks the "::" chain outermost-first
--DESCRIPTION--
Reachability of a chained name is decided from the OUTERMOST name downward, and the first
non-public link short-circuits: once a container is unreachable, nothing beneath it is
reachable either, whatever the inner declarations say.

Checking only the last link passes "M::N::C" when C is public in N but N is internal to M --
publishing a type no caller can name. The diagnostic must name the link that actually failed
(N in M), not the leaf.
--FILE--
<?php
module Ok {
    public module Pub { public class C {} }
    public class Facade {
        public function fine(): \Ok::Pub::C { return new \Ok::Pub::C(); }
    }
}
echo "public chain compiles: ", get_class((new Ok::Facade)->fine()), "\n";

// leaf is public, but its container is not
$src = <<<'PHP'
<?php
module Bad {
    internal module Hidden { public class C {} }
    public class Facade {
        public function leak(): \Bad::Hidden::C { return new \Bad::Hidden::C(); }
    }
}
PHP;
$f = tempnam(sys_get_temp_dir(), 'm134') . '.php';
file_put_contents($f, $src);
$php = getenv('TEST_PHP_EXECUTABLE');
echo shell_exec(escapeshellarg($php) . ' -n -l ' . escapeshellarg($f) . ' 2>&1');
@unlink($f);
?>
--EXPECTF--
public chain compiles: Ok::Pub::C

Fatal error: the return type of Bad::Facade::leak() references "Bad::Hidden::C", which is not reachable from outside the module: "Bad::Hidden" is not a public member of module "Bad"; a module's public surface may not expose internal or unclaimed types (declare a public supertype instead) in %s on line %d
%A
