<?php
    namespace App\EventListener;
    
    use Symfony\Component\HttpFoundation\JsonResponse;

    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpKernel\Event\ExceptionEvent;
    use Symfony\Component\HttpKernel\KernelEvents;
    use Symfony\Component\Security\Core\Exception\AccessDeniedException;

    class AccessDeniedListener implements EventSubscriberInterface
    {
        public static function getSubscribedEvents(): array
        {
            return [
                // the priority must be greater than the Security HTTP
                // ExceptionListener, to make sure it's called before
                // the default exception listener
                KernelEvents::EXCEPTION => ['onKernelException', 2],
            ];
        }
    
        public function onKernelException(ExceptionEvent $event): void
        {
            $exception = $event->getThrowable();
            if (!$exception instanceof AccessDeniedException) {
                return;
            }
    
            // $event->setResponse(new Response(null, 403));
            $response = new JsonResponse(
                    [   'status' => false,
                        'message' => 'Access Denied',
                    ],
                    JsonResponse::HTTP_FORBIDDEN
            );
    
            // Sends the modified response object to the event
            $event->setResponse($response);
    
            // or stop propagation (prevents the next exception listeners from being called)
            //$event->stopPropagation();
        }
    }
