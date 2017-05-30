<?php
namespace DvbSlimAuthentication\Entities;

class RememberToken {
	/**
	 * @var int
	 */
	private $id_remember_token = 0;

	/**
	 * @var int
	 */
	private $id_user = 0;

	/**
	 * @var string
	 */
	private $token = "";

	/**
	 * @var null|\DateTime
	 */
	private $expire_date = null;

	/**
	 * @var bool
	 */
	private $revoked = false;

	/**
	 * @return int
	 */
	public function getIdRememberToken(): int {
		return $this->id_remember_token;
	}

	/**
	 * @param int $id_remember_token
	 *
	 * @return RememberToken
	 */
	public function setIdRememberToken(int $id_remember_token): RememberToken {
		$this->id_remember_token = $id_remember_token;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getIdUser(): int {
		return $this->id_user;
	}

	/**
	 * @param int $id_user
	 *
	 * @return RememberToken
	 */
	public function setIdUser(int $id_user): RememberToken {
		$this->id_user = $id_user;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @param string $token
	 *
	 * @return RememberToken
	 */
	public function setToken(string $token): RememberToken {
		$this->token = $token;

		return $this;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getExpireDate() {
		return $this->expire_date;
	}

	/**
	 * @param \DateTime|null $expire_date
	 *
	 * @return RememberToken
	 */
	public function setExpireDate($expire_date) {
		$this->expire_date = $expire_date;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isRevoked(): bool {
		return $this->revoked;
	}

	/**
	 * @param bool $revoked
	 *
	 * @return RememberToken
	 */
	public function setRevoked(bool $revoked): RememberToken {
		$this->revoked = $revoked;

		return $this;
	}
}
