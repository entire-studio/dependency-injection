<?php

declare(strict_types=1);

namespace EntireStudio\DependencyInjection\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
