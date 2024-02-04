<?php
include 'db.php';

$link = 'https://' . SUBDOMAIN . '/oauth2/access_token';
$data = [
    'client_id' => CLIENT_ID,
    'client_secret' => CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => CODE,
    'redirect_uri' => REDIRECT_URI,
];

$curl = curl_init();
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl);

$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$code = (int)$code;
$errors = [
    400 => 'Bad request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not found',
    500 => 'Internal server error',
    502 => 'Bad gateway',
    503 => 'Service unavailable',
];

try
{

    if ($code < 200 || $code > 204) {
        throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
    }
}
catch(\Exception $e)
{
    die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}


$response = json_decode($out, true);

$access_token = $response['access_token'];
$refresh_token = $response['refresh_token'];
$token_type = $response['token_type'];
$expires_in = $response['expires_in'];

$timestamp = time() + 86400;

$expires_date = date('Y-m-d H:i:s', $timestamp);

$connection->query("INSERT INTO tokens (client_id, access_token, refresh_token, expires_at) VALUES ('" . CLIENT_ID . "', '" . $access_token . "', '" . $refresh_token . "', '" . $expires_date . "')");