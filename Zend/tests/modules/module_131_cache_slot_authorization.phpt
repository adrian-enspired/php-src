--TEST--
Modules: a runtime cache slot caches resolution, never authorization
--DESCRIPTION--
A populated class cache slot must not license a use the gate would refuse. Two ways that
went wrong:

  * CATCH / INSTANCEOF may legally name an internal class (observation is permitted). They
    share a class cache slot with NEW / FETCH_CLASS, so the legal observation resolved the
    CE into the slot and the gated use read it straight back.
  * A CLOSURE's scope is not fixed. Binding a module member into one at compile time on
    the strength of its declaring scope is stale the moment Closure::bind() moves it.

Both must agree with the interpreter.
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
module Bank {
    internal class Overdraft extends \RuntimeException {}
    public class Api {
        public static function fail(): void { throw new module::Overdraft('insufficient'); }
        public static function leak(): \Closure {
            return static function () {
                try { return \Bank::Overdraft::class . '/' . (new \Bank::Overdraft('x'))->getMessage(); }
                catch (\Error $e) { return 'denied'; }
            };
        }
    }
}
class Elsewhere {}

// Observation by the internal name is legal and must keep working.
try { Bank::Api::fail(); } catch (Bank::Overdraft $e) { echo "catch: ", $e->getMessage(), "\n"; }
try { Bank::Api::fail(); } catch (\RuntimeException $e) { var_dump($e instanceof Bank::Overdraft); }

// ...and must not have warmed a slot that a gated use can reuse.
try { new Bank::Overdraft('y'); echo "new after catch: LEAKED\n"; }
catch (\Error $e) { echo "new after catch: denied\n"; }

// A closure built inside the module may use the internal class; rebound out, it may not.
$c = Bank::Api::leak();
echo "closure in module : ", (strpos($c(), 'Bank::Overdraft') === 0 ? 'reached' : $c()), "\n";
$rebound = \Closure::bind($c, null, Elsewhere::class);
echo "closure rebound   : ", $rebound(), "\n";
?>
--EXPECT--
catch: insufficient
bool(true)
new after catch: denied
closure in module : reached
closure rebound   : denied
