<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use function MongoDB\BSON\toRelaxedExtendedJSON;

class TransactionControllerTest extends AbstractTest
{
    private string $startingPath = '/api/v1/transactions/';
    private SerializerInterface $serializer;

    public function getFixtures(): array
    {
        return [
            new AppFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(PaymentService::class)
            ),
            new CourseFixtures(),
            new TransactionFixtures()
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get('jms_serializer');
    }

    public function auth($user): array
    {
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->serializer->serialize($user, 'json')
        );

        return json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function testTransactionsHistory(): void
    {
        // Успешное получение транзакций
        $user = [
            'username' => 'user@mail.ru',
            'password' => 'user123',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        $client->request(
            'GET',
            $this->startingPath,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertCount(9, $response);

        // Применение фильтров
        $filters = [
            'type' => 'payment',
            'course_code' => 'PPBI',
            'skip_expired' => true,
        ];

        $client->request(
            'GET',
            $this->startingPath . '/?' . http_build_query($filters),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );

        $this->assertResponseOk();

        $response  = $this->serializer->deserialize($client->getResponse()->getContent(), 'array', 'json');

        self::assertCount(1, $response);

        // Попытка получения транзакций для неавторизованного пользователя
        $token = 'novalid';

        $client->request(
            'GET',
            $this->startingPath,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }
}
