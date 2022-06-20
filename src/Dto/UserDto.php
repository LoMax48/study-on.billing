<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *  @OA\Schema(
 *      title="UserDto",
 *      description="UserDto"
 *  )
 */
class UserDto
{
    /**
     *  @OA\Property(
     *      type="string",
     *      title="Username",
     *      description="Username"
     *  )
     *  @Serializer\Type("string")
     *  @Assert\Email(message="Email address {{ value }} is not valid")
     *  @Assert\NotBlank(message="Username can't be blank")
     */
    public string $username;

    /**
     *  @OA\Property(
     *      type="string",
     *      title="Password",
     *      description="Password"
     *  )
     *  @Serializer\Type("string")
     *  @Assert\Length(
     *      min="6",
     *      minMessage="Your password must be at least {{ limit }} characters",
     *  )
     *  @Assert\NotBlank(message="Password can't be blank")
     */
    public string $password;
}