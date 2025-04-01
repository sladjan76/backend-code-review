<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * Code Review:
 *
 * 1. **Use parameter binding to prevent SQL injection
 *    - Suggested:
 *          ->createQuery(
 *              "SELECT m FROM App\Entity\Message m WHERE m.status = :status"
 *          )
 *          ->setParameter('status', $status)
 *
 * 2. **Method name** Instead of `by` use more intuitive name for method.
 *    - Suggested: public function filteredByStatus()
 */

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Returns messages filtered by status if provided
     *
     * @return Message[]
     */
    public function by(Request $request): array
    {
        $status = $request->query->get('status');
        
        if ($status) {
            /** @var Message[] $messages */ // Tell PHPStan that the result will be an array of Message objects
            $messages = $this->getEntityManager()
                ->createQuery(
                    sprintf("SELECT m FROM App\Entity\Message m WHERE m.status = '%s'", $status)
                )
                ->getResult();
        } else {
            $messages = $this->findAll();
        }
        
        return $messages;
    }
}
