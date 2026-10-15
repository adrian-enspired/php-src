--TEST--
Modules: outside a module, "module::X" is an ordinary name (module is a contextual keyword)
--DESCRIPTION--
`module::` means "the enclosing module" only inside a module. Elsewhere `Module` is an
ordinary class-like name, so pre-existing code referring to a class named Module keeps
working, and `use module::X` imports member X of a module named "Module".
--FILE--
<?php
namespace App {
    class Module { const K = 'k'; public static function create(): static { return new static; } }
}
namespace {
    use App\Module;
    var_dump(Module::class, Module::K, Module::create() instanceof Module);
}
namespace Lib {
    module Module { public class Foo {} }
}
namespace {
    use Lib\Module::Foo;
    var_dump(new Foo);
    try { new module::Foo; } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECTF--
string(10) "App\Module"
string(1) "k"
bool(true)
object(Lib\Module::Foo)#%d (0) {
}
Class "module::Foo" not found
