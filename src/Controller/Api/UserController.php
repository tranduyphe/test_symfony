<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use App\Dto\User\SignUpDto;
use App\Service\User\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private $passwordEncoder;
    private $userRepo;
    private $entityManager;
    public function __construct(
        UserPasswordHasherInterface $passwordEncoder,
        private readonly Handler $handler,
        EntityManagerInterface $entityManager,
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepo = $entityManager->getRepository(User::class);
    }

    public function index(): JsonResponse
    {
        try {
            $users = $this->userRepo->findAllUsers();
            if (!$users) {
                return $this->json(['message' => 'No users found!', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);
            }
            $listUsers = [];
            foreach ($users as $user) {
                $listUsers[] = $user->toArray();
            }
            return $this->json(['message' => 'Successfully retrieved user list!', 'data' => $listUsers], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $dto = new SignUpDto();
            $dto->setEmail($data['email']);
            $dto->setFirstName($data['first_name']);
            $dto->setLastName($data['last_name']);
            $dto->setPassword($data['password']);

            $errors = $validator->validate($dto);
            if (count($errors) > 0) {

                $errorsString = '';
                foreach ($errors as $violation) {
                    $errorsString .= $violation->getMessage() . ' ';
                }
                return new JsonResponse(['message' => $errorsString, 'success' => false], JsonResponse::HTTP_BAD_REQUEST);

            }

            $response = $this->handler->handleCreateUser($dto);

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('id');

            if (!$userId) {
                return $this->json(['message' => 'invalid Id!', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user = $this->userRepo->find($userId);

            if (!$user) {
                return $this->json(['message' => 'User not found!', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);
            }

            return $this->json(['message' => 'User information retrieved successfully!', 'success' => true, 'data' => $user->toArray()], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('id');
            if (!$userId)
                return $this->json(['message' => 'invalid Id!', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);

            $user = $this->userRepo->find($userId);

            if (!$user)
                return $this->json(['message' => 'User not found!', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);

            $data = json_decode($request->getContent(), true);
            $password = $data['password'] ?? '';
            $first_name = $data['first_name'] ?? '';
            $last_name = $data['last_name'] ?? '';

            if (!empty($first_name)) {
                $user->setFirstName($first_name);
            }

            if (!empty($last_name)) {
                $user->setLastName($last_name);
            }

            if (!empty($password)) {
                $encodedPassword = $this->passwordEncoder->hashPassword($user, $password);
                $user->setPassword($encodedPassword);
            }

            $this->entityManager->flush();
            return new JsonResponse(['message' => 'User updated successfully!', 'success' => true, 'data' => $user->toArray()], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(request $request): JsonResponse
    {
        try {
            $userId = $request->get('id');

            if (!$userId)
                return $this->json(['message' => 'invalid Id!', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);

            $user = $this->userRepo->find($userId);

            if (!$user)
                return $this->json(['message' => 'User not found!', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);

            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return $this->json(['message' => 'User deleted successfully!', 'success' => true], JsonResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}