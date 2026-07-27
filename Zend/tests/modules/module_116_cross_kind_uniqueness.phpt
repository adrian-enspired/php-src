--TEST--
Module functions: member names are unique across kinds (class-like vs function), both directions
--FILE--
<?php
function lint(string $code): string {
    $f = tempnam(sys_get_temp_dir(), 'xk') . '.php';
    file_put_contents($f, "<?php\n" . $code);
    $out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -n -l ' . escapeshellarg($f) . ' 2>&1');
    @unlink($f);
    return $out;
}

// class first, then function with the same member name
echo str_contains(
    lint("module XK;\nclass Foo {}\nfunction foo() {}\n"),
    'Cannot declare module function XK::foo') ? "reject ok: function-after-class\n" : "FAIL 1\n";

// function first, then class
echo str_contains(
    lint("module XK2;\nfunction foo() {}\nclass Foo {}\n"),
    'Cannot declare module member XK2::Foo') ? "reject ok: class-after-function\n" : "FAIL 2\n";

// inline block, both kinds: caught by the kind-agnostic duplicate-handle rule
echo str_contains(
    lint("module XK3 { public class Foo {} public function foo() {} }\n"),
    'Duplicate module member handle') ? "reject ok: inline\n" : "FAIL 3\n";

// outside modules PHP's separate name spaces are untouched
echo str_contains(
    lint("class Foo {}\nfunction foo() {}\n"),
    'No syntax errors') ? "allow ok: global coexistence\n" : "FAIL 4\n";
?>
--EXPECT--
reject ok: function-after-class
reject ok: class-after-function
reject ok: inline
allow ok: global coexistence
