<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\io;

/**
 * Interface ZipInterface
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface ZipInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @param $sourceFolder
     * @param $destZip
     *
     * @return mixed
     */
    public function zip($sourceFolder, $destZip);

    /**
     * @param $sourceZip
     * @param $destFolder
     *
     * @return mixed
     */
    public function unzip($sourceZip, $destFolder);

    /**
     * Will add either a file or a folder to an existing zip file.  If it is a folder, it will add the contents
     * recursively.
     *
     * @param string $sourceZip  The zip file to be added to.
     * @param string $filePath   A file or a folder to add.  If it is a folder, it will recursively add the contents of
     *                           the folder to the zip.
     * @param string $basePath   The root path of the file(s) to be added that will be removed before adding.
     * @param string $pathPrefix A path to be prepended to each file before it is added to the zip.
     *
     * @return boolean
     */
    public function add($sourceZip, $filePath, $basePath, $pathPrefix = null);
}
