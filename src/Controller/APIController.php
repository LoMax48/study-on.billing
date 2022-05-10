<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\UserDTO;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1")
 */
class APIController extends AbstractController
{
    /**
     * @Route("/auth", name="api_login", methods={"POST"})
     */
    public function auth(): void
    {
        // get JWT-token
    }
}
