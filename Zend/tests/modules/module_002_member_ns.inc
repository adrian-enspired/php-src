<?php
// Membership declaration, then a namespace: the class is canonically module-rooted on its
// simple tail ("Vendor\User::PasswordChecker") and projects its namespace name
// ("Auth\PasswordChecker") as an outward alias.
module Vendor\User;
namespace Auth;

class PasswordChecker {
    public function tag(): string { return "checker"; }
}
