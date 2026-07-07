--TEST--
%% operator: euclidean modulo semantics on both folded and runtime paths
--FILE--
<?php
// Constant-folded path (literal operands)
var_dump(7 %% 3);
var_dump(-7 %% 3);
var_dump(7 %% -3);
var_dump(-7 %% -3);
var_dump(0 %% 5);
var_dump(6 %% 3);

echo "--- runtime path ---\n";
$vals = [[7, 3], [-7, 3], [7, -3], [-7, -3], [0, 5], [6, 3]];
foreach ($vals as [$a, $b]) {
    var_dump($a %% $b);
}

echo "--- result always in [0, |divisor|) ---\n";
$min = PHP_INT_MIN;
$neg1 = -1;
var_dump(PHP_INT_MIN %% -1);   // folded: no overflow
var_dump($min %% $neg1);       // runtime: no SIGFPE
var_dump(PHP_INT_MIN %% 10);
var_dump(PHP_INT_MAX %% -10);

echo "--- precedence and associativity (same tier as %) ---\n";
var_dump(2 + 9 %% 4);          // 2 + (9 %% 4) = 3
var_dump(-2 - -9 %% 4);        // -2 - ((-9) %% 4) = -2 - 3 = -5
var_dump(100 %% 7 %% 4);       // (100 %% 7) %% 4 = 2 %% 4 = 2
var_dump(3 * 4 %% 5);          // left-assoc within tier: (3*4) %% 5 = 2

echo "--- constant expressions ---\n";
const WRAP = -1 %% 12;
var_dump(WRAP);
class C {
    const HOUR = -25 %% 24;
}
var_dump(C::HOUR);
function f(int $x = -3 %% 5): int { return $x; }
var_dump(f());
?>
--EXPECT--
int(1)
int(2)
int(1)
int(2)
int(0)
int(0)
--- runtime path ---
int(1)
int(2)
int(1)
int(2)
int(0)
int(0)
--- result always in [0, |divisor|) ---
int(0)
int(0)
int(2)
int(7)
--- precedence and associativity (same tier as %) ---
int(3)
int(-5)
int(2)
int(2)
--- constant expressions ---
int(11)
int(23)
int(2)
