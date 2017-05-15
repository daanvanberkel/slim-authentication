<?php
namespace DvbSlimAuthentication\Controllers;

use DvbPhpMessages\MessageHelper;
use DvbSlimAuthentication\DvbSlimAuthentication;
use DvbSlimAuthentication\Entities\User;
use Slim\Http\Request;
use Slim\Http\Response;

class UserController {
	public function login(Request $request, Response $response): Response {
		$view = DvbSlimAuthentication::getInstance()->getConfigItem('login_template');
		$model = DvbSlimAuthentication::getInstance()->getModel();

		if (empty($request->getParsedBodyParam('dvb_submit'))) {
			return $response->write($view);
		}

		switch($request->getParsedBodyParam('dvb_submit')) {
			case 'dvb_login':
				$email = $request->getParsedBodyParam('dvb_email');
				$password = $request->getParsedBodyParam('dvb_password');

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
						$user = $model->generateTwoFactorSecret($user);

						$two_factor_view = sprintf(DvbSlimAuthentication::getInstance()->getConfigItem('two_factory_secret_template'), $model->getTwoFactorInstance()->getQRCodeImageAsDataUri($user->getName(), $user->getTwoFactorCode()));

						return $response->write($two_factor_view);
					}

					if (DvbSlimAuthentication::getInstance()->getConfigItem('two_factor_enabled')) {
						$two_factor_view = DvbSlimAuthentication::getInstance()->getConfigItem('two_factory_template');

						return $response->write($two_factor_view);
					}

					$_SESSION['dvb_loggedin'] = true;

					return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_login_url'));
				} catch (\Throwable $exception) {
					MessageHelper::getInstance()->addError($exception->getMessage());

					$response = $response->write(MessageHelper::getInstance()->getMessagesHTML());
					return $response->write($view);
				}
				break;

			case 'dvb_two_factor_verify':
				try {
					if (!isset($_SESSION['dvb_id_user']) || empty($_SESSION['dvb_id_user'])) {
						throw new \Exception("User not found");
					}

					$user = $model->getUser((int) $_SESSION['dvb_id_user']);

					$verify_code = $request->getParsedBodyParam('dvb_verify');

					if (empty($verify_code)) {
						throw new \Exception("Enter a verification code");
					}

					if (!$model->getTwoFactorInstance()->verifyCode($user->getTwoFactorCode(), $verify_code)) {
						throw new \Exception("Enter a correct verification code");
					}

					$_SESSION['dvb_loggedin'] = true;

					return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_login_url'));
				} catch (\Throwable $exception) {
					MessageHelper::getInstance()->addError($exception->getMessage());

					$response = $response->write(MessageHelper::getInstance()->getMessagesHTML());
					return $response->write($view);
				}
				break;

			case 'dvb_two_factor':
				try {
					if (!isset($_SESSION['dvb_id_user']) || empty($_SESSION['dvb_id_user'])) {
						throw new \Exception("User not found");
					}

					$user = $model->getUser((int) $_SESSION['dvb_id_user']);

					if (!$model->getTwoFactorInstance()->verifyCode($user->getTwoFactorCode(), $request->getParsedBodyParam('dvb_two_factory'))) {
					    throw new \Exception("Enter a valid two factor code");
                    }

					$_SESSION['dvb_loggedin'] = true;

					return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_login_url'));
				} catch (\Throwable $exception) {
					MessageHelper::getInstance()->addError($exception->getMessage());

					$response = $response->write(MessageHelper::getInstance()->getMessagesHTML());
					return $response->write($view);
				}
				break;

			default:
				return $response->write("Action unknown");
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
                throw new \Exception("The paassword must contain at least 8 characters");
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

            $model->saveUser($user);

            return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('after_register_url'));
        } catch (\Throwable $exception) {
            MessageHelper::getInstance()->addError($exception->getMessage());

            $response = $response->write(MessageHelper::getInstance()->getMessagesHTML());
            return $response->write($view);
        }
    }

    public function logout(Request $request, Response $response): Response {
	    unset($_SESSION['dvb_id_user']);
	    unset($_SESSION['dvb_loggedin']);

	    session_destroy();

	    return $response->withRedirect(DvbSlimAuthentication::getInstance()->getConfigItem('login_url'));
    }
}