<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Manager;

use AppBundle\Entity\CommentBase;
use AppBundle\Entity\CommentRepositoryBase;
use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantComment;
use AppBundle\Entity\ParticipantPaymentEvent;
use AppBundle\Entity\Participation;
use AppBundle\Entity\ParticipationComment;
use AppBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class Payment Manager
 *
 * @package AppBundle\Manager
 */
class PaymentManager
{

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Price event repository
     *
     * @var CommentRepositoryBase
     */
    protected $repository;

    /**
     * The user currently logged in
     *
     * @var User|null
     */
    protected $user = null;

    /**
     * CommentManager constructor.
     *
     * @param EntityManagerInterface $em
     * @param TokenStorage           $tokenStorage
     */
    public function __construct(EntityManagerInterface $em, TokenStorage $tokenStorage = null)
    {
        $this->em = $em;
        if ($tokenStorage) {
            $this->user = $tokenStorage->getToken()->getUser();
        }
        $this->repository = $this->em->getRepository('AppBundle:ParticipantPaymentEvent');
    }

    /**
     * Set price for multiple participants
     *
     * @param array     $participants List of participants where this operation should be applied to
     * @param int|float $value        New price
     * @param string    $description  Description for change
     * @return array|ParticipantPaymentEvent[]
     */
    public function setPrice(array $participants, $value, string $description)
    {
        $em    = $this->em;
        /** @var Participant $participant */

        return $em->transactional(
            function (EntityManager $em) use ($participants, $value, $description) {
                $events = [];
                /** @var Participant $participant */
                foreach ($participants as $participant) {
                    $participant->setPrice($value);
                    $em->persist($participant);
                    $event = ParticipantPaymentEvent::createPriceSetEvent(
                        $this->user, $value, $description
                    );
                    $participant->addPaymentEvent($event);
                    $em->persist($event);
                    $events[] = $event;
                }
                $em->flush();
                return $events;
            }
        );
    }

    /**
     * Convert value in euro to value in euro cents
     *
     * @param   string|float|int $priceInEuro Value in euros, separated by comma
     * @return float|int
     */
    public static function convertEuroToCent($priceInEuro)
    {
        if (empty($priceInEuro)) {
            return 0;
        } elseif (preg_match('/^(\d+)(?:[,.]{0,1})(\d*)$/', $priceInEuro, $priceParts)) {
            $euros = $priceParts[1] ?: 0;
            $cents = $priceParts[2] ?: 0;

            return ($euros * 100 + $cents);
        } else {
            throw new \InvalidArgumentException('Failed to convert "' . $priceInEuro . '" to euro cents');
        }
    }

    /**
     * Get all payment events for transmitted @see Participation
     *
     * @param Participation $participation Desired participation
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipation(Participation $participation)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->innerJoin('a.participation', 'p')
           ->andWhere($qb->expr()->eq('p.pid', $participation->getPid()))
           ->orderBy('e.createdAt', 'DESC');
        $result = $qb->getQuery()->execute();
        return $result;
    }

    /**
     * Get all payment events for transmitted @see Participant
     *
     * @param Participant $participant Desired participant
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipant(Participant $participant)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->andWhere($qb->expr()->eq('a.aid', $participant->getAid()))
           ->orderBy('e.createdAt', 'DESC');
        $result = $qb->getQuery()->execute();
        return $result;
    }

    /**
     * Get all payment events for transmitted @see Participants
     *
     * @param array|Participant[] $participants List of Participants
     * @return array|ParticipantPaymentEvent[]
     */
    public function paymentHistoryForParticipantList(array $participants)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->orderBy('e.createdAt', 'DESC');
        /** @var Participant $participant */
        foreach ($participants as $participant) {
            $qb->orWhere($qb->expr()->eq('e.participant', $participant->getAid()));
        }
        $result = $qb->getQuery()->execute();
        return $result;
    }

    /**
     * Payment for a single participant received
     *
     * @param Participant $participant Participant on which operation will be applied to
     * @param int|float   $value       Numeric value of the payment event - Note that a negative sign indicates a
     *                                 reduction of price which still needs to be payed, a positive indicates a reverse
     *                                 booking, which results in increase of the value which still needs to be payed
     * @param string      $description Info text for payment event
     * @return ParticipantPaymentEvent New created payment event
     */
    public function paymentForParticipant(Participant $participant, $value, string $description)
    {
        $event = new ParticipantPaymentEvent(
            $this->user, ParticipantPaymentEvent::EVENT_TYPE_PAYMENT, $value, $description
        );
        $participant->addPaymentEvent($event);
        $this->em->persist($event);
        $this->em->flush($event);
        return $event;
    }

    /**
     * Get amount of money which still needs to be paid for a single @see Participant
     *
     * @param Participant $participant Target participant
     * @return int|null                Value needed to be payed
     */
    public function toPayValueForParticipant(Participant $participant)
    {
        $fullHistory  = $this->paymentHistoryForParticipant($participant);
        $currentPrice = null;
        $payments     = [];

        /** @var ParticipantPaymentEvent $event */
        foreach ($fullHistory as $event) {
            if ($event->isPricePaymentEvent()) {
                $payments[] = $event;
            } elseif ($event->isPriceSetEvent() && $currentPrice === null) {
                $currentPrice = $event->getValue();
            }
        }
        if ($currentPrice === null) {
            //no price set event for current participant present, so default price of event is used
            $currentPrice = $participant->getEvent()->getPrice();
        }

        if ($currentPrice === null) {
            return 0;
        }

        /** @var ParticipantPaymentEvent $payment */
        foreach ($payments as $payment) {
            $currentPrice += $payment->getValue();
        }

        return $currentPrice;
    }
}