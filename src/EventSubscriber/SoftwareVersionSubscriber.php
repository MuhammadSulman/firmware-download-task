<?php

namespace App\EventSubscriber;

use App\Entity\SoftwareVersion;
use App\Repository\SoftwareVersionRepository;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SoftwareVersionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SoftwareVersionRepository $repository,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['ensureSingleLatest'],
            BeforeEntityUpdatedEvent::class => ['ensureSingleLatest'],
        ];
    }

    public function ensureSingleLatest($event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof SoftwareVersion) {
            return;
        }

        if ($entity->isLatest()) {
            $this->repository->clearLatestForProductLine($entity->getName());
        }
    }
}
