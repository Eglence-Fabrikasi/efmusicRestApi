<?php
require_once dirname(dirname(__FILE__)) . '/BL/Tables/tokenBlacklists.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    public function __invoke(Request $request, Handler $handler): Response
    {

        //$authHeader = $request->getHeaderLine('Authorization');
        //$token = str_replace('Bearer ', '', $authHeader);
        $token=isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : "";
        $tb = tokenBlacklists::getToken($token);
        if (!$token || $tb->ID>0) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Cookie token not provided']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            $request = $request->withAttribute('jwt', $decoded);
            return $handler->handle($request);
        } catch (Exception $e) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Invalid cookie token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }
}
