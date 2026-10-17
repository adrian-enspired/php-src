--TEST--
Modules: a module-qualified head that is not imported is relative to the current namespace
--DESCRIPTION--
A qualified name resolves its head like the first segment of any qualified name: `use` imports,
else the current namespace. Inside module code the current namespace is the module itself (as
for a bare class name), so in module N\M "Inner\Gadget" is "N\M\Inner\Gadget" -- the same
in `new`, types and expression chains -- and for backslash-qualified names ("Sub\X" is
"N\M\Sub\X"; "N\M\C" is "N\M\N\M\C"). A leading backslash is absolute.
--FILE--
<?php
namespace N;

module Other { public class X { const V = 'other'; } }
module M {
    public module Inner { public class Gadget { const V = 'gadget'; public static function f() { return 'f'; } } }
    public class Helper { const K = 'N\M\Inner\Gadget'; }
    public class C {
        public static function t(string $n, \Closure $f) {
            try { $r = $f(); echo "$n: ", is_object($r) ? $r::class : $r, "\n"; }
            catch (\Throwable $e) { echo "$n: ", $e->getMessage(), "\n"; }
        }
        public static function run() {
            self::t('new Inner\Gadget', fn() => new Inner\Gadget);
            self::t('Inner\Gadget::V', fn() => Inner\Gadget::V);
            self::t('Inner\Gadget::f()', fn() => Inner\Gadget::f());
            self::t('typed', fn() => (fn(Inner\Gadget $g) => $g)(new module::Inner\Gadget));
            self::t('Helper::K::V', fn() => Helper::K::V);
            self::t('module::Inner\Gadget::V', fn() => module::Inner\Gadget::V);
            self::t('\N\Other\X::V', fn() => \N\Other\X::V);
            self::t('new Other\X', fn() => new Other\X);
            self::t('new N\M\C', fn() => new N\M\C);
            self::t('N\M\Inner\Gadget::V', fn() => N\M\Inner\Gadget::V);
            self::t('new Sub\X', fn() => new Sub\X);
            self::t('new \N\M\C', fn() => new \N\M\C);
            self::t('\N\M\Inner\Gadget::V', fn() => \N\M\Inner\Gadget::V);
            self::t('typed \N\M\C', fn() => (fn(\N\M\C $c): \N\M\C => $c)(new C));
            self::t('instanceof \N\M\C', fn() => (new C) instanceof \N\M\C);
            self::t('\N\M\C::class', fn() => \N\M\C::class);
            self::t('new \N\M\C (ordinary name)', fn() => new \N\M\C);
        }
    }
}
use N\Other as O;
module Imp { public class Y { public static function viaImport() { return O\X::V; } } }

echo "-- inside module N\\M --\n";
M\C::run();
echo "-- outside, namespace N --\n";
M\C::t('new M\C', fn() => new M\C);
M\C::t('M\Inner\Gadget::V', fn() => M\Inner\Gadget::V);
M\C::t('new N\M\C', fn() => new N\M\C);
M\C::t('new \N\M\C', fn() => new \N\M\C);
M\C::t('import', fn() => Imp\Y::viaImport());
?>
--EXPECT--
-- inside module N\M --
new Inner\Gadget: N\M\Inner\Gadget
Inner\Gadget::V: gadget
Inner\Gadget::f(): f
typed: N\M\Inner\Gadget
Helper::K::V: gadget
module::Inner\Gadget::V: gadget
\N\Other\X::V: other
new Other\X: Class "N\M\Other\X" not found
new N\M\C: Class "N\M\N\M\C" not found
N\M\Inner\Gadget::V: Class "N\M\N\M\Inner\Gadget" not found
new Sub\X: Class "N\M\Sub\X" not found
new \N\M\C: N\M\C
\N\M\Inner\Gadget::V: gadget
typed \N\M\C: N\M\C
instanceof \N\M\C: 1
\N\M\C::class: N\M\C
new \N\M\C (ordinary name): N\M\C
-- outside, namespace N --
new M\C: N\M\C
M\Inner\Gadget::V: gadget
new N\M\C: Class "N\N\M\C" not found
new \N\M\C: N\M\C
import: other
