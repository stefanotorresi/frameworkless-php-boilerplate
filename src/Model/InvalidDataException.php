<?php declare(strict_types=1);

namespace Acme\ToDo\Model;

use Assert\InvalidArgumentException;
use Assert\LazyAssertionException;
use Exception;

final class InvalidDataException extends Exception
{
    /**
     * @var string[]
     */
    private $details = [];

    public static function fromLazyAssertionException(LazyAssertionException $e): self
    {
        $new = new static('Invalid data', 0,  $e);

        foreach ($e->getErrorExceptions() as $error) {
            $new->details[$error->getPropertyPath()] = $error->getMessage();
        }

        return $new;
    }

    public static function fromAssertInvalidArgumentException(InvalidArgumentException $e): self
    {
        $new = new static('Invalid data', 0,  $e);
        $new->details[$e->getPropertyPath()] = $e->getMessage();
        return $new;
    }

    /**
     * @return string[]
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
