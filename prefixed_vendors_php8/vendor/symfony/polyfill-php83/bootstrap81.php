<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (\PHP_VERSION_ID >= 80300) {
    return;
}
if (!\function_exists('Org\\Wplake\\Advanced_Views\\Optional_Vendors\\ldap_exop_sync') && \function_exists('ldap_exop')) {
    function ldap_exop_sync(\LDAP\Connection $ldap, string $request_oid, ?string $request_data = null, ?array $controls = null, &$response_data = null, &$response_oid = null) : bool
    {
        return \ldap_exop($ldap, $request_oid, $request_data, $controls, $response_data, $response_oid);
    }
}
if (!\function_exists('Org\\Wplake\\Advanced_Views\\Optional_Vendors\\ldap_connect_wallet') && \function_exists('ldap_connect')) {
    function ldap_connect_wallet(?string $uri, string $wallet, #[\SensitiveParameter] string $password, int $auth_mode = \Org\Wplake\Advanced_Views\Optional_Vendors\GSLC_SSL_NO_AUTH) : \LDAP\Connection|false
    {
        return \ldap_connect($uri, $wallet, $password, $auth_mode);
    }
}
