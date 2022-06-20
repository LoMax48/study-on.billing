<?php

namespace App\Controller;

use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/users")
 */
class ApiUserController extends AbstractController
{
    /**
     *  @OA\Get(
     *      path="/api/v1/users/current",
     *      tags={"User"},
     *      summary="Информация о пользователе",
     *      description="Получение информации о пользователе",
     *      operationId="current",
     *      @OA\Response(
     *          response="200",
     *          description="Успешно",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="username",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="roles",
     *                  type="array",
     *                  @OA\Items(
     *                      type="string"
     *                  )
     *              ),
     *              @OA\Property(
     *                  property="balance",
     *                  type="number",
     *                  format="float"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response="401",
     *          description="Не удалось получить данные",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="code",
     *                  type="string",
     *                  example="401"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="JWT-токен не найден"
     *              )
     *          )
     *      )
     *  )
     *  @Security(name="Bearer")
     *  @Route("/current", name="api_current_user", methods={"GET"})
     */
    public function getCurrentUser(SerializerInterface $serializer, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $response = new Response();

        if (!$user) {
            $data = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'message' => 'User does not exist',
            ];
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
        } else {
            $user = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

            $data = [
                'username' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'balance' => $user->getBalance(),
            ];

            $response->setStatusCode(Response::HTTP_OK);
        }
        $response->setContent($serializer->serialize($data, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }
}
