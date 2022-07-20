<?php

namespace App\Controller;

use App\Dto\CourseDto;
use App\Dto\PayDto;
use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
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
     * @OA\Get(
     *     path="/api/v1/courses",
     *     tags={"Courses"},
     *     summary="Получение всех курсов",
     *     description="Получение всех курсов",
     *     operationId="courses.index",
     *     @OA\Response(
     *          response="200",
     *          description="Успешное получение курсов",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(
     *                      property="code",
     *                      type="string",
     *                      example="AREND199230SKLADS"
     *                  ),
     *                  @OA\Property(
     *                      property="type",
     *                      type="string",
     *                      example="rent"
     *                  ),
     *                  @OA\Property(
     *                      property="price",
     *                      type="number",
     *                      format="float",
     *                      example="2000"
     *                  ),
     *              )
     *          )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/courses/{code}",
     *     tags={"Courses"},
     *     summary="Получение данного курса",
     *     description="Получение данного курса",
     *     operationId="courses.show",
     *     @OA\Response(
     *         response=200,
     *         description="Курс получен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="AREND199230SKLADS",
     *                 ),
     *                 @OA\Property(
     *                     property="type",
     *                     type="string",
     *                     example="rent",
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="number",
     *                     example="2021",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Данный курс не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="404"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Данный курс не найден"
     *                 ),
     *             ),
     *        )
     *     ),
     * )
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
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/{code}/pay",
     *     summary="Оплата курса",
     *     description="Оплата курса",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Курс куплен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="boolean",
     *                 ),
     *                 @OA\Property(
     *                     property="course_type",
     *                     type="string",
     *                 ),
     *                 @OA\Property(
     *                     property="expires_at",
     *                     type="string",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Данный курс не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="404",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Данный курс не найден",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=406,
     *         description="У вас недостаточно средств",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="406",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="На вашем счету недостаточно средств",
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid JWT Token",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="401",
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Invalid JWT Token",
     *                 ),
     *             ),
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/new",
     *     summary="Создание нового курса",
     *     description="Создание нового курса",
     *     operationId="courses.new",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Response(
     *         response=201,
     *         description="Курс успешно создан",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="201"
     *                 ),
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="true"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Курс с данным кодом уже существует в системе",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="405"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Курс с данным кодом уже существует в системе"
     *                 ),
     *             ),
     *        )
     *     ),
     * )
     * @Route("/new", name="app_course_new", methods={"POST"})
     */
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        SerializerInterface $serializer
    ): Response {
        try {
            if (!$this->getUser() || !in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles(), true)) {
                $dataResponse = [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Доступ запрещён',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

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
                'code' => $exception->getCode(),
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
     * @OA\Post(
     *     tags={"Courses"},
     *     path="/api/v1/courses/{code}/edit",
     *     summary="Редактирование курса",
     *     description="Редактирование курса",
     *     security={
     *         { "Bearer":{} },
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Курс изменен",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="200"
     *                 ),
     *                 @OA\Property(
     *                     property="success",
     *                     type="bool",
     *                     example="true"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Курс с данным кодом уже существует в системе",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="405"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Курс с данным кодом уже существует в системе"
     *                 ),
     *             ),
     *        )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Данный курс в системе не найден",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="code",
     *                     type="string",
     *                     example="404"
     *                 ),
     *                 @OA\Property(
     *                     property="message",
     *                     type="string",
     *                     example="Данный курс в системе не найден"
     *                 ),
     *             ),
     *         )
     *     )
     * )
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
            if (!$this->getUser() || !in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $dataResponse = [
                    'code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Доступ запрещён',
                ];
                throw new \Exception($dataResponse['message'], $dataResponse['code']);
            }

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
                'code' => $exception->getCode(),
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
