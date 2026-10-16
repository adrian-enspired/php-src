--TEST--
Modules: an anonymous class declared in module code belongs to that module
--DESCRIPTION--
It belongs to the module it was declared in (like a module closure), whatever it extends: it
reaches that module's internals, and no more -- extending a class of a nested module does not
place it in the nested module. Its name starts with the module's (unless its parent's already does).
--FILE--
<?php
module M {
    internal class Secret {}
    internal function hid() { return 'hid'; }
    public module In { internal class Deep {} public class X {} }
    public class Base {}
    public class Api {
        static function all() {
            $r = [];
            foreach ([
                'plain' => fn() => new class { function f() { return [get_class(new module::Secret), module::hid()]; } },
                'extends Base' => fn() => new class extends module::Base { function f() { return [get_class(new module::Secret), module::hid()]; } },
                'extends In\X (no M\In access)' => fn() => new class extends module::In\X { function f() { return [get_class(new \M\In\Deep)]; } },
            ] as $k => $mk) {
                $o = $mk();
                try { $r[] = "$k: " . strstr(get_class($o), "\0", true) . ' -> ' . implode(',', $o->f()); } catch (\Error $e) { $r[] = "$k: " . strstr(get_class($o), "\0", true) . ' -> ' . $e->getMessage(); }
            }
            return implode("\n", $r);
        }
    }
}
echo M\Api::all(), "\n";
$o = new class { function f() { return new M\Secret; } };
try { $o->f(); echo "LEAK\n"; } catch (Error $e) { echo "outside anon: ", $e->getMessage(), "\n"; }
echo strstr(get_class($o), "\0", true), "\n";
?>
--EXPECT--
plain: M\class@anonymous -> M\Secret,hid
extends Base: M\Base@anonymous -> M\Secret,hid
extends In\X (no M\In access): M\In\X@anonymous -> Cannot access internal module member "M\In\Deep" from outside its module
outside anon: Cannot access internal module member "M\Secret" from outside its module
class@anonymous
