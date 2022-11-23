<?php
namespace App\Middleware;

use App\Model\UserDao as UserDao;
use App\Dto\UserDto as UserDto;
use App\Dto\BaseResponse as BaseResponse;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as ResponseImpl;

class AuthMiddleware {
    public function __invoke(Request $request, RequestHandler $handler): Response {
        $cookies = $request->getCookieParams();
        if (!array_key_exists("sid", $cookies)) {
            $header = $request->getHeader("X-Session-Id");
            if (count($header) !== 1)
                return $this->failResponse();
            $sid = $header[0];
        } else {
            $sid = $cookies["sid"];
        }

        $dao = new UserDao();
        $user = $dao->getUserBySessionId($sid);
        if ($user == null)
            return $this->failResponse();

        $request = $request->withAttribute("user", UserDto::fromSql($user));
        return $handler->handle($request);
    }

    private function failResponse() {
        $response = new ResponseImpl(401);

        $response->getBody()->write(
            json_encode(new BaseResponse("Не авторизован"), JSON_UNESCAPED_UNICODE)
        );

        return $response->withHeader('Content-Type', 'application/json');
    }
}