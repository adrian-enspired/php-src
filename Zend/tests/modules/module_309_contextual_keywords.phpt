--TEST--
Modules: module/internal are contextual keywords; existing uses as names keep working
--DESCRIPTION--
Only `module <name>`, `module::` and `internal` in a modifier position are keywords. Classes,
interfaces, functions, constants, enum cases and members named Module/Internal keep working,
including as types (`Module $m`, `Internal $i`, `catch (Internal $e)`). Before a variable,
`internal` is the type Internal: module visibility requires a type.
--FILE--
<?php
namespace App {
    class Module { const X = 'x'; static function create() { return new static; } }
    interface Internal {}
    class Impl implements Internal {}
    function module() { return 'fn module'; }
    function internal () { return 'fn internal'; }
    const MODULE = 'const MODULE';
    const INTERNAL = 'const INTERNAL';
    class Holder {
        public ?Module $m = null;
        public Module|null $n = null;
        public ?Internal $i = null;
        public static ?Internal $s = null;
        function take(Module $m): Module { return $m; }
        function accept(Internal $i, Internal ...$rest): Internal { return $i; }
        function __construct(public ?Internal $promoted = null) {}
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
    use App\Internal;
    use App\Module as Mod;
    var_dump(Module::class, Module::X, Module::create() instanceof Module, new Module instanceof Mod);
    var_dump(App\module(), App\internal(), App\MODULE, App\INTERNAL);
    $h = new App\Holder;
    var_dump($h->take(new Module) instanceof Module, $h->accept(new App\Impl) instanceof Internal);
    var_dump(App\E::Module->name, App\E::Internal->name);
    try { throw new Exception('x'); } catch (Internal|Exception $e) { echo "caught\n"; }
    var_dump(new App\Impl instanceof Internal and true);
    class Sub extends Module implements Internal {}
    var_dump(new Sub instanceof Internal);
    $r = new App\Registry;
    echo $r->module(), $r->internal(), App\Registry::module, App\Registry::internal, $r->internal, "\n";

    // module syntax is unaffected
    module M {
        internal class H { static function k() { return module::K; } }
        const K = 'k';
        public class W {
            internal ?int $a = null;
            internal mixed $b;
            public internal(set) int $c = 1;
            static function go() { return module::H::k() . (new module::H instanceof module::H ? '+' : '-'); }
        }
    }
    var_dump(M::W::go());
}
?>
--EXPECT--
string(10) "App\Module"
string(1) "x"
bool(true)
bool(true)
string(9) "fn module"
string(11) "fn internal"
string(12) "const MODULE"
string(14) "const INTERNAL"
bool(true)
bool(true)
string(6) "Module"
string(8) "Internal"
caught
bool(true)
bool(true)
mmccp
string(2) "k+"
