<?php

namespace OpenProvider\WhmcsRegistrar\src;

/**
 * Hardcoded configuration
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */
class Configuration
{
    protected static $api_url                           = 'https://api.openprovider.eu/';
    protected static $api_url_cte                       = 'https://api.cte.openprovider.eu/';
    protected static $OpenproviderPremium               = true;  //  Default: false, Support premium domains
    protected static $require_op_dns_servers            = true;  //  Default: true,  Require Openprovider DNS servers for DNS management
    protected static $sync_settings                     = true;  //  Default: true,  ===Don't understand why this parameter needed===
    protected static $syncUseNativeWHMCS                = true;  //  Default: true,  Use the native WHMCS synchronisation?
    protected static $syncDomainStatus                  = true;  //  Default: true,  Synchronize Domain status from Openprovider?
    protected static $syncAutoRenewSetting              = true;  //  Default: true,  Synchronize Auto renew setting to Openprovider?
    protected static $syncIdentityProtectionToggle      = true;  //  Default: true,  Synchronize Identity protection to Openprovider?
    protected static $syncExpiryDate                    = true;  //  Default: true,  Synchronize Expiry date from Openprovider?
    protected static $updateNextDueDate                 = false; //  Default: false, Synchronize due-date with offset?
    protected static $nextDueDateOffset                 = 14;    //  Default: 14,    Due-date offset
    protected static $nextDueDateUpdateMaxDayDifference = 100;   //  Default: 100,   Due-date max difference in days
    protected static $updateInterval                    = 2;     //  Default: 2,     Update interval
    protected static $domainProcessingLimit             = 200;   //  Default: 200,   Domain process limit
    protected static $sendEmptyActivityEmail            = false; //  Default: false, Send empty activity reports?
    protected static $various_settings                  = '';    //  Default: '',    ===Don't understand why this parameter needed===
    protected static $renewTldsUponTransferCompletion   = '';    //  Default: '',    Renew domains upon transfer completion
    protected static $useNewDnsManagerFeature           = false; //  Default: false, Use new DNS feature?

    /**
     * Return a value.
     *
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        return self::$$key;
    }

    /**
     * Return configuration params Array
     *
     * @return array
     */
    public static function getParams()
    {
        $params = get_class_vars(__CLASS__);
        $result = [];

        foreach ($params as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    public static function getOrDefault($key, $defaultValue = false)
    {
        $value = self::get($key);
        if (!$value) {
            return $defaultValue;
        }

        return $value;
    }

    public static function getApiUrl($apiMethod)
    {
        $serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER[HTTP_HOST]}";
        return "{$serverUrl}/modules/registrars/openprovider/api/{$apiMethod}";
    }
}