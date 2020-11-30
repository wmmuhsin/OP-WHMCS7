<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;


use OpenProvider\WhmcsRegistrar\Helpers\DNS;

class DnsAuthController
{
    public function redirectDnsManagementPage ($params)
    {
        if($url = DNS::getDnsUrlOrFail($params['domainid']))
        {
            // Perform redirect.
            header("Location: " . $url);
            exit;
        }
    }
}
