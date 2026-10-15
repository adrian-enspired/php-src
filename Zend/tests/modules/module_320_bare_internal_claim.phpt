--TEST--
Modules: "internal;" in a module block is a visibility with no member, not a claim
--DESCRIPTION--
`internal` is a contextual keyword, so before ";" it lexes as a name. At the head of a module
member it is the visibility: like `public;`, `internal;` is an error rather than a claim of a
member named "internal" (which could never be a class: Internal is a reserved class name).
--FILE--
<?php
$php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
$f = __DIR__ . '/module_320_case.inc';
foreach (['module M { internal; }', 'module M { Internal; }', 'module M { public internal; }',
          'module M { public; }', 'module M { internal Widget; } echo "claim ok\n";'] as $code) {
    file_put_contents($f, "<?php\n$code\n");
    $r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
    echo preg_match('/(?:Fatal|Parse) error: (.*?) in \//', $r, $m) ? $m[1] : trim($r), "\n";
}
@unlink($f);
?>
--EXPECT--
Cannot claim a member named "internal" in module M (a member is claimed as "internal Name;")
Cannot claim a member named "internal" in module M (a member is claimed as "internal Name;")
Cannot claim a member named "internal" in module M (a member is claimed as "internal Name;")
syntax error, unexpected token ";"
claim ok
