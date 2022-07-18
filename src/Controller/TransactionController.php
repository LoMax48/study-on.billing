<?php

namespace App\Controller;

use App\Dto\TransactionDto;
use App\Repository\CourseRepository;
use App\Repository\TransactionRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransactionController extends AbstractController
{
    /**
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
                    $expiresTime = $transaction->getExpiresTime()->format('Y-m-d T H:i:s');
                }
                $transactionDto = new TransactionDto();
                $transactionDto->id = $transaction->getId();
                $transactionDto->operationTime = $transaction->getOperationTime()->format('Y-m-d T H:i:s');
                $transactionDto->expiresTime = $expiresTime;
                $transactionDto->type = $transaction->getType();
                $transactionDto->amount = $transaction->getAmount();
                $transactionDto->course = $course ? $course->getId() : null;

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
