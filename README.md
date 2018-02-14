extension-tao-sync
========================

The purpose of this extension is to synchronize a local server against a remote.
All data in test centers can be syncrhonized.

This is a two step process.

1°) Data synchronisation
-
To prepare delivery execution, synchronization require to fetch several object $TYPE from data server:
* test-center
* administrator
* proctor
* test-taker
* eligibility
* delivery

To configure the amount of data by request, use `chunkSize` parameter in `taoSync/syncService` config

`php index.php '\oat\taoSync\scripts\tool\SynchronizeData' [--type=$TYPE]`

NB: The delivery has an exported package sent to server to be synchronized to be compiled

2°) Data synchronisation
-

Once client server has delivery execution result, a script synchronize (send) result to central server.
Only finished delivery execution are sent.
Results have been submitted only once.
When a delivery execution is send, sync history will be updated to log the action.
`deleteAfterSend` parameter can be set to true to delete results after synchronisation.

To configure the amount of data by request, use `chunkSize` parameter in `taoSync/resultService` config


`php index.php '\oat\taoSync\scripts\tool\SynchronizeResult'`

