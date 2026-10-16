--TEST--
Modules: every module has a backing class (identity marker), even with no static members
--FILE--
<?php
module Widgets {
    public class Button {}
    internal class Rivet {}
}

// A member-only module still materializes its backing class — the runtime identity
// marker used by autoload, reflection, and the shared-symbol rule.
// A module is not a class: class_exists() is false; the module is reflected as a module.
var_dump(class_exists("Widgets"), (new ReflectionModule("Widgets"))->getName());

// Not instantiable
try { new Widgets(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }

// Members still resolve as before
var_dump(new Widgets\Button instanceof Widgets\Button);
?>
--EXPECT--
bool(false)
string(7) "Widgets"
Cannot instantiate module Widgets
bool(true)
