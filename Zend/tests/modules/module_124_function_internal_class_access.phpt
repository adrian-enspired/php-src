--TEST--
Module functions: a scope-less module function is module code — full access to internal class-side members
--FILE--
<?php
$dir = __DIR__ . '/mod124_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
module GateM {
    public make; public readConst; public callMethod; public viaClosure; public touchProp;
    internal Hidden;
}
PHP);

file_put_contents($dir . '/funcs.php', <<<'PHP'
<?php
module GateM;

class Hidden {
    public const T = 7;
    internal const S = 8;
    public int $p = 1;
    internal int $q = 2;
    public function m(): string { return "m"; }
    internal function im(): string { return "im"; }
}

function make(): object { return new module::Hidden(); }              // internal class: new
function readConst(): int { return module::Hidden::T + Hidden::S; }   // internal class const
function callMethod(): string { $h = new Hidden(); return $h->m() . $h->im(); } // internal method
function touchProp(): int { $h = new Hidden(); return $h->p + $h->q; }          // internal prop
function viaClosure(): callable {
    return fn(): string => (new Hidden())->im();   // module-born closure, internal method
}
PHP);

require $dir . '/manifest.php';
require $dir . '/funcs.php';

var_dump(GateM::make() instanceof GateM\Hidden);
var_dump(GateM::readConst());
var_dump(GateM::callMethod());
var_dump(GateM::touchProp());
$c = GateM::viaClosure();
var_dump($c());

// the same members stay gated for genuine outside code
try { new GateM\Hidden(); echo "LEAK\n"; } catch (\Error $e) { echo "gated: new\n"; }
$h = GateM::make();                       // escaped instance
try { $h->im(); echo "LEAK\n"; } catch (\Error $e) { echo "gated: internal method\n"; }
try { $x = $h->q; echo "LEAK\n"; } catch (\Error $e) { echo "gated: internal prop\n"; }
try { $x = GateM\Hidden::S; echo "LEAK\n"; } catch (\Error $e) { echo "gated: internal const\n"; }
var_dump($h->p, $h->m());                 // public surface of the escaped instance
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod124_tmp';
@unlink($dir . '/manifest.php');
@unlink($dir . '/funcs.php');
@rmdir($dir);
?>
--EXPECT--
bool(true)
int(15)
string(3) "mim"
int(3)
string(2) "im"
gated: new
gated: internal method
gated: internal prop
gated: internal const
int(1)
string(1) "m"
