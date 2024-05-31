<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordHasherInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $users = $entityManager->getRepository(User::class)->findAll();
            return $this->json(['message' => 'Lấy danh sách người dùng thành công', 'data' => $users], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $user = new User();
            $user->setUsername($data['username']);
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, $data['password'])
            );

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(['message' => 'Tạo người dùng thành công', 'data' => $user], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(User $user): JsonResponse
    {
        try {
            return $this->json(['message' => 'Lấy thông tin người dùng thành công', 'data' => $user], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $user->setUsername($data['username']);
            $user->setPassword(
                $this->passwordEncoder->hashPassword($user, $data['password'])
            );

            $entityManager->flush();

            return $this->json(['message' => 'Cập nhật người dùng thành công', 'data' => $user], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($user);
            $entityManager->flush();

            return $this->json(['message' => 'Xóa người dùng thành công'], JsonResponse::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}