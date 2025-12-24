<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CustomerRegistrationDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastName;
}
