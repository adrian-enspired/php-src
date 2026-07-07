--TEST--
unless statement: basic behavior
--FILE--
<?php
unless (false) {
    echo "runs when condition is false\n";
}
unless (true) {
    echo "never printed\n";
}
$x = 0;
unless ($x) {
    echo "falsy int runs\n";
}
unless ($x + 1) {
    echo "never printed\n";
}
// single-statement (no braces) form
unless (false) echo "single statement form\n";
// keywords are case-insensitive
UNLESS (false) {
    echo "case-insensitive\n";
}
// nesting
unless (false) {
    unless (0 == 1) {
        echo "nested\n";
    }
}
?>
--EXPECT--
runs when condition is false
falsy int runs
single statement form
case-insensitive
nested
