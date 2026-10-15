--TEST--
Modules: module/internal are contextual keywords; existing non-class uses as names keep working
--DESCRIPTION--
Only `module <name>`, `module::` and `internal` in a modifier position are keywords. Classes
named Module (also as types), and functions, constants, enum cases and members named
Module/Internal keep working. `Internal` is a reserved class name (like a visibility keyword).
--FILE--
<?php
namespace App {
    class Module { const X = 'x'; static function create() { return new static; } }
    function module() { return 'fn module'; }
    function internal () { return 'fn internal'; }
    const MODULE = 'const MODULE';
    const INTERNAL = 'const INTERNAL';
    class Holder {
        public ?Module $m = null;
        public Module|null $n = null;
        function take(Module $m): Module { return $m; }
        function __construct(public ?Module $promoted = null) {}
    }
    enum E { case Module; case Internal; }
    class Registry {
        const module = 'c'; const internal = 'c';
        public $internal = 'p';
        function module() { return 'm'; } function internal() { return 'm'; }
    }
}
namespace {
    use App\Module;
    use App\Module as Mod;
    var_dump(Module::class, Module::X, Module::create() instanceof Module, new Module instanceof Mod);
    var_dump(App\module(), App\internal(), App\MODULE, App\INTERNAL);
    $h = new App\Holder;
    var_dump($h->take(new Module) instanceof Module);
    var_dump(App\E::Module->name, App\E::Internal->name);
    class Sub extends Module {}
    var_dump(new Sub instanceof Module);
    $r = new App\Registry;
    echo $r->module(), $r->internal(), App\Registry::module, App\Registry::internal, $r->internal, "\n";

    // module syntax is unaffected
    module M {
        internal class H { static function k() { return module::K; } }
        const K = 'k';
        public class W {
            internal ?int $a = null;
            internal $b;
            public internal(set) int $c = 1;
            static function go() { return module::H::k() . (new module::H instanceof module::H ? '+' : '-'); }
        }
    }
    var_dump(M::W::go());

    class_alias('stdClass', 'Internal');
}
?>
--EXPECTF--
string(10) "App\Module"
string(1) "x"
bool(true)
bool(true)
string(9) "fn module"
string(11) "fn internal"
string(12) "const MODULE"
string(14) "const INTERNAL"
bool(true)
string(6) "Module"
string(8) "Internal"
bool(true)
mmccp
string(2) "k+"

Fatal error: Cannot use "internal" as a class alias as it is reserved in %s on line %d
