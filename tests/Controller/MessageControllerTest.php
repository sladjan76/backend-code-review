<?php
declare(strict_types=1);

namespace Controller;

use App\Entity\Message;
use App\Message\SendMessage;
use App\Repository\MessageRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class MessageControllerTest extends WebTestCase
{
    use InteractsWithMessenger;

    private KernelBrowser $client;

    /** @var MessageRepository&MockObject */
    private MessageRepository $messageRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->messageRepository = $this->createMock(MessageRepository::class);

        self::getContainer()->set(MessageRepository::class, $this->messageRepository);
    }
    
//    function test_list(): void
//    {
//        $this->markTestIncomplete('the Controller-Action needs tests');
//    }

    public function test_list_returns_messages_as_json(): void
    {
        // Create mock message objects
        $message1 = $this->createMessageObject(
            '1f00bd14-3434-660c-aeec-6fa761676088',
            'A message with sent status',
            'sent'
        );
        $message2 = $this->createMessageObject(
            '1f00bd15-3434-660c-aeec-6fa761676088',
            'A message with read status',
            'read'
        );

        $messages = [$message1, $message2];

        // Configure the repository mock
        $this->messageRepository
            ->expects($this->once())
            ->method('by')
            ->willReturn($messages);

        // Make the request
        $this->client->request('GET', '/messages');
        $responseContent = $this->client->getResponse()->getContent();

        // Make assertions
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $this->assertIsString($responseContent);

        /** @var array<string,mixed> $responseData */
        $responseData = json_decode($responseContent, true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('messages', $responseData);
        $this->assertIsArray($responseData['messages']);
        $this->assertCount(2, $responseData['messages']);

        $this->assertArrayHasKey(0, $responseData['messages']);
        $this->assertEquals([
            'uuid' => '1f00bd14-3434-660c-aeec-6fa761676088',
            'text' => 'A message with sent status',
            'status' => 'sent',
        ], $responseData['messages'][0]);

        $this->assertEquals([
            'uuid' => '1f00bd15-3434-660c-aeec-6fa761676088',
            'text' => 'A message with read status',
            'status' => 'read',
        ], $responseData['messages'][1]);
    }

    public function test_list_returns_empty_array_when_no_results(): void
    {
        // Configure the repository mock to return an empty array
        $this->messageRepository
            ->expects($this->once())
            ->method('by')
            ->willReturn([]);

        // Make the request
        $this->client->request('GET', '/messages');
        $responseContent = $this->client->getResponse()->getContent();

        // Make assertions
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertIsString($responseContent);

        /** @var array<string,mixed> $responseData */
        $responseData = json_decode($responseContent, true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('messages', $responseData);
        $this->assertIsArray($responseData['messages']);
        $this->assertEmpty($responseData['messages']);
    }

    public function test_list_with_query_parameters(): void
    {
        // Expect that the Request object will be passed to the by() method
        $this->messageRepository
            ->expects($this->once())
            ->method('by')
            ->with($this->callback(function($request) {
                return $request->query->get('status') === 'sent';
            }))
            ->willReturn([]);

        // Make the request with query parameters
        $this->client->request(
            'GET',
            '/messages?status=sent'
        );

        // Make assertions
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    function test_that_it_sends_a_message(): void
    {
        $this->client->request('GET', '/messages/send', [
            'text' => 'Hello World',
        ]);

        $this->assertResponseIsSuccessful();

        // This is using https://packagist.org/packages/zenstruck/messenger-test
        $this->transport('sync')
            ->queue()
            ->assertContains(SendMessage::class, 1);
    }

    private function createMessageObject(string $uuid, string $text, string $status): object
    {
        $message = $this->createMock(Message::class);

        $message->method('getUuid')->willReturn($uuid);
        $message->method('getText')->willReturn($text);
        $message->method('getStatus')->willReturn($status);

        return $message;
    }

}