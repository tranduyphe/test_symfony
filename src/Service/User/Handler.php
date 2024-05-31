<?php

namespace App\Service\User;

// use App\Dto\User\ForgotPasswordDto;
// use App\Dto\User\SettingsDto;
use App\Dto\User\SignUpDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class Handler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly PasswordHasherFactoryInterface $passwordHasher
    ) {
    }

    public function handle(SignUpDto $dto): UserInterface
    {
        if ($this->userRepository->hasByEmail($dto->getEmail())) {
            throw new \RuntimeException('User already exists.');
        }

        $user = (new User())
            ->setEmail($dto->getEmail())
            ->setFirstName($dto->getFirstName())
            ->setLastName($dto->getLastName())
            ->setPassword($this->passwordHasher->getPasswordHasher(User::class)->hash($dto->getPassword()))
            ->setRoles([User::ROLE_USER])
            ->setIsActive(true);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    // public function handleEditForm(SettingsDto $dto): void
    // {
    //     $user = $this->security->getUser();
    //     $emailDto = $dto->getEmail();

    //     if ($user->getEmail() !== $emailDto) {
    //         if ($this->userRepository->hasByEmail($emailDto)) {
    //             throw new \DomainException('User already exists.');
    //         }
    //     }

    //     //dd($user->getUpdatePassword());

    //     $user->setEmail($dto->getEmail())
    //         ->setFirstName($dto->getFirstName())
    //         ->setLastName($dto->getLastName());
    //     $this->em->persist($user);

    //     if($user->getUpdatePassword()) {
    //         if ($dto->getCurrentPassword() == null && $dto->getNewPassword() !== null) {
    //             throw new \DomainException('Wrong value for your current password.');
    //         }

    //         if ($dto->getCurrentPassword() !== null && $dto->getNewPassword() !== null) {
    //             if (password_verify($dto->getCurrentPassword(), $user->getPassword()) === true) {
    //                 $user->setPassword($this->passwordHasher->getPasswordHasher(User::class)->hash($dto->getNewPassword()));
    //                 $this->em->persist($user);
    //             }
    //             else {
    //                 throw new \DomainException('Wrong value for your current password.');
    //             }
    //         }
    //     }
    //     else {
    //         if ($dto->getNewPassword() !== null) { 
    //             $user->setPassword($this->passwordHasher->getPasswordHasher(User::class)->hash($dto->getNewPassword()));
    //             $user->setUpdatePassword(true);
    //             $this->em->persist($user);
    //         }
    //     }



    //     $this->em->flush();
    // }

    // public function handleForgotPasswordForm(ForgotPasswordDto $dto): string|null
    // {
    //     try {
    //         if (!$this->userRepository->existByEmail($dto->getEmail())) {
    //             throw new \Exception(sprintf('User %s does not exist', $dto->getEmail()));
    //         }
    //         $error = null;
    //     } catch (\Exception $e) {
    //         $error = $e->getMessage();
    //     } catch (\Throwable $e) {
    //         $this->logger->error(sprintf('message %s, code: %s', $e->getMessage(), $e->getCode()));
    //         $error = sprintf('Error, code: %s', $e->getCode());
    //     }

    //     return $error;
    // }

    public function handleCreateUser(SignUpDto $dto)
    {
        try {
            if ($this->userRepository->hasByEmail($dto->getEmail())) {
                return new JsonResponse(
                    ['message' => 'User already exists.', 'success' => false],
                    JsonResponse::HTTP_CONFLICT
                );
            } else {
                $provider = $dto->getProvider() ? $dto->getProvider() : 'normal';
                $user = (new User())
                    ->setEmail($dto->getEmail())
                    ->setFirstName($dto->getFirstName())
                    ->setLastName($dto->getLastName())
                    ->setProvider($provider)
                    ->setPassword($this->passwordHasher->getPasswordHasher(User::class)->hash($dto->getPassword()))
                    ->setRoles([User::ROLE_USER])
                    ->setIsActive(true);

                $this->em->persist($user);
                $this->em->flush();

                return new JsonResponse(
                    ['message' => 'User registered successfully', 'success' => true, 'data' => $user],
                    JsonResponse::HTTP_CREATED
                );
            }
        } catch (\Exception $e) {
            return new JsonResponse(
                ['message' => $e->getMessage(), 'success' => false],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function handleUpdatedUser(SignUpDto $dto)
    {
        $results = [];
        try {
            if (!$this->userRepository->hasByEmail($dto->getEmail())) {
                throw new \RuntimeException(sprintf('User %s does not exist', $dto->getEmail()));
                $results['message'] = 'User %s does not exist';
                $results['success'] = false;
            } else {
                $activeProPartner = $dto->getActiveProPartner() ? $dto->getActiveProPartner() : false;
                $user = $this->userRepository->findOneBy(['email' => $dto->getEmail()]);
                $user->setEmail($dto->getEmail())
                    ->setFirstName($dto->getFirstName())
                    ->setLastName($dto->getLastName())
                    ->setActiveProPartner($activeProPartner);
                $this->em->persist($user);
                $this->em->flush();
                $results['message'] = 'User updated successfully';
                $results['success'] = true;
                $results['data'] = $user;
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['message'] = $e->getMessage();
        }
        return $results;
    }

}