<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function add(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Transaction[] Returns an array of Transaction objects
     */
    public function findByFilter(User $user, Request $request, CourseRepository $courseRepository)
    {
        $types = [
            'payment' => 1,
            'deposit' => 2,
        ];

        $type = $request->query->get('type');
        $courseCode = $request->query->get('course_code');
        $skipExpired = (bool)$request->query->get('skip_expired');

        $queryBuilder = $this->createQueryBuilder('t')
            ->leftJoin('t.course', 'c')
            ->andWhere('t.billingUser = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('t.operationTime', 'DESC');
        if ($type) {
            $queryBuilder
                ->andWhere('t.type = :type')
                ->setParameter('type', $types[$type]);
        }
        if ($courseCode) {
            $queryBuilder
                ->andWhere('c.code = :courseCode')
                ->setParameter('courseCode', $courseCode);
        }
        if ($skipExpired) {
            $queryBuilder
                ->andWhere('t.expiresTime IS NULL OR t.expiresTime >= :today')
                ->setParameter('today', new \DateTimeImmutable());
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
