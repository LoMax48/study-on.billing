<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
class Transaction
{
    private const OPERATION_TYPES = [
        1 => 'payment',
        2 => 'deposit',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $billingUser;

    /**
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="transactions")
     */
    private $course;

    /**
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime")
     */
    private $operationTime;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expiresTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBillingUser(): ?User
    {
        return $this->billingUser;
    }

    public function setBillingUser(?User $billingUser): self
    {
        $this->billingUser = $billingUser;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getType(): ?string
    {
        return self::OPERATION_TYPES[$this->type];
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getOperationTime(): ?\DateTimeInterface
    {
        return $this->operationTime;
    }

    public function setOperationTime(\DateTimeInterface $operationTime): self
    {
        $this->operationTime = $operationTime;

        return $this;
    }

    public function getExpiresTime(): ?\DateTimeInterface
    {
        return $this->expiresTime;
    }

    public function setExpiresTime(\DateTimeInterface $expiresTime): self
    {
        $this->expiresTime = $expiresTime;

        return $this;
    }
}
