<?php
/**
 * Code Review:
 *
 *  **Redundant Import:** `Doctrine\ORM\EntityManagerInterface` is not used anywhere
 *
 *  **Dependency Injection:** MessageRepository should be injected through constructor rather than
 *     method injection.
 *     - Suggested:
 *          public function __construct(
 *              private MessageRepository $messageRepository
 *          ) {
 *          }
 *
 *  - Method `list`
 *      1. **Missing HTTP Method:** In the route it's not specified what Http method is supported
 *         - Suggested: #[Route('/messages', methods: ['GET'])]
 *
 *      2. **Variable Name Conflict:** Both, result and repository have same name `$messages`.
 *         - Suggested: public function list(Request $request, MessageRepository $messageRepository).
 *
 *      3. **JSON Encoding:** Using json_encode directly with manual headers instead of Symfony's built-in
 *         JSON response. And return type should be JSON response as well.
 *         - Suggested: public function list(
 *                          Request $request
 *                      ): JsonResponse
 *                      {
 *                          ...
 *                          return new JsonResponse($responseData);
 *                      }
 *
 *      4. **No Pagination:** This method could return a lot of data.
 *
 *  - Method `send`
 *      1. **HTTP Method:** Consider using POST instead of GET
 *
 *      2. **Validation:** Using `!$text` can be problematic as it treats "0" as empty.
 *         - Suggested: Use more strict comparison `$text === ''`
 *
 *      3. **No Error Handling:** There is no error handling to handle dispatch problems
 *         - Suggested: Use try/catch block
 *              try {
 *                  $bus->dispatch(new SendMessage($text));
 *                  return new JsonResponse(['status' => 'Successfully sent'], Response::HTTP_NO_CONTENT);
 *              } catch (\Exception $e) {
 *                  return new JsonResponse(['error' => 'Failed to send message'], Response::HTTP_INTERNAL_SERVER_ERROR);
 *              }
 */

declare(strict_types=1);

namespace App\Controller;

use App\Message\SendMessage;
use App\Repository\MessageRepository;
use Controller\MessageControllerTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @see MessageControllerTest
 * TODO: review both methods and also the `openapi.yaml` specification
 *       Add Comments for your Code-Review, so that the developer can understand why changes are needed.
 */
class MessageController extends AbstractController
{
//    /**
//     * TODO: cover this method with tests, and refactor the code (including other files that need to be refactored)
//     */
//    #[Route('/messages')]
//    public function list(Request $request, MessageRepository $messages): Response
//    {
//        $messages = $messages->by($request);
//
//        foreach ($messages as $key=>$message) {
//            $messages[$key] = [
//                'uuid' => $message->getUuid(),
//                'text' => $message->getText(),
//                'status' => $message->getStatus(),
//            ];
//        }
//
//        return new Response(json_encode([
//            'messages' => $messages,
//        ], JSON_THROW_ON_ERROR), headers: ['Content-Type' => 'application/json']);
//    }

    public function __construct(
        private MessageRepository $messageRepository
    ) {
    }

    #[Route('/messages', methods: ['GET'])]
    public function list(
        Request $request
    ): JsonResponse
    {
        $messageEntities = $this->messageRepository->by($request);

        $responseData = [
            'messages' => array_map(function($message) {
                return [
                    'uuid' => $message->getUuid(),
                    'text' => $message->getText(),
                    'status' => $message->getStatus(),
                ];
            }, $messageEntities),
        ];

        return new JsonResponse($responseData);
    }


    #[Route('/messages/send', methods: ['GET'])]
    public function send(Request $request, MessageBusInterface $bus): Response
    {
        /**
         * Cast query parameter to string to ensure type safety.
         * This fixes PHPStan error: "Parameter #1 $text of class App\Message\SendMessage
         *      constructor expects string, float|int<min, -1>|int<1, max>|string|true given."
         */
        $text = (string)$request->query->get('text');
        
        if (!$text) {
            return new Response('Text is required', 400);
        }

        $bus->dispatch(new SendMessage($text));
        
        return new Response('Successfully sent', 204);
    }
}