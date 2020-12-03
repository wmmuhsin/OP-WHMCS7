<?php

include ('../api.php');

$params = [
    'tag'    => $_GET['tag'],
    'userid' => $_GET['userid'],
];

openprovider_registrar_launch('system')
    ->output($params, 'updateContactsTagApi');
