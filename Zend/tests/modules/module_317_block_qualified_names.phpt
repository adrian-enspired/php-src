--TEST--
Modules: a backslash-qualified name inside a module block is relative to the module
--DESCRIPTION--
Inside module N\M the namespace is the module, for qualified names as for bare ones:
"Sub\X" is "N\M::Sub\X" (a member declared in a member file under `namespace Sub;`).
--FILE--
<?php
$dir = __DIR__ . '/m317_tmp';
@mkdir($dir);
file_put_contents("$dir/x.php", "<?php\nmodule N\\M;\nnamespace Sub;\nclass X {}\n");
file_put_contents("$dir/main.php", <<<'PHP'
<?php
namespace N;
module M {
    public Sub\X;
    public class T {
        public static function run(): array {
            return [
                (new Sub\X)::class,
                (new module::Sub\X)::class,
                (new \N\M::Sub\X)::class,
                (new \N\M\Sub\X)::class,
                Sub\X::class,
            ];
        }
    }
}
require __DIR__ . '/x.php';
var_dump(M::T::run());
PHP);
require "$dir/main.php";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m317_tmp';
@unlink("$dir/x.php");
@unlink("$dir/main.php");
@rmdir($dir);
?>
--EXPECT--
array(5) {
  [0]=>
  string(10) "N\M::Sub\X"
  [1]=>
  string(10) "N\M::Sub\X"
  [2]=>
  string(10) "N\M::Sub\X"
  [3]=>
  string(10) "N\M::Sub\X"
  [4]=>
  string(10) "N\M::Sub\X"
}
