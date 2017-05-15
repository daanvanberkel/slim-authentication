<?php
namespace DvbSlimAuthentication\Middleware;

use DvbSlimAuthentication\DvbSlimAuthentication;
use Slim\Http\Request;
use Slim\Http\Response;

class UserMiddleware {
    public function __invoke(Request $request, Response $response, callable $next): Response {
        if (
            !isset($_SESSION['dvb_id_user']) ||
            !isset($_SESSION['dvb_loggedin'])
        ) {
            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('login_url'));
        }

        try {
            $user = DvbSlimAuthentication::getInstance()->getModel()->getUser($_SESSION['dvb_id_user']);
        } catch (\Exception $exception) {
            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('login_url'));
        }

        $request = $request->withAttribute('dvb_user', $user);

        return $next($request, $response);
    }
}