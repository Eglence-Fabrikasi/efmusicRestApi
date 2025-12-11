<?php

require_once dirname(dirname(__FILE__)) . '/BL/Tables/tokenBlacklists.php';

use \Firebase\JWT\JWT;
use \Tuupola\Base62;



class token
{

    function generateToken($user_id)
    {
        $secret_key = $_ENV["SECRET_KEY"];
        $issued_at = time();
        $expiration_time = $issued_at + 3600; // token valid for 1 hour
        $payload = array(
            'user_id' => $user_id,
            'iat' => $issued_at,
            'exp' => $expiration_time
        );
        return JWT::encode($payload, $secret_key,'HS256');
    }

    function verifyToken($token)
    {
        $secret_key = $_ENV["SECRET_KEY"];
        try {
            $payload = JWT::decode($token, $secret_key, array('HS256'));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    function logout($token)
    {
        $secret_key = $_ENV["SECRET_KEY"];
        $decoded = JWT::decode($token, $secret_key, array('HS256'));

        // expire süresini al
        $expiresAt = date("Y-m-d H:i:s", $decoded->exp);

        $tb = tokenBlacklists::getToken($token);
        $tb->ctoken=$token;
        $tb->expiresAt=$expiresAt;
        $tb->save();

        return true;
    }
}
