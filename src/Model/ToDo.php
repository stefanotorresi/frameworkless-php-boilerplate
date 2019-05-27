<?php declare(strict_types=1);

namespace Acme\ToDo\Model;

use function Acme\ToDo\now;
use Assert\Assert;
use Assert\InvalidArgumentException;
use Assert\LazyAssertionException;
use DateTimeImmutable;
use Doctrine\Instantiator\Instantiator;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ToDo implements JsonSerializable
{
    public const DATE_FORMAT = "Y-m-d\TH:i:s.uO"; // ISO8601 with milliseconds

    /**
     * @var UuidInterface
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var DateTimeImmutable|null
     */
    private $dueFor;

    /**
     * @var DateTimeImmutable|null
     */
    private $doneAt;

    /**
     * @throws InvalidDataException
     */
    public function __construct(string $name, DateTimeImmutable $dueFor = null, DateTimeImmutable $doneAt = null)
    {
        $this->id        = Uuid::uuid4();
        $this->name      = $name;
        $this->createdAt = now();
        $this->dueFor    = $dueFor;
        $this->doneAt    = $doneAt;

        $this->validate();
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function withId(string $id): self
    {
        $new = clone $this;
        $new->id = Uuid::fromString($id);
        $new->validate();

        return $new;
    }

    public function withCreatedAt(string $createdAt): self
    {
        $new = clone $this;
        $new->createdAt = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $createdAt);
        $new->validate();

        return $new;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreatedAtAsString(): string
    {
        return $this->createdAt->format(self::DATE_FORMAT);
    }

    public function getDueFor(): ?DateTimeImmutable
    {
        return $this->dueFor;
    }

    public function getDueForAsString(): string
    {
        return $this->dueFor ? $this->dueFor->format(self::DATE_FORMAT) : '';
    }

    public function markDone(): void
    {
        $this->doneAt = now();
    }

    public function getDoneAt(): ?DateTimeImmutable
    {
        return $this->doneAt;
    }

    public function getDoneAtAsString(): string
    {
        return $this->doneAt ? $this->doneAt->format(self::DATE_FORMAT): '';
    }

    public function isDone(): bool
    {
        return $this->doneAt !== null;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->name,
            'createdAt' => $this->getCreatedAtAsString(),
            'dueFor' => $this->getDueForAsString() ?: null,
            'doneAt' => $this->getDoneAtAsString() ?: null,
            'isDone' => $this->isDone(),
        ];
    }

    /**
     * This method is intended as a type-unsafe alternative to the constructor
     *
     * @throws InvalidDataException
     */
    public static function createFromArray(array $data): self
    {
        /**
         * we use this to avoid double validation.
         * @var $new self
         */
        $new = (new Instantiator)->instantiate(__CLASS__);

        $new->id = Uuid::uuid4();
        $new->createdAt = now();
        $new->updateFromArray($data);

        return $new;
    }

    /**
     * @throws InvalidDataException
     */
    public function updateFromArray(array $data): void
    {
        $this->name = $data['name'] ?? $this->name;
        $this->dueFor = isset($data['dueFor']) ?
            DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $data['dueFor']) :
            $this->dueFor;
        $this->doneAt = isset($data['doneAt']) ?
            DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $data['doneAt']) :
            $this->doneAt;

        $this->validate();
    }

    /**
     * @throws InvalidDataException
     */
    private function validate(): void
    {
        $assert = Assert::lazy()
              ->tryAll()
              ->that($this->id, 'id')->isInstanceOf(Uuid::class)
              ->that($this->name, 'name')->string()->notBlank()
              ->that($this->createdAt, 'createdAt')->isInstanceOf(DateTimeImmutable::class, 'Invalid date format')
              ->that($this->dueFor, 'dueFor')->nullOr()->isInstanceOf(DateTimeImmutable::class, 'Invalid date format')
              ->that($this->doneAt, 'doneAt')->nullOr()->isInstanceOf(DateTimeImmutable::class, 'Invalid date format')
        ;
        try {
            $assert->verifyNow();
        } catch (LazyAssertionException $e) {
            throw InvalidDataException::fromLazyAssertionException($e);
        }
    }
}
