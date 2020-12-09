<?php


namespace App\Controller;


use App\Entity\Message;
use App\Entity\Result;
use App\Entity\User;
use App\Utility\Utils;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsController::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsController extends AbstractController
{
    public const RUTA_API = '/api/v1/results';

    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em) {
        $this->entityManager = $em;
    }

    /**
     * CGET Action
     *
     * @param Request $request
     * @return Response
     * @Route(
     *      path=".{_format}/{sort?id}",
     *      defaults={ "_format": "json", "sort": "id" },
     *      requirements={
     *         "sort": "id|result|user|time",
     *         "_format": "json|xml"
     *      },
     *      methods={ Request::METHOD_GET },
     *      name="cget"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
    */
    public function cgetAction(Request $request): Response
    {
        $order = $request->get('sort');
        $results = $this->entityManager
            ->getRepository(Result::class)
            ->findBy([], [$order => 'ASC']);
        $format = Utils::getFormat($request);

        if(empty($results)) {
            return $this->error404($format);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results' => array_map(fn ($r) => ['result' => $r], $results)],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'must-revalidate',
                self::HEADER_ETAG => md5(json_encode($results)),
            ]
        );
    }

    /**
     * GET Action
     *
     * @param Request $request
     * @param  int $resultId Result id
     * @return Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
    */
    public function getAction(Request $request, int $resultId): Response
    {
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);
        $format = Utils::getFormat($request);

        if(empty($result)) {
            return $this->error404($format);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'must-revalidate',
                self::HEADER_ETAG => md5(json_encode($result)),
            ]
        );
    }

    /**
     * POST action
     *
     * @param Request $request request
     * @return Response
     * @Route(
     *     path=".{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_POST },
     *     name="post"
     * )
     *
    */
    public function postAction(Request $request): Response
    {
        // Puede crear un resultado sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access'
            );
        }

        $body = $request->getContent();
        $postData = json_decode($body, true);
        $format = Utils::getFormat($request);
        $token = $request->headers->get('Authorization');

        $user_id = $this->decodeToken($token);
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([ 'id' => $user_id ]);


        if (!isset($postData[Result::RESULT_ATTR])) {
            // 422 - Unprocessable Entity -> Faltan datos
            $message = new Message(Response::HTTP_UNPROCESSABLE_ENTITY, Response::$statusTexts[422]);
            return Utils::apiResponse(
                $message->getCode(),
                $message,
                $format
            );
        }

        $result = new Result(
            $postData[Result::RESULT_ATTR],
            new DateTime('now'),
            $user
        );

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ Result::RESULT_ATTR => $result ],
            $format,
            [
                'Location' => self::RUTA_API . '/' . $result->getId(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param int $resultId
     * @return Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_PUT },
     *     name="put"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function putAction(Request $request, int $resultId): Response
    {
        // Puede crear un resultado sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access'
            );
        }

        $body = $request->getContent();
        $postData = json_decode($body, true);
        $format = Utils::getFormat($request);

        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (null === $result) {    // 404 - Not Found
            return $this->error404($format);
        }

        // result
        if (isset($postData[Result::RESULT_ATTR])) {
            $result->setResult($postData[Result::RESULT_ATTR]);
        }

        $this->entityManager->flush();

        return Utils::apiResponse(
            209,// 209 - Content Returned
            [ Result::RESULT_ATTR => $result ],
            $format
        );
    }

    /**
     * Summary: Provides the list of HTTP supported methods
     * Notes: Return a &#x60;Allow&#x60; header with a list of HTTP supported methods.
     *
     * @param  int $resultId Result id
     * @return Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "resultId" = 0, "_format": "json" },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_OPTIONS },
     *     name="options"
     * )
     */
    public function optionsAction(int $resultId): Response
    {
        $methods = $resultId
            ? [ Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE ]
            : [ Request::METHOD_GET, Request::METHOD_POST ];
        $methods[] = Request::METHOD_OPTIONS;

        return new JsonResponse(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(', ', $methods),
                self::HEADER_CACHE_CONTROL => 'public, inmutable'
            ]
        );
    }

    /**
     * DELETE Action
     * Summary: Removes the User resource.
     * Notes: Deletes the user identified by &#x60;userId&#x60;.
     *
     * @param   Request $request
     * @param   int $resultId User id
     * @return  Response
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_DELETE },
     *     name="delete"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="`Unauthorized`: Invalid credentials."
     * )
     */
    public function deleteAction(Request $request, int $resultId): Response
    {
        // Puede crear un usuario sólo si tiene ROLE_ADMIN
        if (!$this->isGranted(self::ROLE_ADMIN)) {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: you don\'t have permission to access'
            );
        }
        $format = Utils::getFormat($request);

        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (null === $result) {   // 404 - Not Found
            return $this->error404($format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @param $token
     * @return mixed
     */
    private function decodeToken($token) {
        $tokenParts = explode(".", $token);
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);
        return $jwtPayload->id;
    }

    /**
     * Response 404 Not Found
     * @param string $format
     *
     * @return Response
     */
    private function error404(string $format): Response
    {
        $message = new Message(Response::HTTP_NOT_FOUND, Response::$statusTexts[404]);
        return Utils::apiResponse(
            $message->getCode(),
            $message,
            $format
        );
    }
}