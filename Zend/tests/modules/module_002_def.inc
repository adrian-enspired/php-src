<?php
// Module definition: claims the split-file members public so they are reachable
// from outside the module. (Unclaimed split-file symbols are internal by default.)
namespace Vendor;
module User {
    public Auth\PasswordChecker;   // claim keys on the tail -> Vendor\User::PasswordChecker
    public GuestUser;
}
