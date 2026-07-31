--TEST--
Modules: an internal method is gated on every callable-resolution route, not only VM call opcodes
--FILE--
<?php
// The VM call opcodes reach an internal method through zend_std_get_method() /
// zend_std_get_static_method(), where the gate lives. zend_is_callable_ex() is a
// SECOND resolver: zend_is_method_callable() finds the method by a direct
// zend_hash_find() in ce->function_table and only consults the handlers on a miss.
// An internal method is ZEND_ACC_PUBLIC|ZEND_ACC_MODULE_INTERNAL, so the hash hits
// and the handler -- and the gate -- is skipped. Both resolvers must agree.

module M {
    public class C {
        internal function inst(): string { return "inst"; }
        internal static function stat(): string { return "stat"; }
        public function ok(): string { return "ok"; }

        /* The same routes, exercised from INSIDE the module: all must keep working.
         * A gate that denies these is over-restrictive, not safe. */
        public static function insideRoutes(C $c): string {
            return implode(",", [
                call_user_func([$c, "inst"]),
                call_user_func("M::C::stat"),
                array_map([$c, "inst"], [1])[0],
                (\Closure::fromCallable([$c, "inst"]))(),
                is_callable([$c, "inst"]) ? "callable" : "NOT-callable",
            ]);
        }
    }
}

$c = new M::C();

/* Report only denied-vs-leaked. The exception CLASS differs per route (Error from a
 * call opcode, TypeError from a ZPP callable parameter) and is a property of the
 * route, not of the module gate -- asserting it here would make this test brittle. */
function deny(string $label, callable $fn): void {
    try {
        printf("%-34s LEAKED %s\n", $label, var_export($fn(), true));
    } catch (\Throwable $e) {
        printf("%-34s DENIED\n", $label);
    }
}

// The public surface is unaffected.
var_dump($c->ok());

// --- Already gated: VM call opcodes reach the object handlers. ---
deny('$c->inst()',                    fn() => $c->inst());
deny('[$c,"inst"]()',                 fn() => [$c,"inst"]());
deny('["M::C","stat"]()',             fn() => ["M::C","stat"]());
deny('$f="M::C::stat"; $f()',         function () { $f = "M::C::stat"; return $f(); });

// --- The second resolver: everything below routes through zend_is_callable_ex(). ---
deny('call_user_func([$c,"inst"])',   fn() => call_user_func([$c,"inst"]));
deny('call_user_func("M::C::stat")',  fn() => call_user_func("M::C::stat"));
deny('call_user_func_array(...)',     fn() => call_user_func_array([$c,"inst"], []));
deny('array_map([$c,"inst"],[1])',    fn() => array_map([$c,"inst"], [1]));
deny('array_map("M::C::stat",[1])',   fn() => array_map("M::C::stat", [1]));
deny('Closure::fromCallable([...])',  fn() => (Closure::fromCallable([$c,"inst"]))());
deny('Closure::fromCallable("...")',  fn() => (Closure::fromCallable("M::C::stat"))());

// A first-class callable taken from outside must not launder the method either.
deny('$c->inst(...)',                 fn() => ($c->inst(...))());

// is_callable() must not advertise a method the caller cannot reach.
var_dump(is_callable([$c, "inst"]));
var_dump(is_callable("M::C::stat"));
var_dump(is_callable(["M::C", "stat"]));

// ...but syntax-only mode is a SYNTAX check, not an access check: it returns before
// any resolution happens, so it stays true. This is intended, not a leak.
var_dump(is_callable("M::C::stat", true));

// --- In-module callers keep every route. ---
$cls = "M::C";
echo $cls::insideRoutes($c), "\n";
?>
--EXPECT--
string(2) "ok"
$c->inst()                         DENIED
[$c,"inst"]()                      DENIED
["M::C","stat"]()                  DENIED
$f="M::C::stat"; $f()              DENIED
call_user_func([$c,"inst"])        DENIED
call_user_func("M::C::stat")       DENIED
call_user_func_array(...)          DENIED
array_map([$c,"inst"],[1])         DENIED
array_map("M::C::stat",[1])        DENIED
Closure::fromCallable([...])       DENIED
Closure::fromCallable("...")       DENIED
$c->inst(...)                      DENIED
bool(false)
bool(false)
bool(false)
bool(true)
inst,stat,inst,inst,callable
