<?php

namespace DvbSlimAuthentication\Models;

use DvbSlimAuthentication\DvbSlimAuthentication;
use DvbSlimAuthentication\Entities\User;
use RobThree\Auth\TwoFactorAuth;

class UserModel {
	private $pdo;
	private $two_factory;

	public function __construct(\PDO $pdo) {
		$this->pdo = $pdo;
	}

	public function getTwoFactorInstance(): TwoFactorAuth {
		if (!$this->two_factory) {
			$this->two_factory = new TwoFactorAuth(DvbSlimAuthentication::getInstance()->getConfigItem('name'));
		}

		return $this->two_factory;
	}

	public function getUser(int $id_user): User {
		$query = "
			SELECT
				id_user,
				firstname,
				lastname,
				email,
				password,
				secret,
				two_factor_code
			FROM
				" . DvbSlimAuthentication::getInstance()->getConfigItem('table') . "
			WHERE
				id_user = :id_user
			LIMIT 1
		";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
				":id_user" => $id_user
			));

			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (empty($result)) {
				throw new \Exception("User not found");
			}

			return $this->parseUser($result);
		} catch (\Throwable $exception) {
			throw $exception;
		}
	}

	public function getUserByEmail(string $email): User {
		$query = "
			SELECT
				id_user,
				firstname,
				lastname,
				email,
				password,
				secret,
				two_factor_code
			FROM
				" . DvbSlimAuthentication::getInstance()->getConfigItem('table') . "
			WHERE
				email = :email
			LIMIT 1
		";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
				":email" => $email
			));

			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (empty($result)) {
				throw new \Exception("User not found");
			}

			return $this->parseUser($result);
		} catch (\Throwable $exception) {
			throw $exception;
		}
	}

	public function saveUser(User $user): User {
		if (empty($user->getIdUser())) {
		    try {
		        $user = $this->getUserByEmail($user->getEmail());

		        return $user;
            } catch (\Exception $exception) {
		        // User not found, continue
            }

			$query = "
				INSERT INTO
					" . DvbSlimAuthentication::getInstance()->getConfigItem('table') . " (
						firstname,
						lastname,
						email,
						password,
						secret,
						two_factor_code
					)
				VALUES (
					:firstname,
					:lastname,
					:email,
					:password,
					:secret,
					:two_factor_code
				)
			";

			$args = array(
				":firstname" => $user->getFirstname(),
				":lastname" => $user->getLastname(),
				":email" => $user->getEmail(),
				":password" => $user->getPassword(),
				":secret" => $user->getSecret(),
				":two_factor_code" => $user->getTwoFactorCode()
			);
		} else {
			$query = "
				UPDATE 
					" . DvbSlimAuthentication::getInstance()->getConfigItem('table') . "
				SET 
					firstname = :firstname,
					lastname = :lastname,
					email = :email,
					password = :password,
					secret = :secret,
					two_factor_code = :two_factor_code
				WHERE
					id_user = :id_user
			";

			$args = array(
				":firstname" => $user->getFirstname(),
				":lastname" => $user->getLastname(),
				":email" => $user->getEmail(),
				":password" => $user->getPassword(),
				":secret" => $user->getSecret(),
				":two_factor_code" => $user->getTwoFactorCode(),
				":id_user" => $user->getIdUser()
			);
		}

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute($args);

			$user->setIdUser((int) $this->pdo->lastInsertId());

			return $user;
		} catch (\Throwable $exception) {
			throw $exception;
		}
	}

	public function deleteUser(User $user) {
		$query = "
			DELETE FROM
				" . DvbSlimAuthentication::getInstance()->getConfigItem('table') . "
			WHERE
				id_user = :id_user
		";

		$stmt = $this->pdo->prepare($query);
		$stmt->execute(array(":id_user" => $user->getIdUser()));
	}

	public function generateSecret(User $user): User {
		$user->setSecret(bin2hex(openssl_random_pseudo_bytes(10)));

		return $user;
	}

	public function hashPassword(User $user): User {
		if (empty($user->getPassword())) {
			throw new \Exception("Password cannot be empty");
		}

		if (empty($user->getSecret())) {
			$user = $this->generateSecret($user);
		}

		$user->setPassword(password_hash($user->getPassword() . $user->getSecret(), PASSWORD_DEFAULT));

		return $user;
	}

	public function generateTwoFactorSecret(User $user): User {
		$user->setTwoFactorCode($this->getTwoFactorInstance()->createSecret(160));

		return $user;
	}

	public function validatePassword(User $user, string $password): bool {
		return password_verify($password . $user->getSecret(), $user->getPassword());
	}

	private function parseUser(\stdClass $class): User {
		if (
			!isset($class->id_user) ||
			!isset($class->firstname) ||
			!isset($class->lastname) ||
			!isset($class->email) ||
			!isset($class->password) ||
			!isset($class->secret) ||
			!isset($class->two_factor_code)
		) {
			throw new \Exception("User is missing data");
		}

		$user = new User();

		$user
			->setIdUser((int) $class->id_user)
			->setFirstname($class->firstname)
			->setLastname($class->lastname)
			->setEmail($class->email)
			->setPassword($class->password)
			->setSecret($class->secret)
			->setTwoFactorCode($class->two_factor_code);

		return $user;
	}
}