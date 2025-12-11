<?php

declare(strict_types=1);
require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\Filesystem;
use League\Flysystem\GoogleCloudStorage\GoogleCloudStorageAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;

const SERVICEACCOUNT = 'spotifygcssa@spotifygcs.iam.gserviceaccount.com';
const BUCKET_NAME = 'sp-content-ingestion-mp-upload-eglencefabrikasi';
const PROJECT_ID = 'spotifygcs';
const KEYFILE = "spotifygcs-4f1970a20be5.json";

class gcs
{
    public $storageClient;
    public $bucket;

    function __construct()
    {

        $this->storageClient = new StorageClient([
            'keyFilePath' => dirname(dirname(__FILE__)) . "/keys/" . KEYFILE,
        ]);
        $this->bucket = $this->storageClient->bucket(BUCKET_NAME);
    }
    function deleteFile($gcsfilePath)
    {
        $response = true;
        try {
            $object = $this->bucket->object($gcsfilePath);
            $object->delete();
        } catch (exception $ex) {
            $response = false;
        }
        return $response;
    }

    function getBucketFiles () {
        foreach ($this->bucket->objects() as $object) {
            echo '<pre>';
            printf('Object: %s' . PHP_EOL, $object->name() );
          }
    }

    function upload($folderPath)
    {
        $response = true;
        $files = $this->scanAllDir($folderPath);
        try {
            foreach ($files as $file) {
                $object = $this->bucket->upload(
                    fopen((__DIR__ . '/' . $folderPath . '/' . $file), 'r'),
                    ['name' => "test/" . $folderPath . "/" . $file]
                );
            }
        } catch (exception $ex) {
            $response = false;
        }
        return $response;
    }

    function scanAllDir($dir)
    {
        $result = [];
        foreach (scandir($dir) as $filename) {
            if ($filename[0] === '.') continue;
            $filePath = $dir . '/' . $filename;
            if (is_dir($filePath)) {
                foreach ($this->scanAllDir($filePath) as $childFilename) {
                    $result[] = $filename . '/' . $childFilename;
                }
            } else {
                $result[] = $filename;
            }
        }
        return $result;
    }
}
