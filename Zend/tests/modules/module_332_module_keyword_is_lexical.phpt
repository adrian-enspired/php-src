--TEST--
Modules: module:: means the module the code is written in; inherited code keeps its module
--DESCRIPTION--
Like self::, not static::. Inherited methods and closures in them run as their declaring
module wherever the subclass lives (another module, an anonymous class, global code); a
subclass's own methods use its own module.
--FILE--
<?php
module M {
    internal class Secret { const K = 'M-secret'; }
    internal function hid() { return 'M-hid'; }
    public class Base {
        function inherited() { return get_class(new module::Secret) . ' ' . module::hid() . ' ' . module::Secret::K; }
        function closure() { return (fn() => get_class(new module::Secret))(); }
    }
    public class Make {
        static function anon() { return new class extends module::Base { function own() { return get_class(new module::Secret); } }; }
    }
}
module N {
    internal class Secret { const K = 'N-secret'; }
    public class Make {
        static function anon() { return new class extends \M::Base { function own() { return get_class(new module::Secret); } }; }
    }
    public class Sub extends \M::Base { function own() { return get_class(new module::Secret); } }
}
class Plain extends M::Base {}
function show($label, $o) {
    foreach (['inherited', 'closure', 'own', 'fromTrait'] as $m) {
        if (!method_exists($o, $m)) continue;
        try { echo "$label->$m(): ", $o->$m(), "\n"; } catch (Error $e) { echo "$label->$m(): ERROR ", $e->getMessage(), "\n"; }
    }
}
show('anon in M extends M::Base', M::Make::anon());
show('anon in N extends M::Base', N::Make::anon());
show('N::Sub extends M::Base', new N::Sub);
show('global Plain extends M::Base', new Plain);
?>
--EXPECT--
anon in M extends M::Base->inherited(): M::Secret M-hid M-secret
anon in M extends M::Base->closure(): M::Secret
anon in M extends M::Base->own(): M::Secret
anon in N extends M::Base->inherited(): M::Secret M-hid M-secret
anon in N extends M::Base->closure(): M::Secret
anon in N extends M::Base->own(): N::Secret
N::Sub extends M::Base->inherited(): M::Secret M-hid M-secret
N::Sub extends M::Base->closure(): M::Secret
N::Sub extends M::Base->own(): N::Secret
global Plain extends M::Base->inherited(): M::Secret M-hid M-secret
global Plain extends M::Base->closure(): M::Secret
