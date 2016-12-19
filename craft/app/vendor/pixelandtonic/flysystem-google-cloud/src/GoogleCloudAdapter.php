<?php

namespace League\Flysystem\GoogleCloud;

use League\Flysystem\AwsS3v2\AwsS3Adapter;

class GoogleCloudAdapter extends AwsS3Adapter
{
    /**
     * {@inheritdoc}
     */
    public function deleteDir($path)
    {
        $path = rtrim($this->applyPathPrefix($path), '/').'/';

        $success = true;

        $objects = $this->listContents($path);
        $directoryList = [];

        foreach ($objects as $object)
        {
            if ($object['type'] == 'dir')
            {
                $directoryList[] = $object['path'];
            }
            else
            {
                $success = $success && $this->delete($object['path']);
            }
        }

        foreach ($directoryList as $directoryPath)
        {
            $directoryPath = rtrim($this->applyPathPrefix($directoryPath), '/').'/';

            // This operation can return false as well, if the directory was not
            // an object itself but only part of the path for the files.
            $this->delete($directoryPath);
        }

        return $success;
    }
}
