<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

use Illuminate\Support\Facades\App;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\WhmcsRegistrar\src\OpenProvider;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
/**
 * Class AdminClientProfileTabController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2018
 */


class AdminClientProfileTabController
{
    public function additionalFields($vars)
    {
        $tagsData = [];
        $customerEmail = $vars['model']->email;
        $OpenProvider  = new OpenProvider();

        try {
            $tagsData = $OpenProvider->api->sendRequest('searchTagRequest');
        } catch (\Exception $e) {}

        $tags = [];
        if (isset($tagsData['results']) && count($tagsData['results']) > 0) {
            $tags = array_map(function ($item) {
                return $item['value'];
            }, $tagsData['results']);
        }

        $customers = [];
        try {
            $params = [
                'emailPattern' => $customerEmail,
            ];
            $customers = $OpenProvider->api->sendRequest('searchCustomerRequest', $params);
        } catch (\Exception $e) {}

        $selectedTag = '';
        if (count($customers['results']) > 0) {
            $customer = $customers['results'][0];
            $selectedTag = isset($customer['tags'][0])
                ? $customer['tags'][0]['value']
                : '';
        }

        $options = false;
        if (count($tags))
            $options = implode('', array_map(function ($tag) use ($selectedTag) {
                if ($selectedTag == $tag)
                    return "<option value='{$tag}' selected>{$tag}</option>";
                return "<option value='{$tag}'>{$tag}</option>";
            }, $tags));

        $onClickUpdateContactsTag = "
<script>
    $('.update-contacts-tag').on('click', function (e) {
        e.preventDefault();
        let btn = $(this);
        let tag = $('select[name=additionalFieldTag]').val();
        const searchUrlParams = new URLSearchParams(window.location.search);
        let userid = searchUrlParams.has('userid') ? searchUrlParams.get('userid') : '';
        btn.attr('disabled', true);
        $.ajax({
            method: 'GET',
            url: '" . Configuration::getApiUrl('contacts-tag-update') . "',
            data: {
                tag,
                userid,
            }
        }).done(function (reply) {
            btn.attr('disabled', false);
        });
        return false;
    })            
</script>
        ";

        return [
            'Tag' => "<select tabindex='50' class='form-control input-300' name='additionalFieldTag'><option value=''>Нет</option>{$options}</select>",
            'Update contacts\' tags (It make take a while)' => '<button class="update-contacts-tag">Update</button>' . $onClickUpdateContactsTag,
        ];
    }

    public function saveFields($vars)
    {
        $tag = $vars['additionalFieldTag'];
        $customerEmail = $vars['email'];
        $tags = $tag
            ? [
                [
                    'key' => 'customer',
                    'value' => $tag,
                ]
            ]
            : '';

        $OpenProvider = new OpenProvider();
        $api = $OpenProvider->api;

        $customers = [];
        try {
            $params = [
                'emailPattern' => $customerEmail,
            ];

            $customers = $api->sendRequest('searchCustomerRequest', $params);
        } catch (\Exception $e) {}

        if (count($customers['results']) > 0) {
            $customer = $customers['results'][0];
            if ($customer['tags'] == $tags || (isset($customer['tags'][0]['value']) && $customer['tags'][0]['value'] == $tag)) {
                return;
            }

            $customerHandle = $customers['results'][0]['handle'];

            $params = [
                'handle' => $customerHandle,
                'tags' => $tags,
            ];

            try {
                $reply = $api->sendRequest('modifyCustomerRequest', $params);
            } catch (\Exception $e) {}
        }
    }
}