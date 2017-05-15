<?php

namespace DvbSlimAuthentication;

use DvbSlimAuthentication\Models\UserModel;

class DvbSlimAuthentication {
	/**
	 * @var \DvbSlimAuthentication\DvbSlimAuthentication
	 */
	private static $instance;

	/**
	 * @return \DvbSlimAuthentication\DvbSlimAuthentication
	 */
	public static function getInstance(): self {
		if (!self::$instance) {
			self::$instance = new self();
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
	private $model;

	/**
	 * DvbSlimAuthentication constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		// Start session if not already
		if (!session_id()) {
			session_start();
		}

		$default_config = array(
			"table"                       => "users",
			"two_factor_enabled"          => true,
			"name"                        => "Authentication",
			"enable_registration"         => true,
			"login_template"              => '<form method="post"><input type="email" name="dvb_email" placeholder="Email" /><input type="password" name="dvb_password" placeholder="Password" /><button type="submit" name="dvb_submit" value="dvb_login">Login</button></form>',
			"register_template"           => '<form method="post"><input type="email" name="dvb_email" placeholder="Email" /><input type="password" name="dvb_password1" placeholder="Password" /><input type="password" name="dvb_password2" placeholder="Password verify" /><input type="text" name="dvb_firstname" placeholder="Firstname" /><input type="text" name="dvb_lastname" placeholder="Lastname" /><button type="submit" name="dvb_submit" value="dvb_register">Register</button></form>',
			"two_factory_secret_template" => '<form method="post"><p>Scan the QR-code with the Google Authenticator app and enter the verification code below</p><img src="%s" /><input type="text" name="dvb_verify" placeholder="Verification code" /><button type="submit" name="dvb_submit" value="dvb_two_factor_verify">Verify</button></form>',
			"two_factory_template"        => '<form method="post"><input type="text" name="dvb_two_factory" placeholder="Two factory code" /><button type="submit" name="dvb_submit" value="dvb_two_factor">Login</button></form>',
			"after_login_url"             => '/',
            "after_register_url" => '/login',
            "login_url" => "/login"
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
	 * @return
	 */
	public function getMiddleware() {
		if (!$this->middleware) {
			// TODO: Create new middleware instance
		}

		return $this->middleware;
	}

	/**
	 * @return \DvbSlimAuthentication\Models\UserModel
	 * @throws \Throwable
	 */
	public function getModel(): UserModel {
		try {
			if (!$this->model) {
				$this->model = new UserModel($this->getPdo());
			}

			return $this->model;
		} catch (\Throwable $exception) {
			throw $exception;
		}
	}
}