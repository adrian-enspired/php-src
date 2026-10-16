--TEST--
Modules: "use module::X" in a member file imports a member of the file's own module
--DESCRIPTION--
The import is pure aliasing: it does not require the module definition to be loaded yet (the
member file may be autoloaded before it), and both the default and `as` alias forms work.
--FILE--
<?php
// "use module::X" in a member file imports a member of the file's own module.
// Importing is aliasing only: the member file may compile before the module definition.
spl_autoload_register(function (string $c) {
    $files = [
        'M' => '<?php module M { public Widget; internal Helper; }',
        'M\Helper' => '<?php module M; class Helper { static function doThings() { return "done"; } }',
        'M\Widget' => '<?php module M; use module::Helper; use module::Helper as H;
            class Widget {
                internal function f() { return Helper::doThings() . "/" . H::doThings(); }
                function g() { return $this->f(); }
            }',
    ];
    if (isset($files[$c])) {
        $f = tempnam(sys_get_temp_dir(), 'mod');
        file_put_contents($f, $files[$c]);
        require $f;
        unlink($f);
    }
});
echo (new M\Widget)->g(), "\n";

?>
--EXPECT--
done/done
