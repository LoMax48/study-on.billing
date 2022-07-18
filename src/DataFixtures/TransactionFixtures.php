<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courseRepository = $manager->getRepository(Course::class);
        $userRepository = $manager->getRepository(User::class);
        // Пользователь
        $user = $userRepository->findOneBy(['email' => 'user@mail.ru']);
        // Получаем существующие курсы
        $rentCourses = $courseRepository->findBy(['type' => 1]);
        $buyCourses = $courseRepository->findBy(['type' => 3]);

        $transactions = [
            [
                'type' => 2,
                'amount' => 10000,
                'customer' => $user,
                'operationTime' => new \DateTimeImmutable('2022-03-01 00:00:00'),
            ],
            // Арендованные курсы, у которых закончился срок аренды
            [
                'type' => 1,
                'amount' => $rentCourses[0]->getPrice(),
                'expiresAt' => new \DateTimeImmutable('2022-06-08 00:00:00'),
                'course' => $rentCourses[0],
                'customer' => $user,
                'operationTime' => new \DateTimeImmutable('2022-06-01 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $rentCourses[0]->getPrice(),
                'expiresAt' => new \DateTimeImmutable('2022-06-16 00:00:00'),
                'course' => $rentCourses[0],
                'customer' => $user,
                'operationTime' => new \DateTimeImmutable('2022-06-09 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $rentCourses[1]->getPrice(),
                'expiresAt' => new \DateTimeImmutable('2022-07-03 00:00:00'),
                'course' => $rentCourses[1],
                'customer' => $user,
                'operationTime' => new \DateTimeImmutable('2022-07-01 00:00:00'),
            ],
            // Арендованные курсы, у которых еще не закончился срок аренды
            [
                'type' => 1,
                'amount' => $rentCourses[0]->getPrice(),
                'expiresAt' => (new \DateTimeImmutable())->modify('+1 day'),
                'course' => $rentCourses[0],
                'customer' => $user,
                'operationTime' => (new \DateTimeImmutable())->modify('-6 day'),
            ],
            [
                'type' => 1,
                'amount' => $rentCourses[1]->getPrice(),
                'expiresAt' => (new \DateTimeImmutable())->modify('+1 day'),
                'course' => $rentCourses[1],
                'customer' => $user,
                'operationTime' => (new \DateTimeImmutable())->modify('-6 day'),
            ],
            [
                'type' => 1,
                'amount' => $rentCourses[1]->getPrice(),
                'expiresAt' => (new \DateTimeImmutable())->modify('+3 day'),
                'course' => $rentCourses[1],
                'customer' => $user,
                'operationTime' => (new \DateTimeImmutable())->modify('-6 day'),
            ],
            // Купленные курсы
            [
                'type' => 1,
                'amount' => $buyCourses[0]->getPrice(),
                'course' => $buyCourses[0],
                'customer' => $user,
                'operationTime' => new \DateTimeImmutable('2022-04-02 00:00:00'),
            ],
        ];

        foreach ($transactions as $transaction) {
            $newTransaction = new Transaction();
            $newTransaction->setType($transaction['type']);
            $newTransaction->setCourse($transaction['course'] ?? null);
            $newTransaction->setBillingUser($transaction['customer']);
            $newTransaction->setOperationTime($transaction['operationTime']);
            $newTransaction->setAmount($transaction['amount']);
            if (isset($transaction['expiresAt'])) {
                $newTransaction->setExpiresTime($transaction['expiresAt']);
            }
            $manager->persist($newTransaction);
        }

        $manager->flush();
    }
}
