--TEST--
unless is semi-reserved: still valid as a method name (BC for OO APIs)
--FILE--
<?php
class Query {
    public function unless(bool $cond): string {
        return "method named unless still callable";
    }
    const unless = 'constant named unless';
}
$q = new Query();
echo $q->unless(false), "\n";
echo Query::unless, "\n";
?>
--EXPECT--
method named unless still callable
constant named unless
