<?php

declare(strict_types=1);

namespace oat\taoSync\migrations;

use common_report_Report as Report;
use Doctrine\DBAL\Schema\Schema;
use oat\generis\model\data\event\ResourceDeleted;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoSync\model\EntityChecksumCacheService;

final class Version202107050958104040_taoSync extends AbstractMigration
{

    public function getDescription(): string
    {
        return sprintf(
            'Subscribe %s::%s() to %s event',
            EntityChecksumCacheService::class,
            'entityDeleted',
            ResourceDeleted::class
        );
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->attach(ResourceDeleted::class, [EntityChecksumCacheService::class, 'entityDeleted']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Subscribed %s::%s() to %s event',
                    EntityChecksumCacheService::class,
                    'entityDeleted',
                    ResourceDeleted::class
                )
            )
        );
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getEventManager();
        $eventManager->detach(ResourceDeleted::class, [EntityChecksumCacheService::class, 'entityDeleted']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        $this->addReport(
            Report::createSuccess(
                sprintf(
                    'Unsubscribed %s::%s() from %s event',
                    EntityChecksumCacheService::class,
                    'entityDeleted',
                    ResourceDeleted::class
                )
            )
        );
    }

    private function getEventManager(): EventManager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(EventManager::class);
    }
}
