--TEST--
Modules: get_class_methods()/get_class_vars() hide internal members from outside; modules are not classes
--DESCRIPTION--
Like private/protected, internal methods and properties are listed only for callers that could
access them. A module's backing class is not reported by class_exists()/get_declared_classes().
--FILE--
<?php
module M {
    public class P {
        public $pub = 1;
        internal $in = 2;
        internal static $sin = 3;
        public function a() {}
        internal function b() {}
        public static function inside(): array {
            return [get_class_methods(self::class), array_keys(get_class_vars(self::class))];
        }
    }
    public function fromModuleFunction(): array { return get_class_methods(P::class); }
}
echo implode(",", get_class_methods('M::P')), "\n";
echo implode(",", array_keys(get_class_vars('M::P'))), "\n";
[$m, $v] = M::P::inside();
echo implode(",", $m), " | ", implode(",", $v), "\n";
echo implode(",", M::fromModuleFunction()), "\n";
class Sub extends M::P {
    public function mine() { return get_class_methods($this); }
}
echo implode(",", (new Sub)->mine()), "\n";

// the backing class of a module is not a class
var_dump(class_exists('M'), class_exists('M::P'), in_array('M', get_declared_classes()), in_array('M::P', get_declared_classes()));
?>
--EXPECT--
a,inside
pub
a,b,inside | pub,in,sin
a,b,inside
mine,a,inside
bool(false)
bool(true)
bool(false)
bool(true)
