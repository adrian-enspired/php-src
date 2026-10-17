--TEST--
Modules: inline module constants live on the module and become constants on first lookup
--DESCRIPTION--
An inline module constant is compiled with its module (no per-request declaration) and becomes
the namespaced constant "M\K" when first looked up. A qualified fetch of an unloaded module's
constant loads the module; defined() doesn't. Inside the module, a bare name finds the module
constant before a global one. It is not a class constant of the module.
--FILE--
<?php
$dir = __DIR__ . '/m338_tmp';
@mkdir($dir);
file_put_contents("$dir/Shop.php", <<<'PHP'
<?php
namespace { const RATE = 'global'; }
namespace {
module Shop {
    public const VERSION = '3.0';
    public const RATE = 'shop';
    public const DERIVED = module::VERSION . '+';
    internal const SECRET = 's';
    #[\Attribute] public class Tag {}
    #[Tag] public const TAGGED = 1;
    public function rate() { return RATE; }          // bare: the module's, not the global one
    public function secret() { return SECRET; }
}
}
PHP);
spl_autoload_register(function ($c) use ($dir) {
    echo "[autoload $c]\n";
    if (is_file("$dir/$c.php")) require "$dir/$c.php";
});

var_dump(defined('Shop\VERSION'));               // no autoload
echo Shop\VERSION, "\n";                          // autoloads Shop
echo Shop\DERIVED, ' ', Shop\rate(), ' ', Shop\secret(), ' ', RATE, "\n";
var_dump(defined('Shop\VERSION'), constant('Shop\RATE'));
try { echo Shop\SECRET; } catch (Error $e) { echo $e->getMessage(), "\n"; }
try { echo Shop::VERSION; } catch (Error $e) { echo $e->getMessage(), "\n"; }
echo get_class((new ReflectionConstant('Shop\TAGGED'))->getAttributes()[0]->newInstance()), "\n";
echo implode(',', array_keys((new ReflectionModule('Shop'))->getConstants())), "\n";
try { echo Nope\K; } catch (Error $e) { echo $e->getMessage(), "\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m338_tmp';
@unlink("$dir/Shop.php");
@rmdir($dir);
?>
--EXPECT--
bool(false)
[autoload Shop]
3.0
3.0+ shop s global
bool(true)
string(4) "shop"
Cannot access internal module constant Shop\SECRET from outside its module
Undefined constant Shop::VERSION
Shop\Tag
VERSION,RATE,DERIVED,SECRET,TAGGED
[autoload Nope]
Undefined constant "Nope\K"
