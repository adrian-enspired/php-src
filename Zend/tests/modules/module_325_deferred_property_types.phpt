--TEST--
Modules: public property types the compiler can't decide are checked on the class's first use
--DESCRIPTION--
A public property may not name a type unreachable from outside its module. When that can't be
decided at compile time (member file compiled before its definition; a bare name that misses the
claims, which could be an ordinary class in the module's namespace), the class is checked once per
request on first use (instantiation, static access), resolving the types. Public types and ordinary
classes pass; an internal class is not public surface and is skipped; subclasses inherit the check.
--FILE--
<?php
$dir = __DIR__ . '/m325_tmp';
@mkdir($dir);
file_put_contents("$dir/def.php", <<<'PHP'
<?php
module F {
    public Api; public Ok; public Pub; internal Hidden; public Statics; public Ext;
    internal module In { public class G {} }
}
PHP);
file_put_contents("$dir/mem.php", <<<'PHP'
<?php
module F;
class Impl {}
interface Pub {}
class Api { public ?Impl $bare = null; public ?\F::Impl $canon = null; }
class Ok {
    public ?Pub $p = null; public ?\G\External $ext = null; public int $n = 0;
    public static function hidden(): string { return get_class(new Hidden); }
}
class Hidden { public ?Impl $i = null; }
class Statics { public static ?Impl $s = null; }
class Ext { public ?\F::In::G $g = null; }
PHP);
file_put_contents("$dir/ext.php", "<?php\nnamespace G;\nclass External {}\n");
// warm: definition first; a bare name that misses the claims is undecidable until resolved
file_put_contents("$dir/wdef.php", "<?php\nmodule W { public Api; public Fine; }\n");
file_put_contents("$dir/wmem.php", "<?php\nmodule W;\nclass Impl {}\nclass Api { public ?Impl \$p = null; }\nclass Fine { public ?Other \$o = null; }\n");
file_put_contents("$dir/wother.php", "<?php\nnamespace W;\nclass Other {}\n");   // an ordinary class in W's namespace

spl_autoload_register(function ($c) use ($dir) {
    $m = ['f' => 'def', 'g\external' => 'ext', 'w\other' => 'wother'];
    if (isset($m[strtolower($c)])) { require "$dir/{$m[strtolower($c)]}.php"; }
});
require "$dir/mem.php";             // cold: compiled before F's definition
require "$dir/wdef.php";
require "$dir/wmem.php";            // warm

function t($n, $f) { try { $r = $f(); echo "$n: ", is_object($r) ? get_class($r) : var_export($r, true), "\n"; } catch (Throwable $e) { echo "$n: ", $e->getMessage(), "\n"; } }
t('cold internal type', fn() => new F::Api);
t('again (still checked)', fn() => new F::Api);
t('public types + ordinary class', fn() => new F::Ok);
t('internal class skipped', fn() => F::Ok::hidden());
t('static property', fn() => F::Statics::$s);
t('public member of internal nested module', fn() => new F::Ext);
class SubOk extends F::Ok {}
class SubApi extends F::Api {}
t('subclass of passing class', fn() => new SubOk);
t('subclass of failing class', fn() => new SubApi);
t('warm unclaimed bare name', fn() => new W::Api);
t('warm ordinary class in namespace', fn() => new W::Fine);
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m325_tmp';
foreach (['def', 'mem', 'ext', 'wdef', 'wmem', 'wother'] as $f) { @unlink("$dir/$f.php"); }
@rmdir($dir);
?>
--EXPECT--
cold internal type: the type of property F::Api::$bare references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
again (still checked): the type of property F::Api::$bare references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
public types + ordinary class: F::Ok
internal class skipped: 'F::Hidden'
static property: the type of property F::Statics::$s references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
public member of internal nested module: the type of property F::Ext::$g references "F::In::G", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
subclass of passing class: SubOk
subclass of failing class: the type of property F::Api::$bare references "F::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
warm unclaimed bare name: the type of property W::Api::$p references "W::Impl", which is not reachable from outside the module: a module's public surface may not expose internal or unclaimed types (declare a public supertype instead)
warm ordinary class in namespace: W::Fine