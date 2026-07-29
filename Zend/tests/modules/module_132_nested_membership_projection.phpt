--TEST--
Modules: a nested module's membership file projects to ordinary namespaced names
--DESCRIPTION--
A membership file's base namespace is the module's NAMESPACED form, with each "::" boundary
rewritten as "\". For a top-level module the two forms are identical, but for a nested one
("Outer::Inner") seeding the base with the canonical name leaks "::" into every namespaced
name built from it, yielding hybrids like "Outer::Inner\C" that are neither a canonical name
nor a namespaced one -- and that resolve against the wrong module's roster.

Members keep their canonical "::" identity; only the projection is normalized.
--FILE--
<?php
$dir = __DIR__ . '/mod132_tmp';
@mkdir($dir);

file_put_contents($dir . '/manifest.php', <<<'PHP'
<?php
namespace Vendor;
module Pkg {
    public module Nested {
        public Plain;
        public Sub\Deep;
    }
}
PHP);

// nested membership, no sub-namespace
file_put_contents($dir . '/plain.php', <<<'PHP'
<?php
module Vendor\Pkg::Nested;
class Plain {
    public function me() : Plain { return $this; }   // bare module-relative return type
}
PHP);

// nested membership, with a sub-namespace
file_put_contents($dir . '/deep.php', <<<'PHP'
<?php
module Vendor\Pkg::Nested;
namespace Sub;
class Deep {
    public function me() : Deep { return $this; }
}
PHP);

require $dir . '/manifest.php';
require $dir . '/plain.php';
require $dir . '/deep.php';

$p = new Vendor\Pkg::Nested::Plain();
$d = new Vendor\Pkg::Nested::Deep();

echo "canonical (plain) : ", get_class($p->me()), "\n";
echo "canonical (sub-ns): ", get_class($d->me()), "\n";
echo "namespaced (plain): ", class_exists('Vendor\Pkg\Nested\Plain', false) ? 'resolves' : 'MISSING', "\n";
echo "namespaced (sub)  : ", class_exists('Vendor\Pkg\Nested\Sub\Deep', false) ? 'resolves' : 'MISSING', "\n";
echo "no hybrid form    : ", class_exists('Vendor\Pkg::Nested\Plain', false) ? 'LEAKED' : 'absent', "\n";

// a flat module is unaffected -- the two forms coincide there
echo "same class?       : ", (new ReflectionClass('Vendor\Pkg\Nested\Plain'))->getName(), "\n";
?>
--CLEAN--
<?php
$dir = __DIR__ . '/mod132_tmp';
foreach (glob($dir . '/*.php') as $f) { @unlink($f); }
@rmdir($dir);
?>
--EXPECT--
canonical (plain) : Vendor\Pkg::Nested::Plain
canonical (sub-ns): Vendor\Pkg::Nested::Sub\Deep
namespaced (plain): resolves
namespaced (sub)  : resolves
no hybrid form    : absent
same class?       : Vendor\Pkg::Nested::Plain
