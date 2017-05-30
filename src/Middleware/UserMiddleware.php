<?php
namespace DvbSlimAuthentication\Middleware;

use DvbSlimAuthentication\DvbSlimAuthentication;
use Slim\Http\Request;
use Slim\Http\Response;

class UserMiddleware {
    public static $user;

    public function __invoke(Request $request, Response $response, callable $next): Response {
        if (
            (!isset($_SESSION['dvb_id_user']) || !isset($_SESSION['dvb_loggedin'])) &&
            !isset($_COOKIE['dvb_remember_me'])
        ) {
            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('login_url'));
        }

        try {
        	if (isset($_SESSION['dvb_id_user']) && isset($_SESSION['dvb_loggedin'])) {
		        $user = DvbSlimAuthentication::getInstance()->getModel()->getUser($_SESSION['dvb_id_user']);
	        } elseif (isset($_COOKIE['dvb_remember_me'])) {
        		$user = DvbSlimAuthentication::getInstance()->getRememberTokenModel()->getUserByToken($_COOKIE['dvb_remember_me']);
	        } else {
        		throw new \Exception("An error occurred");
	        }
        } catch (\Exception $exception) {
            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('login_url'));
        }

        self::$user = $user;

        $request = $request->withAttribute('dvb_user', $user);

        return $next($request, $response);
    }
}