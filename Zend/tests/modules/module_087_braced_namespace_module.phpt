--TEST--
Modules: a module inside a bracketed namespace takes that namespace as its prefix
--DESCRIPTION--
The namespace prefix is read from FC(current_namespace), which a bracketed `namespace X\Y {
… }` sets just as the semicolon form does. So a definition block inside a bracketed namespace
is prefixed identically — no special-casing, and no reason to forbid it.
--FILE--
<?php
namespace X\Y {
    module Z {
        public class C {}
    }
}

namespace {
    // In the global namespace, "X\Y\Z::C" resolves absolutely.
    var_dump(class_exists('X\Y\Z::C'));
    echo (new X\Y\Z::C)::class, "\n";
}
?>
--EXPECT--
bool(true)
X\Y\Z::C
