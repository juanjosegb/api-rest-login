<?php


namespace App\Domain;


class ChangePasswordData
{
    private $password;
    private $passwordConfirmation;
    private $token;
    private $userId;

    public function __construct(string $password, string $passwordConfirmation, string $token, string $userId)
    {
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
        $this->token = $token;
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getPasswordConfirmation(): string
    {
        return $this->passwordConfirmation;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }
}