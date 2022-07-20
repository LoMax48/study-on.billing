<?php

namespace App\Controller;

use App\Dto\CourseDto;
use App\Dto\PayDto;
use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1/courses")
 */
class CourseController extends AbstractController
{
    /**
     * @Route("", name="app_courses_index", methods={"GET"})
     */
    public function index(CourseRepository $courseRepository, SerializerInterface $serializer): Response
    {
        $courses = $courseRepository->findAll();

        $coursesDto = [];
        foreach ($courses as $course) {
            $courseDto = new CourseDto();

            $courseDto->code = $course->getCode();
            $courseDto->type = $course->getType();
            $courseDto->price = $course->getPrice();
            $courseDto->title = $course->getTitle();

            $coursesDto[] = $courseDto;
        }

        $response = new Response();
        $response->setStatusCode(Response::HTTP_OK);
        $response->setContent($serializer->serialize($coursesDto, 'json'));
        $response->headers->add([
            'Content-Type' => 'application/json',
        ]);

        return $response;
    }

    /**
     * @Route("/{code}", name="app_course_show", methods={"GET"})
     */
    public function show(string $code, CourseRepository $courseRepository, SerializerInterface $serializer): Response
    {
        $course = $courseRepository->findOneBy([
            'code' => $code,
        ]);

        $response = new Response();
        if (!isset($course)) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent($serializer->serialize([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Данный курс не найден',
            ], 'json'));
        } else {
            $courseDto = new CourseDto();

            $courseDto->code = $course->getCode();
            $courseDto->type = $course->getType();
            $courseDto->price = $course->getPrice();
            $courseDto->title = $course->getTitle();

            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($serializer->serialize($courseDto, 'json'));
        }

        $response->headers->add([
            'Content-Type' => 'application/json',
        ]);

        return $response;
    }

    /**
     * @Route("/{code}/pay", name="app_course_pay", methods={"POST"})
     */
    public function pay(
        string $code,
        CourseRepository $courseRepository,
        PaymentService $paymentService,
        SerializerInterface $serializer
    ): Response {
        $course = $courseRepository->findOneBy([
            'code' => $code,
        ]);

        $response = new Response();

        if (!isset($course)) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
            $response->setContent($serializer->serialize([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Данный курс не найден',
            ], 'json'));
            $response->headers->add([
                'Content-Type' => 'application/json',
            ]);

            return $response;
        }

        $user = $this->getUser();

        try {
            $transaction = $paymentService->payment($user, $course);
            $expiresTime = $transaction->getExpiresTime();

            $payDto = new PayDto();
            $payDto->success = true;
            $payDto->courseType = $course->getType();
            $payDto->expiresTime = $expiresTime ? $expiresTime->format('Y-m-d T H:i:s') : null;

            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($serializer->serialize($payDto, 'json'));
            $response->headers->add([
                'Content-Type' => 'application/json',
            ]);

            return $response;
        } catch (\Exception $exception) {
            $response->setStatusCode($exception->getCode());
            $response->setContent($serializer->serialize([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ], 'json'));
            $response->headers->add([
                'Content-Type' => 'application/json'
            ]);

            return $response;
        }
    }

    /**
     * @Route("/new", name="app_course_new", methods={"POST"})
     */
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        SerializerInterface $serializer
    ): Response {
        try {
            $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

            $course = $courseRepository->findOneBy([
                'code' => $courseDto->code
            ]);

            if ($course) {
                $dataResponse = [
                    'code' => Response::HTTP_METHOD_NOT_ALLOWED,
                    'message' => 'Курс с таким кодом уже существует',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

            $course = Course::fromDtoNew($courseDto);

            $entityManager->persist($course);
            $entityManager->flush();

            $dataResponse = [
                'code' => Response::HTTP_CREATED,
                'success' => true,
            ];
        } catch (\Exception $exception) {
            $dataResponse = [
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $exception->getMessage(),
            ];
        }

        $response = new Response();
        $response->setStatusCode($dataResponse['code']);
        $response->setContent($serializer->serialize($dataResponse, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }

    /**
     * @Route("/{code}/edit", name="app_course_edit", methods={"POST"})
     */
    public function edit(
        string $code,
        Request $request,
        CourseRepository $courseRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ): Response {
        try {
            $courseDto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

            $course = $courseRepository->findOneBy(['code' => $code]);
            if (!$course) {
                $dataResponse = [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Курс не существует',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

            $courseDuplicate = $courseRepository->findOneBy(['code' => $courseDto->code]);
            if ($courseDuplicate && $code !== $courseDuplicate->getCode()) {
                $dataResponse = [
                    'code' => Response::HTTP_METHOD_NOT_ALLOWED,
                    'message' => 'Курс с данным кодом уже существует',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

            $course->fromDtoEdit($courseDto);
            $entityManager->flush();

            $dataResponse = [
                'code' => Response::HTTP_OK,
                'success' => true,
            ];
        } catch (\Exception $exception) {
            $dataResponse = [
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $exception->getMessage(),
            ];
        }

        $response = new Response();
        $response->setStatusCode($dataResponse['code']);
        $response->setContent($serializer->serialize($dataResponse, 'json'));
        $response->headers->add(['Content-Type' => 'application/json']);

        return $response;
    }
}
