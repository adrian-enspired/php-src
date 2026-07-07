--TEST--
%% operator: error conditions (division by zero, bad types, float coercion)
--FILE--
<?php
// Literal zero divisor: folding must be refused, error stays runtime + catchable
try {
    var_dump(5 %% 0);
} catch (DivisionByZeroError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}

// Runtime zero divisor
$zero = 0;
try {
    var_dump(5 %% $zero);
} catch (DivisionByZeroError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}

// Unsupported operand types
try {
    var_dump("abc" %% 3);
} catch (TypeError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}
try {
    var_dump([1] %% 3);
} catch (TypeError $e) {
    echo get_class($e), ": ", $e->getMessage(), "\n";
}

// Floats deprecate-and-truncate, like %
$f = 7.5;
var_dump($f %% 3);

// Numeric strings coerce, like %
$s = "-7";
var_dump($s %% 3);
?>
--EXPECTF--
DivisionByZeroError: Modulo by zero
DivisionByZeroError: Modulo by zero
TypeError: Unsupported operand types: string %% int
TypeError: Unsupported operand types: array %% int

Deprecated: Implicit conversion from float 7.5 to int loses precision in %s on line %d
int(1)
int(2)
