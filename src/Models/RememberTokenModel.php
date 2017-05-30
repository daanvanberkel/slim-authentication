<?php
namespace DvbSlimAuthentication\Models;

use DvbSlimAuthentication\DvbSlimAuthentication;
use DvbSlimAuthentication\Entities\RememberToken;
use DvbSlimAuthentication\Entities\User;

class RememberTokenModel {
	private $pdo;

	public function __construct(\PDO $pdo) {
		$this->pdo = $pdo;
	}

	public function checkRememberToken(User $user, string $token): bool {
		$query = "
			SELECT
				id_remember_token,
				id_user,
				revoked,
				expire_date,
				token
			FROM
				remember_tokens
			WHERE
				id_user = :id_user AND 
				revoked = 0 AND 
				token = :token
			LIMIT 1
		";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
				":id_user" => $user->getIdUser(),
				":token" => $token
			));

			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (empty($result)) {
				throw new \Exception("Remember token not found");
			}

			$expire_date = \DateTime::createFromFormat('Y-m-d H:i:s', $result->expire_date);

			if (empty($expire_date)) {
				throw new \Exception("Remember token has expired");
			}

			if ($expire_date < (new \DateTime())) {
				throw new \Exception("Remember token has expired");
			}

			return true;
		} catch (\Exception $exception) {
			throw $exception;
		}
	}

	public function saveNewToken(User $user): RememberToken {
		$token = $this->generateUniqueToken();
		$expire_date = new \DateTime("+1 month");

		$query = "
			INSERT INTO 
				remember_tokens (
					id_user,
					token,
					expire_date
				)
			VALUES (
				:id_user,
				:token,
				:expire_date
			)
		";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
				":id_user" => $user->getIdUser(),
				":token" => $token,
				":expire_date" => $expire_date->format('Y-m-d H:i:s')
			));

			$remember_token = new RememberToken();

			$remember_token
				->setIdUser($user->getIdUser())
				->setExpireDate($expire_date)
				->setIdRememberToken((int) $this->pdo->lastInsertId())
				->setToken($token);

			return $remember_token;
		} catch (\Exception $exception) {
			throw $exception;
		}
	}

	public function revokeToken(string $token) {
		$query = "
			UPDATE
				remember_tokens
			SET
				revoked = 1
			WHERE
				token = :token
		";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
				":token" => $token
			));
		} catch (\Exception $exception) {
			throw $exception;
		}
	}

	public function getUserByToken(string $token): User {
		$query = "
			SELECT
				id_user
			FROM
				remember_tokens
			WHERE
				token = :token
		";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
				":token" => $token
			));

			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (empty($result)) {
				throw new \Exception("User not found");
			}

			$user = DvbSlimAuthentication::getInstance()->getModel()->getUser((int) $result->id_user);

			if (!$this->checkRememberToken($user, $token)) {
				throw new \Exception("Remember token has expired");
			}

			return $user;
		} catch (\Exception $exception) {
			throw $exception;
		}
	}

	public function getToken(string $token): RememberToken {
		$query = "
			SELECT
				id_remember_token,
				id_user,
				token,
				revoked,
				expire_date
			FROM
				remember_tokens
			WHERE
				token = :token
		";

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
				":token" => $token
			));

			$result = $stmt->fetch(\PDO::FETCH_OBJ);

			if (empty($result)) {
				throw new \Exception("Token not found");
			}

			$token = new RememberToken();

			$token
				->setIdRememberToken($result->id_remember_token)
				->setIdUser($result->id_user)
				->setToken($result->token)
				->setRevoked($result->revoked)
				->setExpireDate($result->expire_date);

			return $token;
		} catch (\Exception $exception) {
			throw $exception;
		}
	}

	public function renewToken(User $user, string $token): RememberToken {
		try {
			$str_token = $token;
			$token = $this->getToken($token);

			if ($token->getIdUser() != $user->getIdUser()) {
				throw new \Exception("User mismatch");
			}

			if ($token->getExpireDate() > (new \DateTime("-1 week"))) {
				$this->revokeToken($str_token);
				return $this->saveNewToken($user);
			}

			return $token;
		} catch (\Exception $exception) {
			throw $exception;
		}
	}

	private function generateUniqueToken(): string {
		$token = bin2hex(openssl_random_pseudo_bytes(20));

		$query = "
			SELECT
				token
			FROM
				remember_tokens
			WHERE
				token = :token
		";

		$stmt = $this->pdo->prepare($query);
		$stmt->execute(array(
			":token" => $token
		));

		$result = $stmt->fetch(\PDO::FETCH_OBJ);

		if (empty($result)) {
			return $token;
		}

		return $this->generateUniqueToken();
	}
}