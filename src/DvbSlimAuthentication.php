<?php

namespace DvbSlimAuthentication;

use DvbSlimAuthentication\Middleware\UserMiddleware;
use DvbSlimAuthentication\Models\RememberTokenModel;
use DvbSlimAuthentication\Models\UserModel;

class DvbSlimAuthentication {
	/**
	 * @var \DvbSlimAuthentication\DvbSlimAuthentication
	 */
	private static $instance;

	/**
	 * @return \DvbSlimAuthentication\DvbSlimAuthentication
	 */
	public static function getInstance(array $config = array()): self {
		if (!self::$instance) {
			self::$instance = new self($config);
		}

		return self::$instance;
	}

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var \PDO
	 */
	private $pdo;

	/**
	 * @var
	 */
	private $middleware;

	/**
	 * @var \DvbSlimAuthentication\Models\UserModel
	 */
	private $user_model;

	/**
	 * @var \DvbSlimAuthentication\Models\RememberTokenModel
	 */
	private $remember_token_model;

	/**
	 * DvbSlimAuthentication constructor.
	 *
	 * @param array $config
	 */
	private function __construct(array $config = array()) {
		// Start session if not already
		if (!session_id()) {
			session_start();
		}

		$default_config = array(
			"table"                       => "users",
			"two_factor_enabled"          => true,
			"name"                        => "Authentication",
			"enable_registration"         => true,
			"login_template"              => '<form method="post"><input type="email" name="dvb_email" placeholder="Email" /><input type="password" name="dvb_password" placeholder="Password" /><input type="text" name="dvb_two_factory" placeholder="Two factory code" /><button type="submit" name="dvb_submit" value="dvb_login">Login</button></form>',
			"register_template"           => '<form method="post"><input type="email" name="dvb_email" placeholder="Email" /><input type="password" name="dvb_password1" placeholder="Password" /><input type="password" name="dvb_password2" placeholder="Password verify" /><input type="text" name="dvb_firstname" placeholder="Firstname" /><input type="text" name="dvb_lastname" placeholder="Lastname" /><button type="submit" name="dvb_submit" value="dvb_register">Register</button></form>',
			"two_factor_secret_template"  => '<form method="post"><p>Scan the QR-code with the Google Authenticator app and enter the verification code below</p><input type="hidden" name="dvb_two_factor_secret" value="%s" /><img src="%s" /><input type="text" name="dvb_verify" placeholder="Verification code" /><button type="submit" name="dvb_submit" value="dvb_two_factor_verify">Verify</button></form>',
			"two_factor_error_message"    => '<p><a href="%s">Try again</a></p>',
			"after_login_url"             => '/',
            "after_register_url" => '/login',
            "login_url" => "/login",
            "two_factor_setup_url" => '/two-factor-setup'
		);

		foreach ($default_config as $key => $value) {
			if (!isset($config[$key])) {
				$config[$key] = $value;
			}
		}

		$this->config = $config;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	public function getConfigItem(string $key) {
		if (!isset($this->config[$key])) {
			return null;
		}

		return $this->config[$key];
	}

	/**
	 * @param \PDO $pdo
	 */
	public function setPdo(\PDO $pdo) {
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$this->pdo = $pdo;
	}

	/**
	 * @return \PDO
	 * @throws \Exception
	 */
	public function getPdo(): \PDO {
		if (!$this->pdo) {
			throw new \Exception("Supply a PDO instance via the setPdo method");
		}

		return $this->pdo;
	}

	/**
	 * @return \DvbSlimAuthentication\Middleware\UserMiddleware
	 */
	public function getMiddleware(): UserMiddleware {
		if (!$this->middleware) {
			$this->middleware = new UserMiddleware();
		}

		return $this->middleware;
	}

	/**
	 * @return \DvbSlimAuthentication\Models\UserModel
	 * @throws \Throwable
	 */
	public function getModel(): UserModel {
		try {
			if (!$this->user_model) {
				$this->user_model = new UserModel($this->getPdo());
			}

			return $this->user_model;
		} catch (\Throwable $exception) {
			throw $exception;
		}
	}

	/**
	 * @return \DvbSlimAuthentication\Models\RememberTokenModel
	 * @throws \Throwable
	 */
	public function getRememberTokenModel(): RememberTokenModel {
		try {
			if (!$this->remember_token_model) {
				$this->remember_token_model = new RememberTokenModel($this->getPdo());
			}

			return $this->remember_token_model;
		} catch (\Throwable $exception) {
			throw $exception;
		}
	}
}