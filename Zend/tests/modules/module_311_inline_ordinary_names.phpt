--TEST--
Modules: inline members have ordinary namespaced names (non-breaking adoption)
--DESCRIPTION--
An inline member is named "V\M\W", exactly as a member-file member is; visibility is enforced,
and the name is taken like any class's (a second class "Q\W" is a redeclaration).
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
    var_dump(get_class(new \V\M\W), new \V\M\W instanceof \V\M\W, get_class(new \V\M\N\D));
    try { new \V\M\I; } catch (Error $e) { echo $e->getMessage(), "\n"; }
    var_dump(interface_exists('V\M\X'), class_exists('V\M\W'));
    function f(\V\M\W $w): \V\M\W { return $w; }
    var_dump(f(new V\M\W) instanceof \V\M\W);

    // the name is taken: a separate class may not take it
    $f = __DIR__ . '/module_311.inc';
    file_put_contents($f, "<?php\nnamespace Q { class W {} }\nnamespace { module Q { public class W {} } }\n");
    $out = shell_exec((getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY) . ' -n ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    echo preg_replace('/ in \/.*/', '', strtok(trim($out), "\n")), "\n";
}
?>
--EXPECT--
string(5) "V\M\W"
bool(true)
string(7) "V\M\N\D"
Cannot access internal module member "V\M\I" from outside its module
bool(true)
bool(true)
bool(true)
Fatal error: Cannot redeclare class Q\W (previously declared
