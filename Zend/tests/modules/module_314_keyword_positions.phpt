--TEST--
Modules: module/internal are contextual -- a class/function/constant named Module or Internal works in every position
--DESCRIPTION--
Each line is a position a name can occupy; each is compiled and run with the name spelled
Module, module, Internal and internal. All must behave as in PHP without modules. Directly before a
variable `internal` is the type Internal (module visibility requires a type), so `static Internal $x`
etc. are typed properties. The only error is `internal` used as a modifier outside a module.
--FILE--
<?php
$corpus = <<<'CORPUS'
class-decl|class NAME {}
class-decl-extends|class NAME extends \stdClass {}
class-decl-implements|class NAME implements \Countable { function count(): int { return 0; } }
interface-decl|interface NAME {}
interface-extends|interface NAME extends \Countable {}
trait-decl|trait NAME {}
enum-decl|enum NAME {}
enum-backed|enum NAME: string {}
enum-implements|enum NAME implements \JsonSerializable { function jsonSerialize(): mixed { return 1; } }
function-decl|function NAME() {}
function-decl-space|function NAME () {}
function-decl-ref|function &NAME() { static $a; return $a; }
const-decl|const NAME = 1;
const-decl-space|const NAME= 1;
class-const|class C { const NAME = 1; }
class-const-typed|class C { const int NAME = 1; }
method-decl|class C { function NAME() {} }
method-decl-space|class C { function NAME () {} }
static-method-decl|class C { static function NAME() {} }
property|class C { public $NAME; }
enum-case|enum E { case NAME; }
enum-case-value|enum E: int { case NAME = 1; }
label|goto NAME; NAME: echo 1;
new|class NAME {} new NAME;
new-parens|class NAME {} new NAME();
new-space-parens|class NAME {} new NAME ();
new-args-space|class NAME { function __construct($a) {} } new NAME (1);
static-call|class NAME { static function f() {} } NAME::f();
static-call-space|class NAME { static function f() {} } NAME :: f();
class-const-fetch|class NAME { const K = 1; } echo NAME::K;
class-name|class NAME {} echo NAME::class;
static-prop|class NAME { static $p = 1; } echo NAME::$p;
instanceof|class NAME {} var_dump(new NAME instanceof NAME);
instanceof-and|class NAME {} var_dump(new NAME instanceof NAME and true);
instanceof-or|class NAME {} var_dump(new NAME instanceof NAME or true);
instanceof-xor|class NAME {} var_dump(new NAME instanceof NAME xor true);
instanceof-ternary|class NAME {} var_dump(new NAME instanceof NAME ? 1 : 2);
instanceof-paren|class NAME {} var_dump((new NAME) instanceof NAME);
catch|class NAME extends \Exception {} try {} catch (NAME $e) {}
catch-noVar|class NAME extends \Exception {} try {} catch (NAME) {}
catch-union-first|class NAME extends \Exception {} try {} catch (NAME|\Error $e) {}
catch-union-last|class NAME extends \Exception {} try {} catch (\Error|NAME $e) {}
param|class NAME {} function f(NAME $x) {}
param-nullable|class NAME {} function f(?NAME $x) {}
param-union-first|class NAME {} function f(NAME|int $x) {}
param-union-last|class NAME {} function f(int|NAME $x) {}
param-intersection-last|interface NAME {} function f(\Countable&NAME $x) {}
param-dnf|interface NAME {} function f((\Countable&NAME)|null $x) {}
param-variadic|class NAME {} function f(NAME ...$x) {}
param-byref|class NAME {} function f(NAME &$x) {}
param-default|class NAME {} function f(NAME $x = null) {}
param-promoted|class NAME {} class C { function __construct(public NAME $x) {} }
param-promoted-ro|class NAME {} class C { function __construct(public readonly NAME $x) {} }
param-closure|class NAME {} $f = function (NAME $x) {};
param-arrow|class NAME {} $f = fn(NAME $x) => 1;
return|class NAME {} function f(): NAME {}
return-nullable|class NAME {} function f(): ?NAME {}
return-union|class NAME {} function f(): int|NAME {}
return-abstract|class NAME {} interface I { function f(): NAME; }
return-arrow|class NAME {} $f = fn(): NAME => new NAME;
prop-typed|class NAME {} class C { public NAME $x; }
prop-typed-nullable|class NAME {} class C { public ?NAME $x; }
prop-typed-union-last|class NAME {} class C { public int|NAME $x; }
prop-typed-union-first|class NAME {} class C { public NAME|int $x; }
prop-typed-readonly|class NAME {} class C { public readonly NAME $x; }
prop-typed-static|class NAME {} class C { public static NAME $x; }
prop-typed-var|class NAME {} class C { var NAME $x; }
prop-typed-hook|class NAME {} class C { public NAME $x { get => new NAME; } }
const-typed|enum NAME { case A; } class C { const NAME K = NAME::A; }
extends|class NAME {} class C extends NAME {}
implements|interface NAME {} class C implements NAME {}
implements-list|interface NAME {} class C implements \Countable, NAME { function count(): int { return 0; } }
iface-extends|interface NAME {} interface I extends NAME {}
use-trait|trait NAME {} class C { use NAME; }
use-trait-block|trait NAME { function f() {} } class C { use NAME { f as g; } }
trait-insteadof|trait NAME { function f() {} } trait T2 { function f() {} } class C { use NAME, T2 { NAME::f insteadof T2; } }
use-import|namespace X { class NAME {} } namespace { use X\NAME; }
use-import-as|namespace X { class NAME {} } namespace { use X\NAME as Y; }
use-import-alias-to|namespace X { class C {} } namespace { use X\C as NAME; }
use-global|class NAME {} use NAME;
use-function|namespace X { function NAME() {} } namespace { use function X\NAME; }
use-const|namespace X { const NAME = 1; } namespace { use const X\NAME; }
use-group|namespace X { class NAME {} class B {} } namespace { use X\{NAME, B}; }
use-group-as|namespace X { class NAME {} } namespace { use X\{NAME as Z}; }
namespace-seg|namespace App\NAME; class C {}
namespace-end|namespace NAME; class C {}
namespace-block|namespace NAME { class C {} }
qualified|namespace X { class NAME {} } namespace { new X\NAME; }
fq|class NAME {} new \NAME;
call|function NAME() {} NAME();
call-space|function NAME() {} NAME ();
call-ns|namespace X { function NAME() { return 1; } } namespace { echo X\NAME(); }
const-fetch|const NAME = 1; echo NAME;
const-fetch-semicolon|const NAME = 1; $x = NAME;
const-and|const NAME = 1; var_dump(NAME and true);
const-coalesce|const NAME = 1; var_dump(NAME ?? 2);
const-ternary|const NAME = 1; var_dump(NAME ? 1 : 2);
const-nullsafe-short|const NAME = 1; var_dump(NAME ?: 2);
const-concat|const NAME = 1; echo NAME . "x";
const-array|const NAME = 1; var_dump([NAME => 1]);
const-match|const NAME = 1; echo match(1) { NAME => 'a', default => 'b' };
const-case|const NAME = 1; switch (1) { case NAME: echo 1; }
const-default-param|const NAME = 1; function f($a = NAME) {}
attribute|#[Attribute] class NAME {} #[NAME] function f() {}
attribute-args|#[Attribute] class NAME { function __construct($a) {} } #[NAME(1)] function f() {}
named-arg|function f($NAME) {} f(NAME: 1);
method-call|class C { function NAME() {} } (new C)->NAME();
method-call-space|class C { function NAME() {} } (new C)->NAME ();
nullsafe-method|class C { function NAME() {} } (new C)?->NAME();
static-method-call|class C { static function NAME() {} } C::NAME();
static-method-call-space|class C { static function NAME() {} } C::NAME ();
prop-fetch|class C { public $NAME = 1; } echo (new C)->NAME;
class-const-by-name|class C { const NAME = 1; } echo C::NAME;
first-class-callable|function NAME() {} $f = NAME(...);
callable-string|function NAME() {} call_user_func('NAME');
static-closure|class NAME {} $f = static fn(NAME $x): NAME => $x;
new-anon-extends|class NAME {} $o = new class extends NAME {};
new-anon-implements|interface NAME {} $o = new class implements NAME {};
docblock|/** @var NAME */ $x = 1;
cast-like|class NAME {} $x = (NAME::class);
array-key-class|class NAME {} $a = [NAME::class => 1];
list-key|const NAME = 'a'; ['a' => $v] = [NAME => 1];
echo-comma|const NAME = 1; echo NAME, NAME;
print|const NAME = 1; print NAME;
return-const|const NAME = 1; function f() { return NAME; }
yield-const|const NAME = 1; function f() { yield NAME; }
throw-new|class NAME extends Exception {} try { throw new NAME; } catch (Exception) {}
clone-new|class NAME {} $x = clone new NAME;
include-like|const NAME = __FILE__; if (0) include NAME;
instanceof-insteadof|class NAME {} var_dump(1 instanceof NAME);
comment-between|class NAME /* c */ extends \stdClass {}
comment-param|class NAME {} function f(NAME /* c */ $x) {}
prop-typed-protected|class NAME {} class C { protected NAME $x; }
prop-typed-private-static|class NAME {} class C { private static NAME $x; }
prop-typed-static-only|class NAME {} class C { static NAME $x; }
prop-typed-readonly-only|class NAME {} class C { readonly NAME $x; function __construct() { $this->x = new NAME; } }
prop-typed-final|class NAME {} class C { final public NAME $x; }
prop-typed-default|class NAME {} class C { public ?NAME $x = null; }
prop-typed-multi|class NAME {} class C { public NAME $x, $y; }
prop-typed-intersection|interface NAME {} class C { public \Countable&NAME $x; }
prop-typed-dnf|interface NAME {} class C { public (\Countable&NAME)|null $x; }
class-const-dynamic|class NAME { const K = 1; } $k = 'K'; echo NAME::{$k};
const-typed-nullable|enum NAME { case A; } class C { const ?NAME K = NAME::A; }
const-typed-union-last|enum NAME { case A; } class C { const int|NAME K = NAME::A; }
const-typed-intersection-last|interface NAME {} enum E implements \Countable, NAME { case A; function count(): int { return 0; } } class C { const \Countable&NAME K = E::A; }
prop-typed-final-only|class NAME {} class C { final NAME $x; }
param-promoted-ro-only|class NAME {} class C { function __construct(readonly NAME $x) {} }
param-promoted-private|class NAME {} class C { function __construct(private ?NAME $x = null) {} }
param-promoted-set|class NAME {} class C { function __construct(public private(set) NAME $x) {} }
param-promoted-final|class NAME {} class C { function __construct(final public NAME $x) {} }
param-promoted-union|class NAME {} class C { function __construct(public int|NAME $x) {} }
param-ctor-plain|class NAME {} class C { function __construct(NAME $x) {} } new C(new NAME);
param-ctor-plain-default|class NAME {} class C { function __construct(NAME $x = new NAME) {} } new C;
param-ctor-variadic|class NAME {} class C { function __construct(NAME ...$x) {} } new C(new NAME);
param-ctor-byref|class NAME {} class C { function __construct(NAME &$x) {} }
closure-param|class NAME {} $f = function (NAME $x) {}; $g = fn(NAME $x) => $x;
module-member-type|function f(\M\NAME $x): \M\NAME { return $x; }
module-member-new|module M { public class NAME {} } $x = new \M\NAME (); $y = new \M\NAME;
module-member-instanceof|module M { public class NAME {} } var_dump(new \M\NAME instanceof \M\NAME);
module-member-const|module M { public class NAME { const K = 1; } } echo \M\NAME::K;
module-member-static|module M { public class NAME { static function f() {} } } \M\NAME::f();
module-member-catch|module M { public class NAME extends \Exception {} } try {} catch (\M\NAME $e) {}
module-member-promoted|module M { public class NAME {} } class C { function __construct(public \M\NAME $x) {} }
param-promoted-final-only|class NAME {} class C { function __construct(final NAME $x) {} }
param-promoted-internal|class NAME {} class C { function __construct(internal NAME $x) {} }
CORPUS;
$php = getenv('TEST_PHP_EXECUTABLE') ?: PHP_BINARY;
$n = 0;
foreach (explode("\n", $corpus) as $line) {
    if (!str_contains($line, '|')) { continue; }
    [$pos, $code] = explode('|', $line, 2);
    foreach (['Module', 'module', 'Internal', 'internal'] as $name) {
        $f = __DIR__ . '/module_314_case.inc';
        file_put_contents($f, "<?php\n" . str_replace('NAME', $name, $code) . "\n");
        $r = shell_exec(escapeshellarg($php) . ' -n ' . escapeshellarg($f) . ' 2>&1') ?? '';
        $n++;
        if (preg_match('/(Parse error|Fatal error): (.*?) in \//', $r, $m)) {
            printf("%s %s: %s\n", $pos, $name, $m[2]);
        }
    }
}
@unlink(__DIR__ . '/module_314_case.inc');
echo "checked ", $n > 600 ? "all" : $n, "\n";
?>
--EXPECT--
param-promoted-internal Module: internal may only be used on a member of a module class
param-promoted-internal module: internal may only be used on a member of a module class
param-promoted-internal Internal: internal may only be used on a member of a module class
param-promoted-internal internal: internal may only be used on a member of a module class
checked all
