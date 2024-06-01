<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use App\Dto\User\SignUpDto;
use App\Service\User\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class AuthController extends AbstractController
{
    private $passwordEncoder;
    private $JWTManager;
    private $entityManager;
    private $jwtEncoder;
    private $userRepo;
    public function __construct(
        UserPasswordHasherInterface $passwordEncoder,
        private readonly Handler $handler,
        JWTTokenManagerInterface $JWTManager,
        EntityManagerInterface $entityManager,
        JWTEncoderInterface $jwtEncoder
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->JWTManager = $JWTManager;
        $this->entityManager = $entityManager;
        $this->jwtEncoder = $jwtEncoder;
        $this->userRepo = $this->entityManager->getRepository(User::class);
    }

    public function login(Request $request)
    {
        try {

            $data = json_decode($request->getContent(), true);
            $username = $data['email'];
            $password = $data['password'];

            $user = $this->userRepo->findOneBy(['email' => $username]);

            if (!$user || !$this->passwordEncoder->isPasswordValid($user, $password)) {
                return new JsonResponse(['message' => 'Email/password is wrong', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);
            }

            $token = $this->JWTManager->create($user);

            return new JsonResponse(['message' => 'Login successful.', 'token' => $token, 'success' => true], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update(Request $request)
    {
        try {
            $token = str_replace('Bearer ', '', $request->headers->get('Authorization'));
            $results = $this->jwtEncoder->decode($token);
            if (count($results)) {
                $username = $results['username'];
                $user = $this->userRepo->findOneBy(['email' => $username]);
                if ($user) {
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
                    return new JsonResponse(['message' => 'User updated successfully', 'success' => true, 'data' => $user->toArray()], JsonResponse::HTTP_OK);

                }
            } else {
                return new JsonResponse(['message' => 'User not found', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);
            }


        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    public function register(Request $request, ValidatorInterface $validator): JsonResponse
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
    public function user(Request $request): JsonResponse
    {
        try {
            $token = str_replace('Bearer ', '', $request->headers->get('Authorization'));
            $data = $this->jwtEncoder->decode($token);
            $user = $this->userRepo->findOneBy(['email' => $data['username']]);
            if (!$user)
                return new JsonResponse(['message' => 'User not found', 'success' => false], JsonResponse::HTTP_BAD_REQUEST);

            return new JsonResponse(['message' => 'User information retrieved successfully', 'success' => true, 'data' => $user->toArray()], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage(), 'success' => false], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}