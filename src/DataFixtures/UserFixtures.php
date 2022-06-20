<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@mail.ru');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setBalance(5234.76);

        $admin = new User();
        $admin->setEmail('admin@mail.ru');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setBalance(99999.99);

        $manager->persist($user);
        $manager->persist($admin);

        $manager->flush();
    }
}
