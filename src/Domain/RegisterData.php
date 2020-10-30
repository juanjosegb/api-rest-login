<?php


namespace App\Domain;


class RegisterData
{
    private $email;
    private $password;
    private $passwordConfirmation;

    public function __construct(string $email, string $password, string $passwordConfirmation)
    {
        $this->email = $email;
        $this->password = $password;
        $this->passwordConfirmation = $passwordConfirmation;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
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
}