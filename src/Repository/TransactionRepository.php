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

    public function findSoonExpiredTransactions(User $user)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.billingUser = :user_id')
            ->andWhere('t.type = 1')
            ->andWhere('t.expiresTime BETWEEN :today AND :tomorrow')
            ->setParameter('today', new \DateTimeImmutable())
            ->setParameter('tomorrow', (new \DateTimeImmutable())->modify('+1 day'))
            ->setParameter('user_id', $user->getId())
            ->getQuery()
            ->getResult();
    }

    public function getPayStatisticPerMonth()
    {
        $dql = "
            SELECT c.title, 
                   (CASE WHEN c.type = 1 THEN 'Аренда' ELSE 'Покупка' END) as course_type, 
                   COUNT(t.id) as transaction_count, 
                   SUM(t.amount) as total_amount
            FROM App\\Entity\\Transaction t JOIN App\\Entity\\Course c WITH t.course = c.id
            WHERE t.type = 1 AND t.operationTime BETWEEN DATE_SUB(CURRENT_DATE(), 1, 'MONTH') AND CURRENT_DATE()
            GROUP BY c.title, c.type
        ";

        return $this->_em->createQuery($dql)->getResult();
    }
}
