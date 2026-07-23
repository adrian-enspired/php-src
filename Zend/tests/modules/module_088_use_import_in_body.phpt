--TEST--
Modules: `use` imports still resolve inside a module body (imports kept, namespace suppressed)
--DESCRIPTION--
While a manifest's member bodies compile, the enclosing namespace is suppressed (so members
are module-scoped, not namespace-scoped), but the file's `use` imports are left active — an
alias like `Plug` still resolves to its imported target inside the module.
--FILE--
<?php
namespace Ext {
    interface Plug {}
}

namespace App {
    use Ext\Plug;                                // alias Plug -> Ext\Plug
    module M {
        public class Widget implements Plug {}   // Plug resolves via the import
    }
}

namespace {
    var_dump((new App\M::Widget) instanceof Ext\Plug);
    echo (new App\M::Widget)::class, "\n";
}
?>
--EXPECT--
bool(true)
App\M::Widget
