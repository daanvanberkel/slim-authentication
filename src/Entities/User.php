<?php
namespace DvbSlimAuthentication\Entities;

class User {
	/**
	 * @var int
	 */
	private $id_user = 0;

	/**
	 * @var string
	 */
	private $firstname = "";

	/**
	 * @var string
	 */
	private $lastname = "";

	/**
	 * @var string
	 */
	private $email = "";

	/**
	 * @var string
	 */
	private $password = "";

	/**
	 * @var string
	 */
	private $secret = "";

	/**
	 * @var string
	 */
	private $two_factor_code = "";

	/**
	 * @return int
	 */
	public function getIdUser(): int {
		return $this->id_user;
	}

	/**
	 * @param int $id_user
	 *
	 * @return User
	 */
	public function setIdUser(int $id_user): User {
		$this->id_user = $id_user;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFirstname(): string {
		return $this->firstname;
	}

	/**
	 * @param string $firstname
	 *
	 * @return User
	 */
	public function setFirstname(string $firstname): User {
		$this->firstname = trim($firstname);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLastname(): string {
		return $this->lastname;
	}

	/**
	 * @param string $lastname
	 *
	 * @return User
	 */
	public function setLastname(string $lastname): User {
		$this->lastname = trim($lastname);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEmail(): string {
		return $this->email;
	}

	/**
	 * @param string $email
	 *
	 * @return User
	 */
	public function setEmail(string $email): User {
		$this->email = trim($email);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * @param string $password
	 *
	 * @return User
	 */
	public function setPassword(string $password): User {
		$this->password = $password;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSecret(): string {
		return $this->secret;
	}

	/**
	 * @param string $secret
	 *
	 * @return User
	 */
	public function setSecret(string $secret): User {
		$this->secret = $secret;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTwoFactorCode(): string {
		return $this->two_factor_code;
	}

	/**
	 * @param string $two_factor_code
	 *
	 * @return User
	 */
	public function setTwoFactorCode(string $two_factor_code): User {
		$this->two_factor_code = $two_factor_code;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->getFirstname() . ' ' . $this->getLastname();
	}
}