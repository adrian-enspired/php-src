--TEST--
Modules: classes and namespaces named Internal keep working (amphp, psalm patterns)
--DESCRIPTION--
`internal` is only a keyword in modifier position. A namespace named Internal can be imported
(`use Amp\Internal;` then `Internal\foo()`), and a class named Internal declared, imported,
used as an attribute, a type, and with `new` -- as psalm/attributes does.
--FILE--
<?php
namespace Amp\Internal {
    function formatStacktrace() { return 'fn'; }
    const X = 'const';
    class Helper { const K = 'class'; }
}
namespace Psalm {
    #[\Attribute]
    final class Internal { public function __construct(public string $scope = '') {} }
}
namespace App {
    use Amp\Internal as AmpInternal;
    use Psalm\Internal;

    echo AmpInternal\formatStacktrace(), ' ', AmpInternal\X, ' ', AmpInternal\Helper::K, "\n";

    #[Internal('App')]
    class Tagged {
        public ?Internal $tag = null;
        public static ?Internal $default = null;
        public function with(Internal $t): Internal { return $t; }
    }
    $attr = (new \ReflectionClass(Tagged::class))->getAttributes(Internal::class)[0]->newInstance();
    var_dump($attr->scope, get_class((new Tagged)->with(new Internal ('x'))));
    try { throw new \Exception('e'); } catch (Internal|\Exception $e) { echo "caught\n"; }
}
namespace X {
    use Amp\Internal;
    echo Internal\formatStacktrace(), "\n";
}
?>
--EXPECT--
fn const class
string(3) "App"
string(14) "Psalm\Internal"
caught
fn
