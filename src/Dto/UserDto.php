<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    /**
     * @Serializer\Type("string")
     * @Assert\Email(message="Email address {{ value }} is not valid")
     * @Assert\Blank(message="Username can't be blank")
     */
    public string $username;

    /**
     * @Serializer\Type("string")
     * @Assert\Length(
     *     min="6",
     *     minMessage="Your password must be at least {{ limit }} characters",
     * )
     * @Assert\NotBlank(message="Password can't be blank")
     */
    public string $password;
}