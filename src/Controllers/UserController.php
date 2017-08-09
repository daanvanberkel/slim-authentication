<?php
namespace DvbSlimAuthentication\Controllers;

use DvbPhpMessages\MessageHelper;
use DvbSlimAuthentication\DvbSlimAuthentication;
use DvbSlimAuthentication\Entities\User;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController {
	public function login(Request $request, Response $response): Response {
	    if (isset($_SESSION['dvb_id_user']) && isset($_SESSION['dvb_loggedin']) && $_SESSION['dvb_loggedin'] == true) {
	        return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_login_url'));
        }

		$view = DvbSlimAuthentication::getInstance()->getConfigItem('login_template');
		$model = DvbSlimAuthentication::getInstance()->getModel();
		$remember_model = DvbSlimAuthentication::getInstance()->getRememberTokenModel();

		if (empty($request->getParsedBodyParam('dvb_submit'))) {
			return $response->write($view);
		}

        $email = $request->getParsedBodyParam('dvb_email');
        $password = $request->getParsedBodyParam('dvb_password');
        $two_factor = $request->getParsedBodyParam('dvb_two_factor');
        $remember_me = (bool) $request->getParsedBodyParam('dvb_remember_me', false);

        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Enter a valid email");
            }

            $user = $model->getUserByEmail($email);

            if (!$model->validatePassword($user, $password)) {
                throw new \Exception("Password not valid");
            }

            $_SESSION['dvb_id_user'] = $user->getIdUser();

            if (empty($user->getTwoFactorCode()) && DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_enabled')) {
                return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_setup_url'));
            }

            if (DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_enabled') && !$model->getTwoFactorInstance()->verifyCode($user->getTwoFactorCode(), $two_factor)) {
                throw new \Exception("Two factor code not valid");
            }

            if ($remember_me) {
                $token = $remember_model->saveNewToken($user);

                setcookie('dvb_remember_me', $token->getToken(), $token->getExpireDate()->getTimestamp(), '/', $request->getUri()->getHost(), true, true);
            }

            $_SESSION['dvb_loggedin'] = true;

            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_login_url'));
        } catch (\Throwable $exception) {
            MessageHelper::getInstance()->addError($exception->getMessage());

            $response = $response->write(MessageHelper::getInstance()->getMessagesHTML());
            return $response->write($view);
        }
	}

	public function register(Request $request, Response $response): Response {
	    if (DvbSlimAuthentication::getInstance()->getConfigItem('enable_registration') !== true) {
	        return $response->withStatus(404);
        }

        $view = DvbSlimAuthentication::getInstance()->getConfigItem('register_template');
        $model = DvbSlimAuthentication::getInstance()->getModel();

        if (empty($request->getParsedBodyParam('dvb_submit'))) {
            return $response->write($view);
        }

        if ($request->getParsedBodyParam('dvb_submit') != 'dvb_register') {
            return $response->write($view);
        }

        try {
            $firstname = $request->getParsedBodyParam('dvb_firstname');
            $lastname = $request->getParsedBodyParam('dvb_lastname');
            $email = $request->getParsedBodyParam('dvb_email');
            $password1 = $request->getParsedBodyParam('dvb_password1');
            $password2 = $request->getParsedBodyParam('dvb_password2');

            if (
                empty($firstname) ||
                empty($lastname) ||
                empty($email) ||
                empty($password1) ||
                empty($password2)
            ) {
                throw new \Exception("Please fill in all fields");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Enter a valid email");
            }

            if (!preg_match('/[^\da-zA-Z]+/', $password1)) {
                throw new \Exception("The password must contain at least one special character");
            }

            if (!preg_match('/[A-Z]+/', $password1)) {
                throw new \Exception("The password must contain at least one capital letter");
            }

            if (!preg_match('/[a-z]+/', $password1)) {
                throw new \Exception("The password must contain at least one normal letter");
            }

            if (strlen($password1) < 8) {
                throw new \Exception("The password must contain at least 8 characters");
            }

            if ($password1 != $password2) {
                throw new \Exception("Please make sure the two passwords matches");
            }

            $user = new User();

            $user
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setEmail($email)
                ->setPassword($password1);

            $user = $model->hashPassword($user);
            $user = $model->saveUser($user);

            $_SESSION['dvb_id_user'] = $user->getIdUser();
            $_SESSION['dvb_register'] = true;

            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_setup_url'));
        } catch (\Throwable $exception) {
            MessageHelper::getInstance()->addError($exception->getMessage());

            $response = $response->write(MessageHelper::getInstance()->getMessagesHTML());
            return $response->write($view);
        }
    }

    public function twoFactorSetup(Request $request, Response $response): Response {
	    if (DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_enabled') !== true) {
            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_register_url'));
        }

	    $model = DvbSlimAuthentication::getInstance()->getModel();
	    $view = DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_secret_template');

        try {
            $user = $model->getUser(($_SESSION['dvb_id_user'] ?? 0));

            $submit = $request->getParsedBodyParam('dvb_submit');
            $secret = $request->getParsedBodyParam('dvb_two_factor_secret');
            $verify = $request->getParsedBodyParam('dvb_verify');

            if (empty($submit) || empty($secret) || empty($verify)) {
                $user = $model->generateTwoFactorSecret($user);

                $view = sprintf($view, $user->getTwoFactorCode(), $model->getTwoFactorInstance()->getQRCodeImageAsDataUri(DvbSlimAuthentication::getInstance()->getConfigItem('name'), $user->getTwoFactorCode()));

                return $response->write($view);
            }

            if (!$model->getTwoFactorInstance()->verifyCode($secret, $verify)) {
                throw new \Exception("Verification code is not right");
            }

            $user->setTwoFactorCode($secret);
            $model->saveUser($user);

            if (isset($_SESSION['dvb_register']) && $_SESSION['dvb_register'] === true) {
                unset($_SESSION['dvb_register']);

                return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_register_url'));
            }

            $_SESSION['dvb_loggedin'] = true;

            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_login_url'));
        } catch (\Exception $exception) {
            MessageHelper::getInstance()->addError($exception->getMessage());

            return $response->write(MessageHelper::getInstance()->getMessagesHTML() . sprintf(DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_error_message'), DvbSlimAuthentication::getInstance()->getConfigItem('login_url')));
        }
    }

    public function logout(Request $request, Response $response): Response {
	    unset($_SESSION['dvb_id_user']);
	    unset($_SESSION['dvb_loggedin']);

	    session_destroy();

	    if (isset($_COOKIE['dvb_remember_me']) && !empty($_COOKIE['dvb_remember_me'])) {
	    	DvbSlimAuthentication::getInstance()->getRememberTokenModel()->revokeToken($_COOKIE['dvb_remember_me']);
	    }

	    setcookie('dvb_remember_me', "", (new \DateTime("-1 day"))->getTimestamp(), '/', $request->getUri()->getHost(), true, true);

	    return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('login_url'));
    }
}