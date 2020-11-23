<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\API;
use OpenProvider\API\APIConfig;
use WeDevelopCoffee\wPower\Models\Registrar;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class ConfigController
 */
class ConfigController extends BaseController
{
    /**
     * @var API
     */
    private $API;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API)
    {
        parent::__construct($core);

        $this->API = $API;
    }

    /**
     * Generate the configuration array.
     * @param $params
     * @return array|mixed
     */
    public function getConfig($params)
    {
        // Get the basic data.
        $configarray = $this->getConfigArray();

        // Process any updated data.
        list($configarray, $params) = $this->parsePostInput($params, $configarray);

        // If we have some login data, let's try to login.
        if(isset($params['Password']) && isset($params['Username']) && isset($params['OpenproviderAPI']))
        {
            try
            {
                // Try to login and fetch the DNS template data.
                $configarray = $this->fetchDnsTemplates($params, $configarray);
            }
            catch (\Exception $ex)
            {
                // Failed to login. Generate a warning.
                $configarray = $this->generateLoginError($configarray);
            }
        }

        return $configarray;
    }

    /**
     * Process the latest post information as WHMCS does not provide the latest information by default.
     *
     * @param $params
     * @param array $configarray
     * @return array
     */
    protected function parsePostInput($params, array $configarray)
    {
        $x = explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
        $filename = end($x);
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save' && $filename == 'configregistrars.php') {
            foreach ($_REQUEST as $key => $val) {
                if (isset($configarray[$key])) {
                    // Prevent that we will overwrite the actual password with the stars.
                    if (substr($val, 0, 3) != '***') {
                        $params[$key] = $val;
                    }
                }
            }
        }

        return array ($configarray, $params);
    }

    /**
     * Try to login and fetch the DNS templates.
     *
     * @param $params
     * @param $configarray
     * @return mixed
     */
    protected function fetchDnsTemplates($params, $configarray)
    {
        if(!strpos($_SERVER['PHP_SELF'], 'configregistrars.php'))
        {
            // We are not on the admin page. Let's use a cached version of this.
            $cached_dns_template = Registrar::getByKey('openprovider', 'dnstemplate_cache');

            if($cached_dns_template != '')
            {
                $configarray['dnsTemplate'] = json_decode($cached_dns_template, true);
                return $configarray;
            }
        }

        if(isset($GLOBALS['op_registrar_module_config_dnsTemplate']))
        {
            $configarray['dnsTemplate'] = $GLOBALS['op_registrar_module_config_dnsTemplate'];
            return $configarray;
        }

        $api = $this->API;
        $api->setParams($params);
        $templates = $api->searchTemplateDnsRequest();

        if (isset($templates['total']) && $templates['total'] > 0) {
            $tpls = 'None,';
            foreach ($templates['results'] as $template) {
                $tpls .= $template['name'] . ',';
            }
            $tpls = trim($tpls, ',');

            $configarray['dnsTemplate'] = array
            (
                "FriendlyName" => "DNS Template",
                "Type" => "dropdown",
                "Description" => "DNS template will be used when a domain is created or transferred to your account",
                "Options" => $tpls
            );
        }

        $GLOBALS['op_registrar_module_config_dnsTemplate'] = $configarray['dnsTemplate'];

        Registrar::updateByKey('openprovider', 'dnstemplate_cache', json_encode($configarray['dnsTemplate']));

        return $configarray;
    }

    /**
     * Generate a login error message.
     *
     * @param $configarray
     * @return mixed
     */
    protected function generateLoginError($configarray)
    {
        $loginFailed = [
            'FriendlyName' => '<b><strong style="color:Tomato;">Login Unsuccessful:</strong></b>',
            'Description' => '<b><strong style="color:Tomato;">Please ensure credentials and URL are correct</strong></b>'
        ];

        // Create a separate array to put the warning at the top as well.
        $firstArray[] = $loginFailed;

        //warn user that login failed at the end.
        $configarray['loginFailed'] = $loginFailed;

        $configarray['Username']['FriendlyName'] = '<b><strong style="color:Tomato;">*Username</strong></b>';
        $configarray['Password']['FriendlyName'] = '<b><strong style="color:Tomato;">*Password</strong></b>';

        return array_merge($firstArray, $configarray);
    }

    /**
     * The configuration array base.
     *
     * @return array
     */
    public function getConfigArray()
    {
        return array
        (
            "version"   => array
            (
                "FriendlyName"  => "Module Version",
                "Type"          => "text",
                "Description"   => APIConfig::getModuleVersion() . "<style>input[name='version']{display: none;}</style>",
            ),
            "Username"          => array
            (
                "FriendlyName"  => "Username",
                "Type"          => "text",
                "Size"          => "20",
                "Description"   => "Openprovider login",
            ),
            "Password"          => array
            (
                "FriendlyName"  => "Password",
                "Type"          => "password",
                "Size"          => "20",
                "Description"   => "Openprovider password",
            ),
            "test_mode"   => array
            (
                "FriendlyName"  => "Openprovider Test mode",
                "Type"          => "yesno",
                "Description"   => "Enable this to use api.cte.openprovider.eu. Defaults to production API.",
                "Default"       => "no"
            ),
        );
    }
}