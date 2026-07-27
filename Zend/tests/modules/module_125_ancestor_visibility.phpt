--TEST--
Modules: internal visibility runs DOWN the containment chain (ancestor-or-self), never up or sideways
--DESCRIPTION--
A module's `internal` members are reachable from that module and from any module nested
beneath it, to any depth. They are NOT reachable from a containing module (up), nor from a
sibling or cousin module (sideways). The rule is a single ancestor-or-self test on the
canonical module path, applied identically to every member kind.
--FILE--
<?php
module X {
    internal class XSecret { public static function tag(): string { return "x-secret"; } }
    internal const XCONST = "x-const";

    public class Root {
        // X reaching DOWN into X::Y's internals must be denied.
        public static function down(): string {
            try { return \X::Y::YSecret::tag(); }
            catch (\Error $e) { return "denied"; }
        }
    }

    public module Y {
        internal class YSecret { public static function tag(): string { return "y-secret"; } }

        public class Mid {
            public static function up():   string { return \X::XSecret::tag(); }
            public static function konst(): string { return \X::XCONST; }
            public static function own():  string { return module::YSecret::tag(); }
        }

        public module Z {
            public class Deep {
                // Two levels up, both reachable.
                public static function up1(): string { return \X::Y::YSecret::tag(); }
                public static function up2(): string { return \X::XSecret::tag(); }
                public static function konst(): string { return \X::XCONST; }
                // A cousin's internals must NOT be reachable.
                public static function cousin(): string {
                    try { return \X::W::WSecret::tag(); }
                    catch (\Error $e) { return "denied"; }
                }
            }
        }
    }

    public module W {
        internal class WSecret { public static function tag(): string { return "w-secret"; } }
        public class Sib {
            // A sibling's internals must NOT be reachable.
            public static function sibling(): string {
                try { return \X::Y::YSecret::tag(); }
                catch (\Error $e) { return "denied"; }
            }
        }
    }
}

echo "-- up the chain (allowed) --\n";
echo "X::Y      -> X::XSecret  : ", X::Y::Mid::up(), "\n";
echo "X::Y      -> X::XCONST   : ", X::Y::Mid::konst(), "\n";
echo "X::Y      -> own internal: ", X::Y::Mid::own(), "\n";
echo "X::Y::Z   -> X::Y secret : ", X::Y::Z::Deep::up1(), "\n";
echo "X::Y::Z   -> X secret    : ", X::Y::Z::Deep::up2(), "\n";
echo "X::Y::Z   -> X::XCONST   : ", X::Y::Z::Deep::konst(), "\n";

echo "-- down the chain (denied) --\n";
echo "X         -> X::Y secret : ", X::Root::down(), "\n";

echo "-- sideways (denied) --\n";
echo "X::W      -> X::Y secret : ", X::W::Sib::sibling(), "\n";
echo "X::Y::Z   -> X::W secret : ", X::Y::Z::Deep::cousin(), "\n";

echo "-- from outside the tree (denied) --\n";
try { X::XSecret::tag(); echo "LEAKED\n"; } catch (\Error $e) { echo "outside   -> X secret    : denied\n"; }
try { echo X::XCONST; echo "LEAKED\n"; } catch (\Error $e) { echo "outside   -> X::XCONST   : denied\n"; }
?>
--EXPECT--
-- up the chain (allowed) --
X::Y      -> X::XSecret  : x-secret
X::Y      -> X::XCONST   : x-const
X::Y      -> own internal: y-secret
X::Y::Z   -> X::Y secret : y-secret
X::Y::Z   -> X secret    : x-secret
X::Y::Z   -> X::XCONST   : x-const
-- down the chain (denied) --
X         -> X::Y secret : denied
-- sideways (denied) --
X::W      -> X::Y secret : denied
X::Y::Z   -> X::W secret : denied
-- from outside the tree (denied) --
outside   -> X secret    : denied
outside   -> X::XCONST   : denied
