<?php

require_once './ExUbiUsersCleaner.php';

$exUbiUsersCleaner = new ExUbiUsersCleaner(
    __DIR__ . DIRECTORY_SEPARATOR . 'ex_ubi_users_keycloak_next2.csv',
    'https://authentification.prod.service.2cloud.app/auth/realms/master/protocol/openid-connect/token'
);


$token = $exUbiUsersCleaner->getTokenFromKeycloak();

$exUbiUsersCleaner->cleanExUbiUsers($token);