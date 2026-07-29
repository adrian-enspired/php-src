--TEST--
Modules: nested-module projection holds at depth, and across `as` aliases and sub-namespaces
--DESCRIPTION--
Companion to module_132. The projection form is derived by rewriting every "::" boundary, so
it must hold at arbitrary nesting depth and compose with the other member-naming features:
a sub-namespace inside a nested module, an `as` alias on a nested module's claim, and the
ancestor rule (a nested module reaching its enclosing module's internals).
--FILE--
<?php
$dir = __DIR__ . '/mod133_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
namespace A;
module B {
    internal Secret;
    public module C {
        public module D {
            public Plain;
            public Sub\Deep;
        }
        public Ui\Button;
        public Legacy\Button as Old;
        public Probe;
    }
}
PHP);

file_put_contents($dir . '/secret.php', <<<'PHP'
<?php
module A\B;
class Secret { public static function tag() : string { return "b-secret"; } }
PHP);

file_put_contents($dir . '/deep3.php', <<<'PHP'
<?php
module A\B::C::D;
class Plain { public function me() : Plain { return $this; } }
PHP);

file_put_contents($dir . '/deep3sub.php', <<<'PHP'
<?php
module A\B::C::D;
namespace Sub;
class Deep { public function me() : Deep { return $this; } }
PHP);

file_put_contents($dir . '/ui.php', <<<'PHP'
<?php
module A\B::C;
namespace Ui;
class Button {}
PHP);

file_put_contents($dir . '/legacy.php', <<<'PHP'
<?php
module A\B::C;
namespace Legacy;
class Button {}
PHP);

file_put_contents($dir . '/probe.php', <<<'PHP'
<?php
module A\B::C;
class Probe {
    // the ancestor rule: C is nested beneath B, so B's internals are in scope
    public static function ancestorInternal() : string { return \A\B::Secret::tag(); }
}
PHP);

foreach (['manifest','secret','deep3','deep3sub','ui','legacy','probe'] as $f) {
    require "$dir/$f.php";
}

echo "3 levels, canonical  : ", get_class((new A\B::C::D::Plain())->me()), "\n";
echo "3 levels + sub-ns    : ", get_class((new A\B::C::D::Deep())->me()), "\n";
echo "3 levels, namespaced : ", class_exists('A\B\C\D\Plain', false) ? 'resolves' : 'MISSING', "\n";
echo "sub-ns,   namespaced : ", class_exists('A\B\C\D\Sub\Deep', false) ? 'resolves' : 'MISSING', "\n";
echo "no hybrid at depth   : ", class_exists('A\B::C::D\Plain', false) ? 'LEAKED' : 'absent', "\n";
echo "`as` alias canonical : ", get_class(new A\B::C::Old()), "\n";
echo "plain member         : ", get_class(new A\B::C::Button()), "\n";
echo "both namespaced      : ",
    (class_exists('A\B\C\Ui\Button', false) && class_exists('A\B\C\Legacy\Button', false))
        ? 'resolve' : 'MISSING', "\n";
echo "ancestor internal    : ", A\B::C::Probe::ancestorInternal(), "\n";
echo "outside is denied    : ";
try { A\B::Secret::tag(); echo "LEAKED\n"; } catch (\Error $e) { echo "denied\n"; }
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod133_tmp';
foreach (glob($dir . '/*.php') as $f) { @unlink($f); }
@rmdir($dir);
?>
--EXPECT--
3 levels, canonical  : A\B::C::D::Plain
3 levels + sub-ns    : A\B::C::D::Sub\Deep
3 levels, namespaced : resolves
sub-ns,   namespaced : resolves
no hybrid at depth   : absent
`as` alias canonical : A\B::C::Legacy\Button
plain member         : A\B::C::Ui\Button
both namespaced      : resolve
ancestor internal    : b-secret
outside is denied    : denied
