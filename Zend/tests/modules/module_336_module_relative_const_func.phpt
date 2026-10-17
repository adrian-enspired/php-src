--TEST--
Modules: module::Sub\K and module::Sub\f() reach constants and functions in sub-namespaces and nested modules
--DESCRIPTION--
`module::` names a member relative to the enclosing module, so a member file in any
sub-namespace can reach the module's other namespaces without spelling the module name.
--FILE--
<?php
namespace {
module M {
    public Other\Probe;
    public module Inner {
        public const K = 'innerK';
        public function f() { return 'innerf'; }
        internal const H = 'innerH';
    }
}
}
namespace M\Sub { const SK = 'subK'; function sf() { return 'subf'; } }
namespace {
eval(<<<'PHP'
module M;
namespace Other;
class Probe {
    const CE = module::Inner\K;
    static function d($x = module::Sub\SK) { return $x; }
    static function run() {
        echo module::Inner\K, ' ', module::Inner\f(), ' ', module::Sub\SK, ' ', module::Sub\sf(), "\n";
        echo self::CE, ' ', self::d(), ' ', (module::Inner\f(...))(), "\n";
        try { echo module::Inner\H; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
        try { echo module::Nope\X; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
        try { module::Nope\g(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
    }
}
PHP);
M\Other\Probe::run();
}
?>
--EXPECT--
innerK innerf subK subf
innerK subK innerf
Cannot access internal module constant M\Inner\H from outside its module
Undefined constant "M\Nope\X"
Call to undefined function M\Nope\g()
