<?php


namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\DataFixtures\CourseFixtures;
use App\Dto\CourseDto;
use App\Entity\User;
use App\Service\PaymentService;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CourseControllerTest extends AbstractTest
{
    private string $startingPath = '/api/v1/courses';
    private SerializerInterface $serializer;

    public function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::$kernel->getContainer()->get('jms_serializer');
    }

    public function getFixtures(): array
    {
        return [
            new AppFixtures(
                self::getContainer()->get(UserPasswordHasherInterface::class),
                self::getContainer()->get(PaymentService::class)
            ),
            CourseFixtures::class
        ];
    }

    public function auth($user): array
    {
        $client = self::getClient();
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            [ 'CONTENT_TYPE' => 'application/json' ],
            $this->serializer->serialize($user, 'json')
        );

        return json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function testGetAllCourses(): void
    {
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'admin123',
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
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token']
            ]
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        self::assertTrue($client->getResponse()->headers->contains(
            'Content-Type',
            'application/json'
        ));

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertCount(8, $response);
    }

    public function testGetCourse(): void
    {
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'admin123',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        $codeCourse = 'PPBIB';
        $client->request(
            'GET',
            $this->startingPath . '/' . $codeCourse,
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

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertEquals('free', $response['type']);

        $codeCourse = 'NONEXIST';
        $client->request(
            'GET',
            $this->startingPath . '/' . $codeCourse,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );

        $this->assertResponseCode(Response::HTTP_NOT_FOUND, $client->getResponse());
    }

    public function testPayCourse(): void
    {
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'admin123',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();
        $codeCourse = 'PPBI';
        $client->request(
            'POST',
            $this->startingPath . '/' . $codeCourse . '/pay',
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

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertEquals(true, $response['success']);

        $codeCourse = 'CAMP';
        $client->request(
            'POST',
            $this->startingPath . '/' . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );

        $codeCourse = 'MSCB';
        $client->request(
            'POST',
            $this->startingPath . '/' . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ]
        );

        $this->assertResponseCode(Response::HTTP_NOT_ACCEPTABLE, $client->getResponse());

        $token = '123';
        $client->request(
            'POST',
            $this->startingPath . '/' . $codeCourse . '/pay',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseCode(Response::HTTP_UNAUTHORIZED, $client->getResponse());
    }

    public function testCourseCreate(): void
    {
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'admin123',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();

        $courseDto = new CourseDto();
        $courseDto->code = 'NEWCOURSE';
        $courseDto->type = 'buy';
        $courseDto->price = 2000;
        $courseDto->title = 'Тестовый курс';

        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );

        $this->assertResponseCode(Response::HTTP_CREATED, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertEquals(true, $response['success']);

        $client = self::getClient();

        $courseDto = new CourseDto();
        $courseDto->code = 'NEWCOURSE';
        $courseDto->type = 'buy';
        $courseDto->price = 2000;
        $courseDto->title = 'Тестовый курс';

        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );

        $this->assertResponseCode(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertEquals($response['message'], 'Курс с таким кодом уже существует');

        $user = [
            'username' => 'user@mail.ru',
            'password' => 'user123',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();

        $courseDto = new CourseDto();
        $courseDto->code = 'NEWTEST';
        $courseDto->type = 'buy';
        $courseDto->price = 1000;
        $courseDto->title = 'Новый тест';

        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/new',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );

        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());
    }

    public function testCourseEdit(): void
    {
        $user = [
            'username' => 'admin@mail.ru',
            'password' => 'admin123',
        ];
        $userData = $this->auth($user);

        // Успешное изменение
        $client = self::getClient();

        $code =  'PPBIB';

        $courseDto = new CourseDto();
        $courseDto->code = 'EDIT';
        $courseDto->type = 'rent';
        $courseDto->price = 1000;
        $courseDto->title = 'Изменённый курс';

        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/' . $code . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );

        $this->assertResponseCode(Response::HTTP_OK, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertEquals(true, $response['success']);

        // Попытка изменения кода на уже существующий
        $client = self::getClient();

        $code =  'EDIT';

        $courseDto = new CourseDto();
        $courseDto->code = 'PPBI';
        $courseDto->type = 'rent';
        $courseDto->price = 1000;
        $courseDto->title = 'Изменённый курс';

        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/' . $code . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );

        $this->assertResponseCode(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertEquals($response['message'], 'Курс с данным кодом уже существует');

        // Попытка изменения за обычного пользователя
        $user = [
            'username' => 'user@mail.ru',
            'password' => 'user123',
        ];
        $userData = $this->auth($user);

        $client = self::getClient();

        $code =  'PPBIB3';

        $courseDto = new CourseDto();
        $courseDto->code = 'TRYUSER';
        $courseDto->type = 'rent';
        $courseDto->price = 1000;
        $courseDto->title = 'Попытка изменения за обычного пользователя';

        $dataRequest = $this->serializer->serialize($courseDto, 'json');
        $client->request(
            'POST',
            $this->startingPath . '/' . $code . '/edit',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $userData['token'],
            ],
            $dataRequest
        );

        $this->assertResponseCode(Response::HTTP_FORBIDDEN, $client->getResponse());
    }
}
