<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use OpenProvider\WhmcsRegistrar\Helpers\DNS;

/**
 * Class DnsAuthController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */

class ClientAreaPrimarySidebarController{

    public function show ($primarySidebar)
    {
       $this->replaceDnsMenuItem($primarySidebar);

       $this->addDNSSECMenuItem($primarySidebar);
    }

    private function replaceDnsMenuItem($primarySidebar)
    {
        // Filter to the Domain menu
        if(!$domainDetailsManagement = $primarySidebar->getChild('Domain Details Management'))
            return;

        if(!$dnsManagement = $domainDetailsManagement->getChild('Manage DNS Host Records'))
            return;

        if($url = DNS::getDnsUrlOrFail($_REQUEST['domainid']))
        {
            // Update the URL.
            $dnsManagement->setUri($url);

            // WHMCS does not natively support target="_blank".
            $id = $dnsManagement->getId();
            $label = $dnsManagement->getLabel() . '</a>
<script >
jQuery( document ).ready(function() {
    jQuery("#' . $id . '").attr("target","_blank");
});
</script>
<a href=\'#\' style=\'display:none;\'>';
            $dnsManagement->setLabel($label);
        }
    }

    private function addDNSSECMenuItem($primarySidebar)
    {
        if (!is_null($primarySidebar->getChild('Domain Details Management'))) {
            $domainId = isset($_REQUEST['domainid']) ? $_REQUEST['domainid'] : $_REQUEST['id'];
            $primarySidebar->getChild('Domain Details Management')
                ->addChild('DNSSEC')
                ->setLabel('DNSSEC Rocords')
                ->setUri("dnssec.php?domainid={$domainId}")
                ->setOrder(100);
        }
    }
}
