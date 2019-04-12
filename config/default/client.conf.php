<?php

use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\client\ConnectionStatsHandler;

return new SynchronisationClient([
    SynchronisationClient::OPTION_STATS_HANDLER => new ConnectionStatsHandler(),
    SynchronisationClient::OPTION_EXPECTED_UPLOAD_SPEED => 1,
    SynchronisationClient::OPTION_EXPECTED_DOWNLOAD_SPEED => 1,
]);
