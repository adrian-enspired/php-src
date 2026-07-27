--TEST--
Modules: an internal member is gated identically whatever its kind — class, constant, or nested module
--DESCRIPTION--
Every member's visibility is its CONTAINER's decision, and the accessor must be able to
reach that container. That is one question, asked the same way for a class, an interface,
a constant, a function, and a nested module's backing class. So a descendant that reaches
an ancestor's internal class also reaches the ancestor's internal nested module — and, by
crossing into it, that module's PUBLIC members only; the internal module's own internals
stay its own (the descendant is below the parent, not below the internal module).
--FILE--
<?php
module X {
    internal class  Cls  { public static function tag(): string { return "CLASS"; } }
    internal const  KON  = "CONST";
    internal module N {
        public   class Pub  { public static function tag(): string { return "MODULE-PUBLIC"; } }
        internal class Priv { public static function tag(): string { return "MODULE-INTERNAL"; } }
    }

    public class Direct {
        public static function cls(): string { return \X::Cls::tag(); }
        public static function kon(): string { return \X::KON; }
        public static function mod(): string { return \X::N::Pub::tag(); }
        public static function deep(): string {
            try { return \X::N::Priv::tag(); } catch (\Error $e) { return "denied"; }
        }
    }
    public module Y { public module Z {
        public class Deep {
            public static function cls(): string { return \X::Cls::tag(); }
            public static function kon(): string { return \X::KON; }
            public static function mod(): string { return \X::N::Pub::tag(); }
            public static function deep(): string {
                try { return \X::N::Priv::tag(); } catch (\Error $e) { return "denied"; }
            }
        }
    } }
}

printf("%-26s class=%-6s const=%-6s module=%-14s module-internal=%s\n",
    "X::Direct (direct)", X::Direct::cls(), X::Direct::kon(), X::Direct::mod(), X::Direct::deep());
printf("%-26s class=%-6s const=%-6s module=%-14s module-internal=%s\n",
    "X::Y::Z::Deep (nested)", X::Y::Z::Deep::cls(), X::Y::Z::Deep::kon(), X::Y::Z::Deep::mod(), X::Y::Z::Deep::deep());

echo "-- from outside X: every kind denied --\n";
foreach ([
    'class'  => fn() => X::Cls::tag(),
    'const'  => fn() => X::KON,
    'module' => fn() => X::N::Pub::tag(),
] as $label => $probe) {
    try { $probe(); echo "$label: LEAKED\n"; } catch (\Error $e) { echo "$label: denied\n"; }
}
?>
--EXPECT--
X::Direct (direct)         class=CLASS  const=CONST  module=MODULE-PUBLIC  module-internal=denied
X::Y::Z::Deep (nested)     class=CLASS  const=CONST  module=MODULE-PUBLIC  module-internal=denied
-- from outside X: every kind denied --
class: denied
const: denied
module: denied
