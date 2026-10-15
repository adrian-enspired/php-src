--TEST--
Modules: use-imports apply to the head of a module-qualified name ("use V\M; new M::A")
--DESCRIPTION--
A module-qualified reference "Head::Member" resolves its head through the file's imports, like
the class part of "Head::CONST". A head that is not imported is taken as written.
--FILE--
<?php
namespace V {
    module M {
        public class A {}
        public module N { public class B {} }
    }
}
namespace {
    use V\M;
    use V\M as MM;
    use V\M::A as AA;
    use V\M::N;

    var_dump(new M::A, new MM::A, new AA, new N::B, new M::N::B);
    var_dump(M::A::class, MM::N::B::class);
    var_dump((new M::A) instanceof MM::A);
    function f(M::A $a): MM::A { return $a; }
    var_dump(f(new AA) instanceof \V\M::A);

    // an unimported head is still taken as written (fully qualified)
    try { new W::A; } catch (Error $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECT--
object(V\M::A)#1 (0) {
}
object(V\M::A)#2 (0) {
}
object(V\M::A)#3 (0) {
}
object(V\M::N::B)#4 (0) {
}
object(V\M::N::B)#5 (0) {
}
string(6) "V\M::A"
string(9) "V\M::N::B"
bool(true)
bool(true)
Class "W::A" not found
