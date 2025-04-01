<?php
/**
 * Code Review:
 *
 * 1. **UUID Auto-Generation:** The `uuid` field should be automatically assigned upon entity creation.
 *    Also, `uuid` property should not allow Null values as it is a unique identifier.
 *    - Suggested: Use `Symfony\Component\Uid\Uuid::v6()->toRfc4122()` in the constructor.
 *    - Suggested: Use `private string $uuid;`
 *
 * 2. **Initialize `createdAt`:** Currently, `createdAt` is not initialized in the constructor.
 *    - Suggested: Set `new DateTimeImmutable()` in the constructor to ensure it always has a value.
 *
 * 3. **Use `DateTimeImmutable`:** The `createdAt` property should use `DateTimeImmutable` instead of `DateTime`
 *    to prevent accidental modification since there is no reason to change time of creation.
 *
 * 4. **Enum for `status`:** The `status` should be, for example, MessageStatus type which should be an Enum.
 *    - Suggested:
 *          enum MessageStatus: string
 *          {
 *              case Sent = 'sent';
 *              case Read = 'read';
 *          }
 *
 */

namespace App\Entity;

use App\Repository\MessageRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    private ?string $text = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;
    
    #[ORM\Column(type: 'datetime')]
    private DateTime $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        
        return $this;
    }
}
