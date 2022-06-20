<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use App\Dto\UserDto;
use App\Entity\User;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiUserControllerTest extends AbstractTest
{
    private SerializerInterface $serializer;
    private string $startingPath = '/api/v1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    protected function getFixtures(): array
    {
        return [UserFixtures::class];
    }

    private function getToken(array $user): string
    {
        $client = self::getClient();
        $client->jsonRequest(
            'POST',
            $this->startingPath . '/auth',
            $user
        );

        return json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['token'];
    }

    public function testGetCurrentUserWithAuth(): void
    {
        $user = [
            'username' => 'user@mail.ru',
            'password' => 'user123'
        ];
        $token = $this->getToken($user);

        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $client = self::getClient();
        $client->jsonRequest(
            'GET',
            $this->startingPath . '/users/current',
            $user,
            $headers
        );

        $this->assertResponseOk();

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $userDto = $this->serializer->deserialize(
            $client->getResponse()->getContent(),
            UserDto::class,
            'json'
        );

        $entityManager = self::getEntityManager();
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'email' => $userDto->username
        ]);

        self::assertNotEmpty($user);
    }

    public function testGetCurrentUserWithWrongToken(): void
    {
        $token = 'wrongtoken';

        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];

        $client = self::getClient();
        $client->jsonRequest(
            'GET',
            $this->startingPath . '/users/current',
            [],
            $headers
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }
}
