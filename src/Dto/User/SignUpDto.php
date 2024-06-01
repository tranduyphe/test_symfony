<?php

declare(strict_types=1);

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SignUpDto
{
    
    #[Email]
    #[NotBlank(normalizer: 'trim')]
    #[Length(max: 30, maxMessage: 'The maximum length is 30 characters.')]
    private string $email;

    #[NotBlank(normalizer: 'trim')]
    #[Length(max: 50, maxMessage: 'The maximum length is 50 characters.')]
    private string $firstName;

    #[NotBlank(normalizer: 'trim')]
    #[Length(max: 50, maxMessage: 'The maximum length is 50 characters.')]
    private string $lastName;

    #[NotBlank]
    private string $password;

    private bool $updatePassword;
    private ?string $provider = null;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getUpdatePassword(): bool
    {
        return $this->updatePassword;
    }

    public function setUpdatePassword(bool $updatePassword): self
    {
        $this->updatePassword = $updatePassword;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}