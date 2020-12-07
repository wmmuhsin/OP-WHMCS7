<?php

use WHMCS\ClientArea;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;

define('CLIENTAREA', true);

const PAGE_TITLE  = 'DNSSEC Records';
const PAGE_NAME   = 'DNSSEC Records';
const MODULE_NAME = 'dnssec';

require __DIR__ . '/init.php';

$ca = new ClientArea();

$ca->setPageTitle(PAGE_NAME);

$domainId = $_GET['domainid'];
$domain = \WHMCS\Database\Capsule::table('tbldomains')
    ->where('id', $domainId)
    ->first();
$domainName = $domain->domain;

$OpenProvider = new OpenProvider();
$api = $OpenProvider->api;

$domainArray = explode('.', $domainName);
$args = [
    'domain' => [
        'extension' => $domainArray[count($domainArray) - 1],
        'name' => implode('.', array_slice($domainArray, 0, count($domainArray) - 1)),
    ],
];

$dnssecKeys = [];
$isDnssecEnabled = false;
try {
    $domain = $api->sendRequest('retrieveDomainRequest', $args);
    $dnssecKeys = $domain['dnssecKeys'];
    $isDnssecEnabled = $domain['isDnssecEnabled'];
} catch (\Exception $e) {
    var_dump($e->getMessage());
}

$ca->assign('dnssecKeys', $dnssecKeys);
$ca->assign('isDnssecEnabled', $isDnssecEnabled);
$ca->assign('apiUrlUpdateDnssecRecords', Configuration::getApiUrl('dnssec-record-update'));
$ca->assign('apiUrlTurnOnOffDnssec', Configuration::getApiUrl('dnssec-enabled-update'));
$ca->assign('domainId', $domainId);
$ca->assign('jsModuleUrl', Configuration::getJsModuleUrl(MODULE_NAME));
$ca->assign('cssModuleUrl', Configuration::getCssModuleUrl(MODULE_NAME));

$ca->addToBreadCrumb('index.php', Lang::trans('globalsystemname'));
$ca->addToBreadCrumb('clientarea.php', Lang::trans('clientareatitle'));
$ca->addToBreadCrumb('clientarea.php?action=domains', Lang::trans('clientareanavdomains'));
$ca->addToBreadCrumb('clientarea.php?action=domaindetails&id=' . $domainId, $domainName);
$ca->addToBreadCrumb('dnssec.php', PAGE_NAME);

$ca->initPage();

$ca->requireLogin();

$primarySidebar = Menu::primarySidebar('domainView');

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Overview')
    ->setLabel('Overview')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}")
    ->setOrder(0);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Auto Renew')
    ->setLabel('Auto Renew')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAutorenew")
    ->setOrder(10);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Nameservers')
    ->setLabel('Nameservers')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabNameservers")
    ->setOrder(20);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Addons')
    ->setLabel('Addons')
    ->setUri("clientarea.php?action=domaindetails&id={$domainId}#tabAddons")
    ->setOrder(30);

$primarySidebar->getChild('Domain Details Management')
    ->addChild('Contact Information')
    ->setLabel('Contact Information')
    ->setUri("clientarea.php?action=domaincontacts&domainid={$domainId}")
    ->setOrder(40);

$ca->setTemplate('/modules/registrars/openprovider/includes/templates/dnssec.tpl');

$ca->output();
