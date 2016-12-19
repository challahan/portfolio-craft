# Flysystem Adapter for Google Cloud

This adapter works around a Google Cloud API issue, which does not support deleting
multiple files with one request. To work around this, adapter lists all files and
deletes them one by one, taking more time but at least making it work.

## Installation

```bash
composer require pixelandtonic/flysystem-google-cloud
```

## Usage

```php
use Aws\S3\S3Client;
use League\Flysystem\GoogleCloud\GoogleCloudAdapter;
use League\Flysystem\Filesystem;

$client = S3Client::factory(array(
    'key'      => '[your key]',
    'secret'   => '[your secret]',
    'region'   => '[aws-region]',
    'base_url' => 'https://storage.googleapis.com'
));

$adapter = new GoogleCloudAdapter($client, 'bucket-name', 'optional-prefix');

$filesystem = new Filesystem($adapter);
```
