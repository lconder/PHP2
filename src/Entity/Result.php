<?php

namespace App\Entity;

use App\Repository\ResultRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ResultRepository", repositoryClass=ResultRepository::class)
 *
 * @Serializer\XmlNamespace(uri="http://www.w3.org/2005/Atom", prefix="atom")
 * @Serializer\AccessorOrder(
 *     "custom",
 *     custom={ "id", "result", "user", "time", "_links" }
 *     )
 *
 * @Hateoas\Relation(
 *     name="parent",
 *     href="expr(constant('\\App\\Controller\\ApiResultsController::RUTA_API'))"
 * )
 *
 * @Hateoas\Relation(
 *     name="self",
 *     href="expr(constant('\\App\\Controller\\ApiResultsController::RUTA_API') ~ '/' ~ object.getId())"
 * )
 */
class Result
{
    public const RESULT_ATTR = 'result';
    public const USER_ATTR = 'user';
    public const TIME_ATTR = 'time';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @Serializer\XmlAttribute
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="integer")
     *
     * @Serializer\SerializedName(Result::RESULT_ATTR)
     * @Serializer\XmlElement(cdata=false)
     */
    private int $result;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Serializer\SerializedName(Result::TIME_ATTR)
     * @Serializer\XmlElement(cdata=false)
     */
    private $time;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="results")
     * @ORM\JoinColumn(nullable=false)
     *
     * @Serializer\SerializedName(Result::USER_ATTR)
     * @Serializer\XmlElement(cdata=false)
     */
    private $user;

    /**
     * Result constructor.
     * @param int $result
     * @param $time
     * @param $user
     */
    public function __construct(int $result = 0, $time, $user)
    {
        $this->result = $result;
        $this->time = $time;
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResult(): ?int
    {
        return $this->result;
    }

    public function setResult(int $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
