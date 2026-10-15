--TEST--
Modules: inline members keep an ordinary namespaced name (non-breaking adoption)
--DESCRIPTION--
An inline member "V\M::W" is also reachable as "V\M\W", exactly as a member-file member is;
visibility is enforced under both names, and the ordinary name is reserved.
--FILE--
<?php
// Inline members keep an ordinary (namespaced) name, like member-file members.
namespace V {
    module M {
        public class W {}
        internal class I {}
        interface X {}
        public module N { public class D {} }
    }
}
namespace {
    var_dump(get_class(new \V\M\W), new \V\M\W instanceof \V\M::W, get_class(new \V\M\N\D));
    try { new \V\M\I; } catch (Error $e) { echo $e->getMessage(), "\n"; }
    var_dump(interface_exists('V\M\X'), class_exists('V\M\W'));
    function f(\V\M\W $w): \V\M::W { return $w; }
    var_dump(f(new V\M::W) instanceof \V\M\W);

    // the ordinary name is reserved: a separate class may not take it
    $f = __DIR__ . '/module_311.inc';
    file_put_contents($f, "<?php\nnamespace Q { class W {} }\nnamespace { module Q { public class W {} } }\n");
    $out = shell_exec((getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY) . ' -n ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    echo preg_replace('/ in \/.*/', '', strtok(trim($out), "\n")), "\n";
}
?>
--EXPECT--
string(6) "V\M::W"
bool(true)
string(9) "V\M::N::D"
Cannot access internal module member "V\M\I" from outside its module
bool(true)
bool(true)
bool(true)
Fatal error: Cannot register module member name "Q\W" for "Q::W": a class or module member with that name already exists
