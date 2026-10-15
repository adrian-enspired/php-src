--TEST--
Modules: a public return type compiled cold is verified at runtime, whatever the spelling
--DESCRIPTION--
A member file compiled before its module definition (the PSR-4 order) cannot decide whether a
return type is public. Every spelling -- bare, `module::`, `\F::Impl`, `\F\Impl` -- is deferred and
checked against the resolved class on return, including a public member of an internal nested
module. Public types pass; an internal class or function (resolved at runtime) is not public
surface and is skipped.
--FILE--
<?php
$dir = __DIR__ . '/m318_tmp';
@mkdir($dir);
file_put_contents("$dir/member.php", <<<'PHP'
<?php
module F;
class Impl {}
interface Pub {}
class PubImpl implements Pub {}
class Api {
    public function bare(): Impl { return new Impl; }
    public function self_(): module::Impl { return new Impl; }
    public function canon(): \F::Impl { return new Impl; }
    public function proj(): \F\Impl { return new Impl; }
    public function nested(): \F::In::G { return new \F::In::G; }
    public function ok(): Pub { return new PubImpl; }
    public function okCanon(): \F::Pub { return new PubImpl; }
    public static function viaHidden(): string { return get_class((new Hidden)->f()); }
    public static function viaInternalFn(): string { return get_class(module::qf()); }
}
class Hidden { public function f(): \F::Impl { return new Impl; } }
function pf(): \F::Impl { return new Impl; }
function qf(): \F::Impl { return new Impl; }
PHP);
file_put_contents("$dir/def.php", <<<'PHP'
<?php
module F {
    public Api; public Pub; public PubImpl; internal Hidden; public pf; internal qf;
    internal module In { public class G {} }
}
PHP);
// PSR-4 order: the member file compiles first (cold); its `module F;` autoloads the definition.
spl_autoload_register(function ($c) use ($dir) { if (strtolower($c) === 'f') { require "$dir/def.php"; } });
require "$dir/member.php";
$api = new F::Api;
foreach (['bare', 'self_', 'canon', 'proj', 'nested', 'ok', 'okCanon'] as $m) {
    try { $r = get_class($api->$m()); echo "$m: $r\n"; }
    catch (Error $e) { echo "$m: ", $e->getMessage(), "\n"; }
}
echo "viaHidden: ", F::Api::viaHidden(), "\n";
echo "viaInternalFn: ", F::Api::viaInternalFn(), "\n";
try { F::pf(); echo "pf: LEAKED\n"; } catch (Error $e) { echo "pf: ", $e->getMessage(), "\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m318_tmp';
@unlink("$dir/member.php");
@unlink("$dir/def.php");
@rmdir($dir);
?>
--EXPECT--
bare: the return type of F::Api::bare() references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
self_: the return type of F::Api::self_() references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
canon: the return type of F::Api::canon() references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
proj: the return type of F::Api::proj() references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
nested: the return type of F::Api::nested() references "F::In::G", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
ok: F::PubImpl
okCanon: F::PubImpl
viaHidden: F::Impl
viaInternalFn: F::Impl
pf: the return type of F::pf() references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)