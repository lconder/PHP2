<?php

namespace App\Tests\Controller;

use App\Controller\ApiResultsController;
use App\Controller\ApiUsersController;
use App\Entity\Result;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultControllerTest
 *
 * @package App/Tests/Controller
 * @group controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsController
*/
class ApiResultControllerTest extends BaseTestCase {

    private const API_ROUTE = '/api/v1/results';

    /**
     * Test OPTIONS /users[/userId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsUserAction204NoContent(): void
    {
        // OPTIONS /api/v1/results
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::API_ROUTE
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::API_ROUTE . '/' . self::$faker->numberBetween(1, 100)
        );

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }

    /**
     * Test POST /results 201 Created
     *
     * @return array result data
     */
    public function testPostResultAction201Created(): array
    {
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(0, 100),
        ];

        // 201
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_POST,
            self::API_ROUTE,
            [],
            [],
            $headers,
            json_encode($p_data),
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        self::assertNotEmpty($result['result']['id']);

        return $result['result'];

    }

    /**
     * Test GET /results/{resultId} 200 Ok (XML)
     *
     * @param array $result result returned by testPostResultAction201Created
     * @return void
     * @depends testPostResultAction201Created
    */
    public function testCGetAction200XmlOk(array $result): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::API_ROUTE . '/' . $result['id'] . '.xml',
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        self::assertTrue($response->headers->contains('content-type', 'application/xml'));

    }

    /**
     * Test GET /results/{resultId}
     *
     * @param array $result result returned by testPostResultAction201Created
     * @return void
     * @depends testPostResultAction201Created
    */
    public function testGetResultAction200Ok(array $result): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::API_ROUTE . '/' . $result['id'],
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        self::assertJson((string) $response->getContent());
        $result_aux = json_decode((string) $response->getContent(), true);
        self::assertSame($result['id'], $result_aux['result']['id']);
    }

    /**
     * Test GET /results/{userId}/users
     *
     * @param array $result result returned by testPostResultAction201Created
     * @return void
     * @depends testPostResultAction201Created
     */
    public function testGetResultsByUser(array $result): void
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_GET,
            self::API_ROUTE . '/'. $result['user']['id'] .'/users',
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        self::assertJson((string) $response->getContent());
    }

    /**
     * Test PUT /results/{userId}/users
     *
     * @param array $result result returned by testPostResultAction201Created
     * @return void
     * @depends testPostResultAction201Created
     */
    public function testCloneResults(array $result): void
    {
        $role = self::$faker->word;
        $p_data = [
            User::EMAIL_ATTR => self::$faker->email,
            User::PASSWD_ATTR => self::$faker->password,
            User::ROLES_ATTR => [ $role ],
        ];

        // 201
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_POST,
            ApiUsersController::RUTA_API,
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();


        $user = json_decode($response->getContent(), true);
        // self::assertNotEmpty($user['user']['id']);

        $p_data = [
          'parent' => $result['user']['id']
        ];

        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_PUT,
            self::API_ROUTE . '/'. $user['user']['id'] .'/users',
            [],
            [],
            $headers,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        self::assertJson((string) $response->getContent());
    }


    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param array $result result returned by testPostResultAction201Created()
     * @depends testPostResultAction201Created
     * @return int
     */
    public function testDeleteResultAction204NoContent(array $result): int
    {
        $headers = $this->getTokenHeaders();
        self::$client->request(
            Request::METHOD_DELETE,
            self::API_ROUTE . '/' . $result['id'],
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertEmpty((string) $response->getContent());
        return $result['id'];
    }

    /**
     * Test PUT /results/{resultId} 209 Content Returned
     * @param array $result result returned by testPostResultAction201Created()
     * @depends testPostResultAction201Created
     * @return array
     *
    */
    public function testPutResultAction209ContentReturned(array $result): array
    {
        $headers = $this->getTokenHeaders();
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(0, 100),
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::API_ROUTE. '/' . $result['id'],
            [],
            [],
            $headers,
            json_encode($p_data),
        );
        $response = self::$client->getResponse();
        self::assertSame(209, $response->getStatusCode());
        self::assertJson((string) $response->getContent());
        $result_aux = json_decode((string) $response->getContent(), true);
        self::assertSame($result['id'], $result_aux['result']['id']);
        return $result_aux['result'];
    }

}