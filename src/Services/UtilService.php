<?php

namespace App\Services;

use App\Domain\ChangePasswordData;
use App\Domain\RegisterData;

class UtilService
{
    public function checkPassword($registerData): array
    {
        $errors = [];
        if (empty($registerData->getPassword()) || empty($registerData->getPasswordConfirmation()) || empty($registerData->getEmail())) {
            $errors[] = "The fields cannot be empty";
        }
        if ($registerData->getPassword() != $registerData->getPasswordConfirmation()) {
            $errors[] = "Password confirmation does not match.";
        }
        if (strlen($registerData->getPassword()) < 6) {
            $errors[] = "Password must contain at least 6 characters.";
        }
        return $errors;
    }
}