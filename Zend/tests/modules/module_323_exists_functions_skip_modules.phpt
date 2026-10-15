--TEST--
Modules: class_exists() and friends report a module as no class-like, on every call path
--DESCRIPTION--
Direct calls compile to the frameless variant; dynamic calls use the regular function; a name
whose class entry is already cached takes a fast path. All must agree. Member classes are classes.
--FILE--
<?php
module W { public class C {} public module In {} }
$f = 'class_exists';
var_dump(
    class_exists('W'),                   // frameless
    class_exists('W', false),            // frameless, no autoload
    $f('W'),                             // regular function
    class_exists('W'),                   // again, name now cached
    class_exists('W::In'),               // nested module
    interface_exists('W'), trait_exists('W'), enum_exists('W'),
    class_exists('W::C'),                // a member class is a class
    in_array('W', get_declared_classes()),
);
new ReflectionModule('W');             // the module itself exists
echo "ok\n";
?>
--EXPECT--
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(true)
bool(false)
ok
