<?php

$root_path = dirname(__DIR__);

require_once $root_path.'/src/Ldap.php';
require_once $root_path.'/src/Ldap/Exception.php';
require_once $root_path.'/src/Ldap/SearchFilterBuilder.php';

use \Wjs\Support\Ldap;

$ldap = new Ldap;

function test_query_string ($ldap)
{
    $ldap
        ->where('objectclass', 'posixGroup')
        ->orWhereNot('description', '*default*')
        ->where('uid', 'ethan')
        ->orWhereNot(Ldap::raw('number>=999'));

    echo $ldap->getFilterQueryString();
    echo "\n";
}

function test_same_query_string ($ldap)
{
    $test_query = '(&(objectclass=posixAccount)(memberof=cn=realuser,cn=groups,dc=example,dc=com)(!(shadowexpire=1)))';
    $ldap
        ->where('objectclass', 'posixAccount')
        ->where('memberof', 'cn=realuser,cn=groups,dc=example,dc=com')
        ->whereNot('shadowexpire', 1);

    echo $ldap->getFilterQueryString();
    echo "\n";
    echo $test_query;
    echo "\n";
    echo $ldap->getFilterQueryString() === $test_query;
    echo "\n";
}

function test_query_data ($ldap)
{
    $ldap->createConnection(array(
        'hostname' => 'ldap.example.com',
        'base_dn'  => 'dc=example,dc=com',
    ));
    var_dump(
        $ldap
            ->where('objectclass', 'posixAccount')
            ->whereNot('memberof', 'cn=non-human,cn=groups,dc=example,dc=com')
            ->whereNot('shadowexpire', 1)
            ->get()
    );
}

function test_bind ($ldap)
{
    $ldap->createConnection(array(
        'hostname' => 'ldap.example.com',
        'base_dn'  => 'dc=example,dc=com',
    ));
    $ldap->bind('account', 'password');
    echo 'Success.';
    echo "\n";
}

try
{
    test_query_data($ldap);
    // test_bind($ldap);
}
catch (\Exception $e)
{
    echo $e->getMessage();
    echo "\n";
}
