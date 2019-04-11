<?php

use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\client\ConnectionStatsHandler;

return new SynchronisationClient([
    SynchronisationClient::OPTION_STATS_HANDLER => new ConnectionStatsHandler()
]);
