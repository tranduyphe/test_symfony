<?php

namespace App\Service\User;

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

    public function handleCreateUser(SignUpDto $dto)
    {
        try {
            if ($this->userRepository->hasByEmail($dto->getEmail())) {
                return new JsonResponse(
                    ['message' => 'User already exists!', 'success' => false],
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
                    ['message' => 'User registered successfully!', 'success' => true, 'data' => $user],
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
}