<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/users")
 */
class ApiUserController extends AbstractController
{
    /**
     * @Route("/current", name="api_current_user")
     */
    public function getCurrentUser(SerializerInterface $serializer, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $response = new Response();

        if (!$user) {
            $data = [
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'User does not exist',
            ];
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
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
