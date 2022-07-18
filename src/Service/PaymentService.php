<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function deposit(User $user, float $amount): void
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $transaction = new Transaction();
            $transaction->setType(2);
            $transaction->setBillingUser($user);
            $transaction->setOperationTime(new DateTime());
            $transaction->setAmount($amount);

            $user->setBalance($user->getBalance() + $amount);

            $this->entityManager->persist($transaction);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
    }

    public function payment(User $user, Course $course): Transaction
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            if ($user->getBalance() < $course->getPrice()) {
                throw new \Exception('На вашем счету недостаточно средств.', 406);
            }

            $transaction = new Transaction();
            $transaction->setType(1);
            $transaction->setBillingUser($user);
            $transaction->setOperationTime(new DateTime());
            $transaction->setAmount($course->getPrice());
            $transaction->setCourse($course);

            if ($course->getType() === 'rent') {
                $expiresTime = (new DateTime())->add(new DateInterval('P1W'));
                $transaction->setExpiresTime($expiresTime);
            } else {
                $transaction->setExpiresTime(null);
            }

            $user->setBalance($user->getBalance() - $course->getPrice());

            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }

        return $transaction;
    }
}