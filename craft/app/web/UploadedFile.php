<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web;

/**
 * UploadedFile represents the information for an uploaded file.
 *
 * Call [[getInstance]] to retrieve the instance of an uploaded file, and then use [[saveAs]] to save it on the
 * server. You may also query other information about the file, including [[name]], [[tempName]], [[type]],
 * [[size]] and [[error]].
 *
 * @property string $name          The original name of the file being uploaded.
 * @property string $tempName      The path of the uploaded file on the server. Note, this is a temporary file which
 *                                 will be automatically deleted by PHP after the current request is processed.
 * @property string $type          The MIME-type of the uploaded file (such as "image/gif"). Since this MIME type is not
 *                                 checked on the server side, do not take this value for granted. Instead, use
 *                                 [[\yii\helpers\FileHelper::getMimeType()]] to determine the exact MIME type.
 * @property int    $size          The actual size of the uploaded file in bytes.
 * @property int    $error         The error code.
 * @property bool   $hasError      Whether there is an error with the uploaded file. Check [[error]] for the
 *                                 detailed error code information.
 * @property string $extensionName The file extension name for [[name]]. The extension name does not include the dot
 *                                 character. An empty string is returned if [[name]] does not have an extension
 *                                 name.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UploadedFile extends \yii\web\UploadedFile
{
    // Public Methods
    // =========================================================================

    /**
     * Returns an instance of the specified uploaded file.  The name can be a plain string or a string like an array
     * element (e.g. 'Post[imageFile]', or 'Post[0][imageFile]').
     *
     * @param string $name The name of the file input field.
     *
     * @return UploadedFile|null The instance of the uploaded file. null is returned if no file is uploaded for the
     *                           specified name.
     */
    public static function getInstanceByName($name)
    {
        $name = static::_normalizeName($name);

        return parent::getInstanceByName($name);
    }

    /**
     * Returns an array of instances starting with specified array name.
     *
     * If multiple files were uploaded and saved as 'Files[0]', 'Files[1]', 'Files[n]'..., you can have them all by
     * passing 'Files' as array name.
     *
     * @param string  $name                  The name of the array of files
     * @param boolean $lookForSingleInstance If set to true, will look for a single instance of the given name.
     *
     * @return UploadedFile[] The array of UploadedFile objects. Empty array is returned if no adequate upload was
     *                        found. Please note that this array will contain all files from all subarrays regardless
     *                        how deeply nested they are.
     */
    public static function getInstancesByName($name, $lookForSingleInstance = true)
    {
        $name = static::_normalizeName($name);
        $instances = parent::getInstancesByName($name);

        if (!$instances && $lookForSingleInstance) {
            $singleInstance = parent::getInstanceByName($name);

            if ($singleInstance) {
                $instances[] = $singleInstance;
            }
        }

        return $instances;
    }

    // Private Methods
    // =========================================================================

    /**
     * Swaps dot notation for the normal format.
     *
     * ex: fields.assetsField => fields[assetsField]
     *
     * @param string $name The name to normalize.
     *
     * @return string
     */
    private static function _normalizeName($name)
    {
        if (($pos = strpos($name, '.')) !== false) {
            // Convert dot notation to the normal format ex: fields.assetsField => fields[assetsField]
            $name = substr($name, 0, $pos).'['.str_replace('.', '][',
                    substr($name, $pos + 1)).']';
        }

        return $name;
    }
}
