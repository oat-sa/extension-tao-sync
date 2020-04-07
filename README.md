# TAO Sync Extension

![TAO Logo](https://github.com/oat-sa/taohub-developer-guide/raw/master/resources/tao-logo.png)

![GitHub](https://img.shields.io/github/license/oat-sa/extension-tao-sync.svg)
![GitHub release](https://img.shields.io/github/release/oat-sa/extension-tao-sync.svg)
![GitHub commit activity](https://img.shields.io/github/commit-activity/y/oat-sa/extension-tao-sync.svg)

> The purpose of this extension is to synchronize a local and a remote server. 

All types of data and results can be synchronized. All HTTP requests are required to be signed following the OAuth 2 standard, also are all data encrypted.

**Important note: While this article uses American spelling, some of the commands are in British spelling for historical reasons, e. g. `synchronisation` instead of `synchronization`!**

## Installation instructions

These instructions assume that you already have a TAO installation on your system. If you don't, go to
[package/tao](https://github.com/oat-sa/package-tao) and follow the installation instructions.


Add the extension to your TAO composer and to the autoloader:
```bash
composer require oat-sa/extension-tao-sync
```

Install the extension on the CLI from the project root:

**Linux:**
```bash
sudo php tao/scripts/installExtension oat-sa/extension-tao-sync
```

**Windows:**
```bash
php tao\scripts\installExtension oat-sa/extension-tao-sync
```

As a system administrator you can also install it through the TAO Extension Manager:
- Settings (the gears on the right-hand side of the menu) -> Extension manager
- Select _taoSync_ on the right-hand side, check the box and hit _install_

## Synchronization

### Data synchronization

In preparation of a delivery execution, the synchronization process needs to fetch data objects of the following types from the central server:

* `test-center`
* `administrator`
* `proctor`
* `test-taker`
* `eligibility`
* `delivery`

Set the `chunkSize` parameter in the `taoSync/syncService` configuration to define the amount of data per request:

```bash
# $type in this context is any of the type above, e. g. test-center
$ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\synchronisation\SynchronizeData' [--type=$type]
```

_Note: The delivery has an exported package sent to server to be synchronized to be compiled._

### Result synchronization

Once the Client Server has the delivery result, a script sends it to the Central Server. Only completed delivery executions will be sent.
Results have been submitted only once.
When a delivery execution is send, sync history will be updated to log the action.
`deleteAfterSend` parameter can be set to true to delete results after synchronization.

To configure the amount of data by request, use `chunkSize` parameter in `taoSync/resultService` config


```bash
$ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\synchronisation\SynchronizeResult'
```

### 3. Synchronize All

To synchronize data and results in the same time:

```bash
$ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\synchronisation\SynchronizeAll'
```

## OAuth credentials

### Generating credentials to allow a user to connect to the platform

This command creates a consumer with OAuth credentials and associates a user to authenticate connection

```bash
$ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\OAuth\GenerateOAuthCredentials'
```

The output will contain:
- key
- secret
- tokenUrl

_Note: Add `-cmd` flag to this command to execute it on the Client Server._

### Importing OAuth credentials to Client Server

To import the previously created consumer, connect to the Client Server and execute the following command:

```bash
$ sudo -u www-data php index.php 'oat\taoSync\scripts\tool\OAuth\ImportOAuthCredentials' -k $key -s $secret -tu $tokenUrl -u $rootUrl
```

Arguments come from created consumer. The `$rootUrl` is the domain name of the host server.

### Scope synchronization to a test center identifier

To scope synchronization to test center orgId property the platform needs to register a test-center property. 
SyncService has also to register new synchronizers to process by organisation id.
```bash
$ sudo -u www-data php index.php '\oat\taoSync\scripts\tool\RegisterSyncServiceByOrgId'
```

_Note_ The test center organization id is http://www.taotesting.com/ontologies/synchro.rdf#organisationId_

