--TEST--
Modules: eval() in module code runs as that module
--DESCRIPTION--
Like eval() in a class method runs in the class scope: code eval'd in a module function,
method or closure resolves module:: and reaches the module's internals (nested eval too;
closures created in it keep the module). It gets nothing more: a nested module's internals stay
denied, and declarations stay global (eval doesn't inherit the namespace). Outside the module,
eval is denied as before.
--FILE--
<?php
const CASES = [
    'new \M::Secret' => 'return get_class(new \M::Secret);',
    'new module::Secret' => 'return get_class(new module::Secret);',
    'module::Secret::K' => 'return module::Secret::K;',
    'module::hid()' => 'return module::hid();',
    'module::C' => 'return module::C;',
    'module::Secret::class' => 'return module::Secret::class;',
    'instanceof' => 'return (new module::Secret) instanceof module::Secret ? "yes" : "no";',
    'nested module internal' => 'return get_class(new module::In::Deep);',
    'internal method' => 'return (new module::Pub)->hidden();',
];
function report(string $where, array $r) { echo "== $where\n"; foreach ($r as $k => $v) echo "  $k: $v\n"; }
module M {
    internal class Secret { const K = 'k'; }
    internal function hid() { return 'hid'; }
    internal const C = 'c';
    public class Pub { internal function hidden() { return 'hidden'; } }
    public module In { internal class Deep {} }
    public function viaFunction() { $r = []; foreach (\CASES as $k => $c) { try { $r[$k] = eval($c); } catch (\Error $e) { $r[$k] = 'ERR ' . $e->getMessage(); } } return $r; }
    public class Cls { static function viaMethod() { $r = []; foreach (\CASES as $k => $c) { try { $r[$k] = eval($c); } catch (\Error $e) { $r[$k] = 'ERR ' . $e->getMessage(); } } return $r; } }
    public function viaClosure() { return (function () { $r = []; foreach (\CASES as $k => $c) { try { $r[$k] = eval($c); } catch (\Error $e) { $r[$k] = 'ERR ' . $e->getMessage(); } } return $r; })(); }
    public function nested() { return eval('return eval(\'return get_class(new module::Secret);\');'); }
    public function closureFromEval() { return eval('return fn() => get_class(new module::Secret);'); }
    public function declares() { eval('class Declared {} function declared_fn() {}'); return [class_exists('Declared', false) ? 'global Declared' : 'no global', class_exists('M::Declared', false) ? 'M::Declared' : 'not a member']; }
}
report('M::viaFunction', M::viaFunction());
report('M::Cls::viaMethod', M::Cls::viaMethod());
report('M::viaClosure', M::viaClosure());
echo "== nested eval: ", M::nested(), "\n";
$c = M::closureFromEval();
try { echo "== closure from eval, called outside: ", $c(), "\n"; } catch (Error $e) { echo "== closure from eval: ERR ", $e->getMessage(), "\n"; }
echo "== declarations in eval: ", implode(', ', M::declares()), "\n";
foreach (['return get_class(new \M::Secret);', 'return get_class(new module::Secret);', 'return \M::hid();'] as $code) {
    try { echo "outside: ", eval($code), " LEAK\n"; } catch (Error $e) { echo "outside: ", $e->getMessage(), "\n"; }
}
?>
--EXPECT--
== M::viaFunction
  new \M::Secret: M::Secret
  new module::Secret: M::Secret
  module::Secret::K: k
  module::hid(): hid
  module::C: c
  module::Secret::class: M::Secret
  instanceof: yes
  nested module internal: ERR Cannot access internal module member "M::In::Deep" from outside its module
  internal method: hidden
== M::Cls::viaMethod
  new \M::Secret: M::Secret
  new module::Secret: M::Secret
  module::Secret::K: k
  module::hid(): hid
  module::C: c
  module::Secret::class: M::Secret
  instanceof: yes
  nested module internal: ERR Cannot access internal module member "M::In::Deep" from outside its module
  internal method: hidden
== M::viaClosure
  new \M::Secret: M::Secret
  new module::Secret: M::Secret
  module::Secret::K: k
  module::hid(): hid
  module::C: c
  module::Secret::class: M::Secret
  instanceof: yes
  nested module internal: ERR Cannot access internal module member "M::In::Deep" from outside its module
  internal method: hidden
== nested eval: M::Secret
== closure from eval, called outside: M::Secret
== declarations in eval: global Declared, not a member
outside: outside: Cannot access internal module member "M::Secret" from outside its module
outside: outside: Class "module::Secret" not found
outside: outside: Cannot call internal module function M::hid() from outside its module
