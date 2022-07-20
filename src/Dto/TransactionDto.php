<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;

class TransactionDto
{
    /**
     * @Serializer\Type("int")
     * @var int
     */
    public int $id;

    /**
     * @Serializer\Type("string")
     * @var string
     */
    public string $operationTime;

    /**
     * @Serializer\Type("string")
     * @var ?string
     */
    public ?string $expiresTime;

    /**
     * @Serializer\Type("string")
     * @var string
     */
    public string $type;

    /**
     * @Serializer\Type("string")
     * @var ?string
     */
    public ?string $course;

    /**
     * @Serializer\Type("float")
     * @var float
     */
    public float $amount;
}