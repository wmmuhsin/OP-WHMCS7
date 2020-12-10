<?php


namespace OpenProvider\WhmcsRegistrar\helpers;

use WHMCS\Database\Capsule;

class DB
{
    public static function checkTableExist($tableName)
    {
        try {
            return !!Capsule::table($tableName)->count();
        } catch(\Exception $e) {
            return false;
        }
    }
}