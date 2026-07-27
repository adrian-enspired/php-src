--TEST--
Modules: the optimizer may not bind a module member the runtime gate would deny
--DESCRIPTION--
Enforcement of `internal` is a RUNTIME gate. Any compile-time step that resolves a module
member to a CE and then folds a constant, inlines a call, or shares a class cache slot must
first ask the same question the gate asks — otherwise the optimized build is more permissive
than the interpreted one. Three distinct paths got this wrong:

  PASS_1  constant folding   -- guarded the CONSTANT's internal flag, never its CLASS's
  PASS_16 call inlining      -- checked PHP visibility (public/same-scope) but no module
  PASS_11 literal compaction -- CATCH/INSTANCEOF (legal observation) shared one class cache
                                slot with NEW/FETCH_CLASS (gated), so a legal observation
                                populated the slot and the gated use read it back

Run with the optimizer fully enabled; every probe must agree with the interpreter.
--SKIPIF--
<?php
if (!extension_loaded('Zend OPcache')) die('skip requires opcache');
?>
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.file_update_protection=0
--FILE--
<?php
module Vault {
    internal class Sealed extends \RuntimeException {
        const TAG = 'sealed-tag';
        public static function make(): string { return 'sealed-make'; }
        public static $slot = 'sealed-slot';
    }
    public class Open {
        internal const  IK = 'internal-const';
        internal static function guts(): string { return 'internal-method'; }
        public   static function ok(): string { return 'ok'; }
    }
    internal const MC = 'module-const';
    internal function mf(): string { return 'module-fn'; }

    internal module Inner {
        public const IV = 'inner-public-const';
        public class Gadget { public static function tag(): string { return 'gadget'; } }
    }

    public class Thrower { public static function boom(): void { throw new module::Sealed('x'); } }
}

// Legal observation of an internal type from outside: catching and instanceof by name.
// These must keep working AND must not warm a cache slot that a gated use can reuse.
try { Vault::Thrower::boom(); }
catch (Vault::Sealed $e) { echo "catch by internal name: ", $e->getMessage(), "\n"; }
try { Vault::Thrower::boom(); }
catch (\RuntimeException $e) { var_dump($e instanceof Vault::Sealed); }

$probes = [
    'internal class :: method' => fn() => Vault::Sealed::make(),
    'internal class :: const'  => fn() => Vault::Sealed::TAG,
    'internal class :: $prop'  => fn() => Vault::Sealed::$slot,
    'internal class :: new'    => fn() => new Vault::Sealed('y'),
    'internal method'          => fn() => Vault::Open::guts(),
    'internal const'           => fn() => Vault::Open::IK,
    'module const'             => fn() => Vault::MC,
    'module function'          => fn() => Vault::mf(),
    'internal module :: const' => fn() => Vault::Inner::IV,
    'internal module :: class' => fn() => Vault::Inner::Gadget::tag(),
];
foreach ($probes as $label => $probe) {
    try { $probe(); echo str_pad($label, 26), ": LEAKED\n"; }
    catch (\Error $e) { echo str_pad($label, 26), ": denied\n"; }
}

// Public surface is unaffected, and in-module code still reaches everything.
echo "public method             : ", Vault::Open::ok(), "\n";
?>
--EXPECT--
catch by internal name: x
bool(true)
internal class :: method  : denied
internal class :: const   : denied
internal class :: $prop   : denied
internal class :: new     : denied
internal method           : denied
internal const            : denied
module const              : denied
module function           : denied
internal module :: const  : denied
internal module :: class  : denied
public method             : ok
