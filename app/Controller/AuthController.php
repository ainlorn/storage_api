<?php

namespace App\Controller;

use App\Dto\AuthResponse;
use App\Dto\BaseResponse;
use App\Dto\UserDto;
use App\Model\SessionDao;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response as Response;
use App\Model\UserDao;

class AuthController {
    public function __invoke(Request $request, Response $response, $args) {
        $body = $request->getParsedBody();

        $username = $body['username'];
        $password = $body['password'];
        if (!$username || !$password) {
            return $response->withJson(new BaseResponse("Отсутствует логин или пароль"),
                400, JSON_UNESCAPED_UNICODE);
        }

        $userDao = new UserDao();
        $user = $userDao->getUserByName($username);
        if ($user) {
            if (password_verify($password, $user->password_hash)) {
                $sessionDao = new SessionDao();
                $sid = $sessionDao->createSession($user->id);
                return $response->withJson(new AuthResponse(UserDto::fromSql($user), $sid));
            }
        }

        return $response->withJson(new BaseResponse("Неверный логин или пароль"),
            400, JSON_UNESCAPED_UNICODE);
    }
}