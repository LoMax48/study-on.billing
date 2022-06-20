<?php

namespace App\Controller;

use App\Entity\User;
use App\Dto\UserDto;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1")
 */
class ApiAuthController extends AbstractController
{
    /**
     *  @OA\Post(
     *      path="/api/v1/auth",
     *      tags={"User"},
     *      summary="Авторизация пользователя",
     *      description="Авторизация пользователя",
     *      operationId="auth",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string",
     *                  example="user@intaro.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user123"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="Успешная авторизация",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="401",
     *          description="Не удалось авторизоваться",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Неверные данные"
     *              )
     *          )
     *      )
     *  )
     *  @Route("/auth", name="api_login", methods={"POST"})
     */
    public function auth(): void
    {
        // get JWT-token
    }

    /**
     *  @OA\Post(
     *      path="/api/v1/register",
     *      tags={"User"},
     *      summary="Регистрация пользователя",
     *      description="Регистрация нового пользователя",
     *      operationId="register",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="email",
     *                  type="string",
     *                  example="user@intaro.ru"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="string",
     *                  example="user123"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="201",
     *          description="Успешная авторизация",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="token",
     *                  type="string"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="400",
     *          description="Ошибка при валидации данных",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string"
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="403",
     *          description="Пользователь уже существует",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="500",
     *          description="Сервер не отвечает"
     *      )
     *  )
     *  @Route("/register", name="api_register", methods={"POST"})
     */
    public function register(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTTokenManager
    ): Response {
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $errors = $validator->validate($userDto);
        $data = [];
        $response = new Response();

        if (count($errors) > 0) {
            $data = ([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $errors,
            ]);

            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setContent($serializer->serialize($data, 'json'));
            $response->headers->add(['Content-Type' => 'application/json']);

            return $response;
        }

        if ($userRepository->findOneBy(['email' => $userDto->username])) {
            $data = [
                'code' => Response::HTTP_FORBIDDEN,
                'message' => 'User already exists',
            ];

            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        } else {
            $user = User::fromDto($userDto);
            $user->setPassword($hasher->hashPassword($user, $user->getPassword()));
            $user->setBalance(0);

            $entityManager->persist($user);
            $entityManager->flush();

            $data = [
                'token' => $JWTTokenManager->create($user),
                'roles' => $user->getRoles(),
            ];

            $response->setStatusCode(Response::HTTP_CREATED);
        }
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }
}
