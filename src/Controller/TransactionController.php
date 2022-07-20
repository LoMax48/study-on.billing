<?php

namespace App\Controller;

use App\Dto\TransactionDto;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransactionController extends AbstractController
{
    /**
     * @OA\Get(
     *     tags={"Transactions"},
     *     path="/api/v1/transactions/",
     *     description="История начислений и списаний текущего пользователя",
     *     summary="История начислений и списаний текущего пользователя",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Тип транзакции [payment | deposit]",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="course_code",
     *         in="query",
     *         description="Символьный код курса",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="skip_expired",
     *         in="query",
     *         description="Отбросить записи с датой оплаты аренд, которые уже истекли",
     *         @OA\Schema(type="bool")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список транзакций",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="int"
     *                     ),
     *                     @OA\Property(
     *                         property="operation_time",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="type",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="course_code",
     *                         type="string"
     *                     ),
     *                     @OA\Property(
     *                         property="amount",
     *                         type="number"
     *                     ),
     *                      @OA\Property(
     *                         property="expires_time",
     *                         type="string"
     *                     ),
     *                 )
     *             ),
     *        )
     *     )
     * )
     * @Route("/api/v1/transactions/", name="app_transactions", methods={"GET"})
     */
    public function transactionsHistory(
        Request $request,
        SerializerInterface $serializer,
        CourseRepository $courseRepository,
        TransactionRepository $transactionRepository
    ): Response {
        try {
            $user = $this->getUser();

            $transactions = $transactionRepository->findByFilter($user, $request, $courseRepository);

            $transactionsDto = [];

            foreach ($transactions as $transaction) {
                $course = $transaction->getCourse();
                $expiresTime = null;
                if (isset($course) && $course->getType() === 'rent') {
                    $expiresTime = $transaction->getExpiresTime()->format('Y-m-d H:i:s');
                }
                $transactionDto = new TransactionDto();
                $transactionDto->id = $transaction->getId();
                $transactionDto->operationTime = $transaction->getOperationTime()->format('Y-m-d H:i:s');
                $transactionDto->expiresTime = $expiresTime;
                $transactionDto->type = $transaction->getType();
                $transactionDto->amount = $transaction->getAmount();
                $transactionDto->course = $course ? $course->getCode() : null;

                $transactionsDto[] = $transactionDto;
            }

            $response = new Response();
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($serializer->serialize($transactionsDto, 'json'));
            $response->headers->add([
                'Content-Type' => 'application/json',
            ]);
        } catch (\Exception $exception) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $response->setContent($serializer->serialize([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ], 'json'));
            $response->headers->add([
                'Content-type' => 'application/json',
            ]);
        }

        return $response;
    }
}
