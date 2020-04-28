<?php declare(strict_types=1);

namespace Acme\ToDo\Model;

use function Acme\ToDo\datetime_from_string;
use function Acme\ToDo\now;
use Assert\Assert;
use Assert\LazyAssertionException;
use DateTimeImmutable;
use Doctrine\Instantiator\Instantiator;
use JsonSerializable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use const Acme\ToDo\DATE_FORMAT;

class ToDo implements JsonSerializable
{
    private UuidInterface $id;

    private string $name;

    private DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $dueFor;

    private ?DateTimeImmutable $doneAt;

    /**
     * @throws InvalidDataException
     */
    public function __construct(string $name, DateTimeImmutable $dueFor = null, DateTimeImmutable $doneAt = null)
    {
        $this->id        = Uuid::uuid4();
        $this->createdAt = now();

        $this->validate(compact('name'));

        $this->name      = $name;
        $this->dueFor    = $dueFor;
        $this->doneAt    = $doneAt;
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function withId(string $id): self
    {
        $new = clone $this;
        $new->id = Uuid::fromString($id);

        return $new;
    }

    public function withCreatedAt(string $createdAt): self
    {
        $new = clone $this;
        $new->createdAt = datetime_from_string($createdAt);

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
        return $this->createdAt->format(DATE_FORMAT);
    }

    public function getDueFor(): ?DateTimeImmutable
    {
        return $this->dueFor;
    }

    public function getDueForAsString(): string
    {
        return $this->dueFor ? $this->dueFor->format(DATE_FORMAT) : '';
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
        return $this->doneAt ? $this->doneAt->format(DATE_FORMAT): '';
    }

    public function isDone(): bool
    {
        return $this->doneAt !== null;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
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
     * @param mixed[] $data
     *
     * @throws InvalidDataException
     */
    public static function createFromArray(array $data): self
    {
        /**
         * we use this to avoid double validation.
         * @var self $new
         */
        $new = (new Instantiator)->instantiate(__CLASS__);

        $new->id = Uuid::uuid4();
        $new->createdAt = now();
        $new->dueFor = null;
        $new->doneAt = null;
        $new->updateFromArray($data);

        return $new;
    }

    /**
     * @param mixed[] $data
     * @throws InvalidDataException
     */
    public function updateFromArray(array $data): void
    {
        $this->validate($data);

        $this->name = $data['name'] ?? $this->name;
        $this->dueFor = isset($data['dueFor']) ? datetime_from_string($data['dueFor']) : $this->dueFor;
        $this->doneAt = isset($data['doneAt']) ? datetime_from_string($data['doneAt']) : $this->doneAt;
    }

    /**
     * @param mixed[] $data
     *
     * @throws InvalidDataException
     */
    private function validate(array $data): void
    {
        $assert = Assert::lazy()->tryAll();

        if (isset($data['name'])) {
            $assert->that($data['name'], 'name')->string()->notBlank();
        }

        if (isset($data['dueFor'])) {
            $assert->that($data['dueFor'], 'dueFor')->nullOr()->date(DATE_FORMAT);
        }

        if (isset($data['doneAt'])) {
            $assert->that($data['doneAt'], 'doneAt')->nullOr()->date(DATE_FORMAT);
        }

        try {
            $assert->verifyNow();
        } catch (LazyAssertionException $e) {
            throw InvalidDataException::fromLazyAssertionException($e);
        }
    }


}
