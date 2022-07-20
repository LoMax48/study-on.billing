<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private PaymentService $paymentService;

    public function __construct(UserPasswordHasherInterface $passwordHasher, PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@mail.ru');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setBalance(0);

        $admin = new User();
        $admin->setEmail('admin@mail.ru');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setBalance(0);

        $manager->persist($user);
        $manager->persist($admin);

        $manager->flush();

        $this->paymentService->deposit($user, $_ENV['START_AMOUNT']);
        $this->paymentService->deposit($admin, $_ENV['START_AMOUNT']);
    }
}
