--TEST--
Modules: module:: in a member file compiled before its module definition (cold) defers the membership check
--DESCRIPTION--
PSR-4 loads a member file by its ordinary name before the module definition. A module::
reference in it must not fail at compile time; a genuine non-member still errors when used.
--FILE--
<?php
// A member file loaded by its ordinary name BEFORE its module definition (the PSR-4 path)
// may use module:: references; membership is then checked when the reference executes.
spl_autoload_register(function (string $c) {
    $files = [
        'M' => '<?php module M { public Widget; internal Helper; }',
        'M\Helper' => '<?php module M; class Helper { static function x() { return "x"; } }',
        'M\Widget' => '<?php module M;
            class Widget {
                function g() { return module::Helper::x() . (new module::Helper)::x(); }
                function bad() { return new module::Nope; }
            }',
    ];
    echo "[autoload $c]\n";
    if (isset($files[$c])) {
        $f = tempnam(sys_get_temp_dir(), 'mod');
        file_put_contents($f, $files[$c]);
        require $f;
        unlink($f);
    }
});
$w = new M\Widget;
echo $w->g(), "\n";
try { $w->bad(); } catch (Error $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
[autoload M\Widget]
[autoload M]
[autoload M\Helper]
xx
[autoload M\Nope]
"Nope" is not a member of module "M"
