<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain as api_domain;

/**
 * Class DomainInformationController
 */
class DomainInformationController extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $api_domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, api_domain $api_domain)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->api_domain = $api_domain;
    }

    /**
     * Get the nameservers.
     *
     * @param $params
     * @return array
     */
    function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        // Launch API
        $api    = $this->API;
        $domain = $this->api_domain;

        $api->setParams($params);

        try {
            $domain->load(array (
                'name' => $params['sld'],
                'extension' => $params['tld']
            ));
        } catch (\Exception $e) {
            return array
            (
                'error' => $e->getMessage(),
            );
        }

        // Get the data
        $op_domain                  = $api->retrieveDomainRequest($domain, true);
        $response = [];
        $response['domain']         = $op_domain['domain']['name'] . '.' . $op_domain['domain']['extension'];
        $response['tld']            = $op_domain['domain']['extension'];
        $response['nameservers']    = $this->getNameservers($api, $domain);
        $response['status']         = api_domain::convertOpStatusToWhmcs($op_domain['status']);
        $response['transferlock']   = ($op_domain['isLocked'] == 0 ? false : true);
        $response['expirydate']     = $op_domain['expirationDate'];
        $response['addons']['hasidprotect'] = ($op_domain['isPrivateWhoisEnabled'] == '1' ? true : false);

        // getting verification data
        $ownerEmail = '';
        try {
            $domain            = new \OpenProvider\API\Domain();
            $domain->name      = $op_domain['domain']['name'];
            $domain->extension = $op_domain['domain']['extension'];
            $ownerInfo         = $api->getContactDetails($domain);
            $ownerEmail        = $ownerInfo['Owner']['Email Address'];
            $args              = [
                'email'  => $ownerEmail,
            ];
            $emailVerification = $api->sendRequest('searchEmailVerificationDomainRequest', $args);
        } catch (Exception $e) {}

        // check email verification status and choose options depend on it
        $firstVerification = isset($emailVerification['results'][0]) ? $emailVerification['results'][0] : false;

        $verification = [];
        if (!$firstVerification) {
            try {
                $args['email'] = $ownerEmail;
                $reply = $api->sendRequest('startCustomerEmailVerificationRequest', $args);
                if (isset($reply['id'])) {
                    $firstVerification['status']         = 'in progress';
                    $firstVerification['isSuspended']    = false;
                    $firstVerification['expirationDate'] = false;
                }

            } catch (\Exception $e) {}
        }

        $verification = $this->getIrtpVerificationEmailOptions($firstVerification);

        $result = (new Domain)
            // domain part
            ->setDomain($domain)
            ->setNameservers($response['nameservers'])
            ->setRegistrationStatus($response['status'])
            ->setTransferLock($response['transferlock'])
            ->setExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $response['expirydate']), 'Europe/Amsterdam') // $response['expirydate'] = YYYY-MM-DD
            ->setIdProtectionStatus($response['addons']['hasidprotect'])
            // irtp part
            ->setIsIrtpEnabled($verification['is_irtp_enabled'])
            ->setIrtpOptOutStatus($verification['irtp_opt_status'])
            ->setIrtpTransferLock($verification['irtp_transfer_lock'])
            ->setDomainContactChangePending($verification['domain_contact_change_pending'])
            ->setPendingSuspension($verification['pending_suspension'])
            ->setIrtpVerificationTriggerFields($verification['irtp_verification_trigger_fields']);

        if ($verification['domain_contact_change_expiry_date'])
            $result->setDomainContactChangeExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $verification['domain_contact_change_expiry_date']));

        return $result;
    }

    /**
     * @param API $api
     * @param Domain $domain
     * @return array
     */
    private function getNameservers(API $api, api_domain $domain): array
    {
        $nameservers = $api->getNameservers($domain, true);
        $return = array ();
        $i = 1;

        foreach ($nameservers as $ns) {
            $return['ns' . $i] = $ns;
            $i++;
        }
        return $return;
    }

    /**
     * Function return array of parameters for irtp domain part to setup email verification
     *
     * @param array $verification data from result of searchEmailVerificationRequest
     * @return array
     * @see https://developers.whmcs.com/domain-registrars/transfer-policy-management/
     */
    private function getIrtpVerificationEmailOptions($verification): array
    {
        $allowedStatusesForPending = ['in progress', 'failed', 'not verified'];

        $result = [
            'is_irtp_enabled'                   => true,
            'irtp_opt_status'                   => true,
            'irtp_transfer_lock'                => false,
            'domain_contact_change_pending'     => false,
            'pending_suspension'                => false,
            'domain_contact_change_expiry_date' => false,
            'irtp_verification_trigger_fields'  => [
                'Registrant' => [
                    'First Name',
                    'Last Name',
                    'Organization Name',
                    'Email Address',
                ],
            ],
        ];

        if ($verification) {
            $result['domain_contact_change_pending']     = in_array($verification['status'], $allowedStatusesForPending);
            $result['pending_suspension']                = !!$verification['isSuspended'];
            $result['domain_contact_change_expiry_date'] = (
                isset($verification['expirationDate']) && $verification['expirationDate']
                    ? Carbon::createFromFormat('Y-m-d H:i:s', $verification['expirationDate'])
                    : false
            );
        }

        return $result;
    }
}