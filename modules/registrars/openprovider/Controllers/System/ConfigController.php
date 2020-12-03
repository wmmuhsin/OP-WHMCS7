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