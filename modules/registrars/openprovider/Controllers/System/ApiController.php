<?php


namespace OpenProvider\WhmcsRegistrar\Controllers\System;


use OpenProvider\WhmcsRegistrar\src\OpenProvider;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WHMCS\Database\Capsule;

class ApiController extends BaseController
{
    /**
     * @var OpenProvider
     */
    private $openProvider;

    /**
     * ApiController constructor.
     */
    public function __construct(Core $core, OpenProvider $openProvider)
    {
        parent::__construct($core);
        $this->openProvider = $openProvider;
    }

    /**
     * Api function for update contacts tags.
     * Params is whmcs 'userid' and openprovider 'tag'.
     *
     * @param $params
     */
    public function updateContactsTag($params)
    {
        $userId = (
                isset($params['userid'])
                && !empty($params['userid'])
                && is_int(intval($params['userid']))
            )
            ? intval($params['userid'])
            : false;
        if ($userId === false) {
            responseError(400, 'user id is required!');
            return;
        }

        $tag = isset($params['tag'])
            ? (
                empty($params['tag'])
                    ? ''
                    : [
                        [
                            'key' => 'customer',
                            'value' => $params['tag']
                        ]
                    ]
            )
            : false;

        if ($tag === false) {
            responseError(400, 'tag is required!');
            return;
        }

        $usersContacts = Capsule::table('wHandles')
            ->where([
                ['user_id', '=', $userId],
                ['registrar', '=', 'openprovider']
            ])
            ->select('handle')
            ->get()
            ->map(function ($contact) {
                return $contact->handle;
            });

        $this->modifyContactsTag($usersContacts, $tag);

        responseSuccess();
    }

    private function modifyContactsTag($contactsHandles, $tags = '')
    {
        $api = $this->openProvider->api;

        foreach ($contactsHandles as $contactHandle) {
            try {
                $params = [
                    'handle' => $contactHandle,
                    'tags' => $tags,
                ];

                $api->sendRequest('modifyCustomerRequest', $params);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}

function responseSuccess($data = [], $code = 200)
{
    $result = array_merge($data, ['code' => $code, 'success' => true, 'error' => false]);
    echo json_encode($result);
}

function responseError($code = 400, $message = 'Bad request')
{
    $result = array_merge([
        'code' => $code,
        'success' => false,
        'error' => true,
        'message' => $message,
    ]);
    echo json_encode($result);
}