--TEST--
unless statement: basic behavior (falsy runs, truthy skips, case-insensitive, nesting)
--FILE--
<?php
unless (false) {
    echo "runs when condition is false\n";
}
unless (true) {
    echo "never printed\n";
}
unless (0) {
    echo "runs on falsy int\n";
}
unless (1) {
    echo "never printed either\n";
}
// single-statement (no braces) form
unless (false) echo "single statement form\n";
// keywords are case-insensitive
UNLESS (false) {
    echo "case-insensitive\n";
}
// nesting
unless (false) {
    unless (0 > 1) {
        echo "nested\n";
    }
}
?>
--EXPECT--
runs when condition is false
runs on falsy int
single statement form
case-insensitive
nested
