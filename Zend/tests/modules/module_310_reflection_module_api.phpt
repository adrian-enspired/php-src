--TEST--
Modules: ReflectionModule API (objects/names per kind, traits, short-name visibility, parent, Reflector) + ReflectionClass/ReflectionFunction module methods
--DESCRIPTION--
get<Kind>s() return reflection objects keyed by canonical name and get<Kind>Names() the names,
like ReflectionClass::getInterfaces()/getInterfaceNames(). getSymbolVisibility()/hasMember()
take a member name relative to the module or its canonical name, for every member kind
(class-likes, nested modules, functions, inline and member-file constants).
--FILE--
<?php
namespace V;
#[\Attribute(\Attribute::TARGET_ALL)] class Tag { function __construct(public string $v = '') {} }
#[Tag('mod')]
module M {
    internal class I {}
    public class P { internal function hid() {} internal const K = 1; internal mixed $p; }
    trait T {}
    interface X {}
    enum E { case A; }
    internal function f() {}
    function g() {}
    const C = 1;
    internal const S = 2;
    internal module N { class Deep {} }
}
namespace G;
$m = new \ReflectionModule('\V\M');
echo $m;
var_dump($m->getName(), $m->getShortName(), $m->getNamespaceName(), $m->inNamespace(), $m->getParentModule(), $m->getStartLine(), $m->getEndLine());
var_dump(array_map(fn($o) => get_class($o), $m->getClasses()), $m->getClassNames(), $m->getInterfaceNames(), $m->getTraitNames(), $m->getEnumNames(), $m->getModuleNames(), $m->getFunctionNames());
var_dump(get_class($m->getEnums()['V\M\E']), get_class($m->getModules()['V\M\N']), get_class($m->getFunctions()['V\M\f']));
var_dump($m->getConstants());
foreach (['I', 'V\M\I', 'P', 'T', 'f', 'g', 'C', 'S', 'N', 'V\M::S', 'Nope', 'N\Deep'] as $n) { try { echo "$n: ", $m->getSymbolVisibility($n), " ", var_export($m->hasMember($n), true), "\n"; } catch (\Throwable $e) { echo $e->getMessage(), "\n"; } }
$n = $m->getModules()['V\M\N'];
var_dump($n->getName(), $n->getShortName(), $n->getNamespaceName(), $n->getParentModule()->getName(), $n->getClassNames());
var_dump((new \ReflectionClass('V\M\I'))->isModuleInternal(), (new \ReflectionClass('V\M\P'))->isModuleInternal(), (new \ReflectionClass('V\M\N\Deep'))->getModule()->name, (new \ReflectionClass('V\M\P'))->getModule()->name, (new \ReflectionClass('V\Tag'))->getModule());
var_dump((new \ReflectionClass('V\M\N'))->isModuleInternal(), (new \ReflectionClass('V\M'))->isModuleInternal());
var_dump((new \ReflectionFunction('V\M\f'))->isModuleInternal(), (new \ReflectionFunction('V\M\g'))->isModuleInternal(), (new \ReflectionFunction('V\M\f'))->getModule()->name, (new \ReflectionFunction('strlen'))->getModule());
var_dump(array_map(fn($a) => [$a->getName(), $a->getArguments()], $m->getAttributes()));
var_dump($m instanceof \Reflector);
try { $m->name = 'x'; } catch (\ReflectionException $e) { echo $e->getMessage(), "\n"; }
try { clone $m; } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { new \ReflectionModule('V\M\I'); } catch (\ReflectionException $e) { echo $e->getMessage(), "\n"; }
--EXPECTF--
Module [ <user> V\M ] {
  @@ %s 5-15

  - Members {
    internal class V\M\I
    public class V\M\P
    public trait V\M\T
    public interface V\M\X
    public enum V\M\E
    internal module V\M\N
    internal function V\M\f
    public function V\M\g
    public const V\M::C
    internal const V\M::S
  }
}
string(3) "V\M"
string(1) "M"
string(1) "V"
bool(true)
NULL
int(5)
int(15)
array(2) {
  ["V\M\I"]=>
  string(15) "ReflectionClass"
  ["V\M\P"]=>
  string(15) "ReflectionClass"
}
array(2) {
  [0]=>
  string(5) "V\M\I"
  [1]=>
  string(5) "V\M\P"
}
array(1) {
  [0]=>
  string(5) "V\M\X"
}
array(1) {
  [0]=>
  string(5) "V\M\T"
}
array(1) {
  [0]=>
  string(5) "V\M\E"
}
array(1) {
  [0]=>
  string(5) "V\M\N"
}
array(2) {
  [0]=>
  string(5) "V\M\f"
  [1]=>
  string(5) "V\M\g"
}
string(14) "ReflectionEnum"
string(16) "ReflectionModule"
string(18) "ReflectionFunction"
array(2) {
  ["C"]=>
  int(1)
  ["S"]=>
  int(2)
}
I: internal true
V\M\I: internal true
P: public true
T: public true
f: internal true
g: public true
C: public true
S: internal true
N: internal true
V\M::S: internal true
Nope: Symbol "Nope" is not a member of module "V\M"
N\Deep: Symbol "N\Deep" is not a member of module "V\M"
string(5) "V\M\N"
string(1) "N"
string(1) "V"
string(3) "V\M"
array(1) {
  [0]=>
  string(10) "V\M\N\Deep"
}
bool(true)
bool(false)
string(5) "V\M\N"
string(3) "V\M"
NULL
bool(true)
bool(false)
bool(true)
bool(false)
string(3) "V\M"
NULL
array(1) {
  [0]=>
  array(2) {
    [0]=>
    string(5) "V\Tag"
    [1]=>
    array(1) {
      [0]=>
      string(3) "mod"
    }
  }
}
bool(true)
Cannot set read-only property ReflectionModule::$name
Trying to clone an uncloneable object of class ReflectionModule
Module "V\M\I" does not exist
