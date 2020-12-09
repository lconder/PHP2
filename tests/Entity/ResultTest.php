<?php

namespace App\Tests\Entity;

use App\Entity\User;
use DateTime;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use Exception;
use PHPUnit\Framework\TestCase;
use App\Entity\Result;

/**
 * Class ResultTest
 *
 * @package App\Tests\Entity
 *
 * @group   entities
 * @coversDefaultClass \App\Entity\Result
 */
class ResultTest extends TestCase {

    protected static Result $result;
    protected static User $user;

    private static FakerGeneratorAlias $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$user = new User();
        self::$result = new Result(
            0,
            new DateTime('now'),
            self::$user
        );
        self::$faker = FakerFactoryAlias::create('es_ES');
    }

    public function testConstructor(): void {
        $time = new DateTime('now');
        $result = new Result(
            0,
            $time,
            self::$user
        );
        self::assertEquals(0, $result->getResult());
        self::assertEquals($time, $result->getTime());
    }

    /**
     * Implement testGetId().
     *
     * @return void
     */
    public function testGetId(): void
    {
        self::assertEquals(0, self::$result->getId());
    }

    /**
     * Implements testGetSetResult().
     *
     * @throws Exception
     * @return void
     */
    public function testGetSetResult() {
        $_result = self::$faker->numberBetween(0, 100);
        self::$result->setResult($_result);
        static::assertSame(
            $_result,
            self::$result->getResult()
        );
    }

    /**
     * Implements testGetSetResult().
     *
     * @throws Exception
     * @return void
     */
    public function testGetSetTime() {
        $_time = new DateTime('now');
        self::$result->setTime($_time);
        static::assertSame(
            $_time,
            self::$result->getTime()
        );
    }


    /**
     * Implements testGetSetResult().
     *
     * @throws Exception
     * @return void
     */
    public function testGetSetUser() {
        $_user = new User();
        self::$result->setUser($_user);
        static::assertSame(
            $_user,
            self::$result->getUser()
        );
    }


}