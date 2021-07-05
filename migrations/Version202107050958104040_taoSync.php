<?php

declare(strict_types=1);

namespace oat\taoSync\migrations;

use common_report_Report as Report;
use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoSync\model\EntityChecksumCacheService;

final class Version202107050958104040_taoSync extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register ' . EntityChecksumCacheService::class;
    }

    public function up(Schema $schema): void
    {
        $this->getServiceManager()->register(
            EntityChecksumCacheService::SERVICE_ID,
            new EntityChecksumCacheService(
                [
                    EntityChecksumCacheService::OPTION_PERSISTENCE => 'default_kv',
                ]
            )
        );

        $this->addReport(
            Report::createSuccess('Registered ' . EntityChecksumCacheService::class)
        );
    }

    public function down(Schema $schema): void
    {
        $this->addReport(
            $this->getServiceManager()->unregister(EntityChecksumCacheService::SERVICE_ID)
                ? Report::createSuccess('Unregistered ' . EntityChecksumCacheService::class)
                : Report::createFailure('Failed to unregister ' . EntityChecksumCacheService::class)
        );
    }
}
