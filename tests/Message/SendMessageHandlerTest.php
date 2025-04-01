<?php

namespace Message;

use App\Entity\Message;
use App\Message\SendMessage;
use App\Message\SendMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class SendMessageHandlerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private $entityManager;

    private SendMessageHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new SendMessageHandler($this->entityManager);
    }

    public function test_it_creates_persists_and_flushes_new_message(): void
    {
        // Arrange
        $messageText = 'Test message content';
        $sendMessage = new SendMessage($messageText);

        // Expect persist and flush to be called
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Message $message) use ($messageText) {
                // Verify message properties
                $this->assertEquals($messageText, $message->getText());
                $this->assertEquals('sent', $message->getStatus());
                $this->assertNotNull($message->getUuid());
                $this->assertNotNull($message->getCreatedAt());
                $this->assertInstanceOf(\DateTime::class, $message->getCreatedAt());

                // Make sure the UUID is valid
                $this->assertTrue(Uuid::isValid($message->getUuid()));

                return true;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->handler->__invoke($sendMessage);
    }
}