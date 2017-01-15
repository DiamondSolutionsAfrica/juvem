<?php
namespace AppBundle\EventListeners;

use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param null|LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->logger === null) {
            return;
        }
        $exception        = $event->getException();
        $flattenException = FlattenException::create($exception);
        $this->logger->error('Stack trace');
        foreach ($flattenException->getTrace() as $trace) {
            $traceMessage = sprintf('  at %s line %s', $trace['file'], $trace['line']);
            $this->logger->error($traceMessage);
        }
    }
}