--TEST--
Modules: a qualified call to a module function loads its module
--DESCRIPTION--
A module function is a namespaced function declared when its module's definition runs. A
qualified call (direct, dynamic string, callable, first-class callable) to one that isn't defined
yet loads the module and tries again; function_exists() doesn't load.
--FILE--
<?php
$dir = __DIR__ . '/m339_tmp';
@mkdir($dir);
foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $m) {
    file_put_contents("$dir/$m.php", "<?php\nmodule $m {\n    public function f() { return '$m::f'; }\n    internal function h() { return 'h'; }\n}\n");
}
spl_autoload_register(function ($c) use ($dir) {
    echo "[autoload $c]\n";
    if (is_file("$dir/$c.php")) require "$dir/$c.php";
});

var_dump(function_exists('A\f'));          // no autoload
echo A\f(), "\n";                           // direct call
$g = 'B\f'; echo $g(), "\n";                // dynamic string
echo call_user_func('C\f'), "\n";           // callable
var_dump(is_callable('D\f'));               // callable check
echo (E\f(...))(), "\n";                    // first-class callable
try { F\h(); } catch (Error $e) { echo $e->getMessage(), "\n"; }   // loads, still gated
try { Nope\f(); } catch (Error $e) { echo $e->getMessage(), "\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m339_tmp';
foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $m) { @unlink("$dir/$m.php"); }
@rmdir($dir);
?>
--EXPECT--
bool(false)
[autoload A]
A::f
[autoload B]
B::f
[autoload C]
C::f
[autoload D]
bool(true)
[autoload E]
E::f
[autoload F]
Cannot call internal module function F\h() from outside its module
[autoload Nope]
Call to undefined function Nope\f()
