# Tao Sync extension

The purpose of this extension is to synchronize a local server against a remote.
All data and results can be synchronized.

To take care about data security all http request required by synchronisation are signed following oauth2 standart.
All data are encrypted.


## Installation

You can add the Tao Sync as a standard TAO extension to your current TAO instance.

```bash
 $ composer require oat-sa/extension-tao-sync
```

## Synchronization

#### 1. Data synchronisation


To prepare delivery execution, synchronization require to fetch several object $TYPE from data server:
* test-center
* administrator
* proctor
* test-taker
* eligibility
* delivery

To configure the amount of data by request, use `chunkSize` parameter in `taoSync/syncService` config

```bash
 $ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\synchronisation\SynchronizeData' [--type=$TYPE]
```

_Note_: 
> The delivery has an exported package sent to server to be synchronized to be compiled

### 2. Result synchronisation

Once client server has delivery execution result, a script synchronize (send) result to central server.
Only finished delivery execution are sent.
Results have been submitted only once.
When a delivery execution is send, sync history will be updated to log the action.
`deleteAfterSend` parameter can be set to true to delete results after synchronisation.

To configure the amount of data by request, use `chunkSize` parameter in `taoSync/resultService` config


```bash
 $ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\synchronisation\SynchronizeResult'
```

### 3. Synchronize All

To synchronize data and results in the same time:

```bash
 $ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\synchronisation\SynchronizeAll'
```

## Oauth credentials

### 1. Generate credentials to allow user to connect to platform

This command create a consumer with oauth credential and associate an user to authenticate connection

```bash
 $ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\oauth\GenerateOauthCredentials'
```

The output will contain:
- key
- secret
- tokenUrl

_Note_: 
> Add `-cmd` flag to this command to have the command to run on client server

### 2. Import Oauth credentials to client server

To import the previously created consumer, connect to client server and enter the following command:

```bash
 $ sudo -u www-data php index.php 'oat\taoSync\scripts\tool\oauth\ImportOauthCredentials' -k $key -s $secret -tu $tokenUrl -u $rootUrl
```

Arguments come from created consumer. The $rootUrl is the domain name of host server

### 3. Scope synchronisation to a test center identifier

To scope synchronisation to test center orgId property the platform needs to register a testcenter property. 
SyncService has also to register new synchronizers to process by organisation id.
```bash
 $ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\RegisterSyncServiceByOrgId'
```

_Note_: 
> The test center organisation id is: http://www.taotesting.com/ontologies/synchro.rdf#organisationId



