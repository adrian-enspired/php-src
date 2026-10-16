--TEST--
Modules: "internal;" in a module block is a visibility with no member, not a claim
--DESCRIPTION--
`internal` is a contextual keyword, so before ";" it lexes as a name. At the head of a module
member it is the visibility: like `public;`, `internal;` is an error. A member named Internal is
claimed with an explicit visibility: `public Internal;` / `internal Internal;`.
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
$f = __DIR__ . '/module_320_case.inc';
foreach (['module M { internal; }', 'module M { Internal; }', 'module M { public; }',
          'module M { internal Widget; } echo "claim ok\n";',
          'module M { public Internal; internal Internal as Hidden; } echo "Internal claimed\n";',
          'module M { public class Internal {} } echo get_class(new M\Internal), "\n";'] as $code) {
    file_put_contents($f, "<?php\n$code\n");
    $r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
    echo preg_match('/(?:Fatal|Parse) error: (.*?) in \//', $r, $m) ? $m[1] : trim($r), "\n";
}
@unlink($f);
?>
--EXPECT--
Missing member name after "internal" in module M (to claim a member named Internal, write "public Internal;" or "internal Internal;")
Missing member name after "internal" in module M (to claim a member named Internal, write "public Internal;" or "internal Internal;")
syntax error, unexpected token ";"
claim ok
Internal claimed
M\Internal
