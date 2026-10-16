--TEST--
Modules: preloaded nested modules and cold-compiled members keep their visibility
--DESCRIPTION--
Preloaded modules never re-run their definition, so the per-request module registry is empty
for them. Visibility normally resolved from it at runtime -- a nested module's (decided by its
container) and a member's compiled before its definition -- is resolved at the end of preloading.
Covers both preload orders (definition first; member file first).
--SKIPIF--
<?php
if (!extension_loaded('Zend OPcache')) die('skip requires opcache');
if (substr(PHP_OS, 0, 3) == 'WIN') die('skip not for Windows');
?>
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE');
$dir = __DIR__ . '/m330_tmp';
@mkdir($dir);
file_put_contents("$dir/def.php", <<<'PHP'
<?php
module O {
    public Api; internal Secret; public pf; internal qf;
    public module In { public class G { function t() { return "g"; } } internal class Hid {} }
    internal module Hide { public class H {} }
    public class Inside { static function reach() { return get_class(new \O\Hide\H); } }
}
PHP);
file_put_contents("$dir/mem.php", <<<'PHP'
<?php
module O;
class Api { function t() { return "api"; } }
class Secret {}
function pf() { return "pf"; }
function qf() { return "qf"; }
PHP);
file_put_contents("$dir/run.php", <<<'PHP'
<?php
foreach ([
    'nested public'       => fn() => (new O\In\G)->t(),
    'nested internal'     => fn() => new O\Hide\H,
    'internal in public nested' => fn() => new O\In\Hid,
    'from inside'         => fn() => O\Inside::reach(),
    'cold public class'   => fn() => (new O\Api)->t(),
    'cold internal class' => fn() => new O\Secret,
    'cold public fn'      => fn() => O\pf(),
    'cold internal fn'    => fn() => O\qf(),
] as $k => $f) {
    try { $r = $f(); echo "$k: ", is_object($r) ? get_class($r) : $r, "\n"; } catch (Error $e) { echo "$k: ", $e->getMessage(), "\n"; }
}
PHP);
foreach (['def first' => "def.php';\nrequire __DIR__ . '/mem.php", 'member file first' => "mem.php';\nrequire __DIR__ . '/def.php"] as $order => $req) {
    file_put_contents("$dir/preload.php", "<?php\nrequire __DIR__ . '/$req';\n");
    foreach (['', ' -d opcache.jit=tracing -d opcache.jit_buffer_size=64M'] as $jit) {
        $out = shell_exec("$php -n -d opcache.enable_cli=1 -d opcache.preload=" . escapeshellarg("$dir/preload.php")
            . "$jit " . escapeshellarg("$dir/run.php") . ' 2>&1');
        echo "== $order", $jit ? " (tracing JIT)" : "", "\n", $out;
    }
}
?>
--CLEAN--
<?php
$dir = __DIR__ . '/m330_tmp';
foreach (['def', 'mem', 'run', 'preload'] as $f) { @unlink("$dir/$f.php"); }
@rmdir($dir);
?>
--EXPECT--
== def first
nested public: g
nested internal: Cannot access "O\Hide\H": module "O\Hide" is internal to its parent module
internal in public nested: Cannot access internal module member "O\In\Hid" from outside its module
from inside: O\Hide\H
cold public class: api
cold internal class: Cannot access internal module member "O\Secret" from outside its module
cold public fn: pf
cold internal fn: Cannot call internal module function O\qf() from outside its module
== def first (tracing JIT)
nested public: g
nested internal: Cannot access "O\Hide\H": module "O\Hide" is internal to its parent module
internal in public nested: Cannot access internal module member "O\In\Hid" from outside its module
from inside: O\Hide\H
cold public class: api
cold internal class: Cannot access internal module member "O\Secret" from outside its module
cold public fn: pf
cold internal fn: Cannot call internal module function O\qf() from outside its module
== member file first
nested public: g
nested internal: Cannot access "O\Hide\H": module "O\Hide" is internal to its parent module
internal in public nested: Cannot access internal module member "O\In\Hid" from outside its module
from inside: O\Hide\H
cold public class: api
cold internal class: Cannot access internal module member "O\Secret" from outside its module
cold public fn: pf
cold internal fn: Cannot call internal module function O\qf() from outside its module
== member file first (tracing JIT)
nested public: g
nested internal: Cannot access "O\Hide\H": module "O\Hide" is internal to its parent module
internal in public nested: Cannot access internal module member "O\In\Hid" from outside its module
from inside: O\Hide\H
cold public class: api
cold internal class: Cannot access internal module member "O\Secret" from outside its module
cold public fn: pf
cold internal fn: Cannot call internal module function O\qf() from outside its module