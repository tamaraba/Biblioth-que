<?php
use ShortPixel\ShortPixelLogger\ShortPixelLogger as Log;

class ShortPixelMetaFacade {
    const MEDIA_LIBRARY_TYPE = 1;
    const CUSTOM_TYPE = 2;

    private $ID;
    private $type;
    private $meta;
    private $spMetaDao;
    private $rawMeta;

    public function __construct($ID) {
        if(strpos($ID, 'C-') === 0) {
            $this->ID = substr($ID, 2);
            $this->type = self::CUSTOM_TYPE;
        } else {
            $this->ID = $ID;
            $this->type = self::MEDIA_LIBRARY_TYPE;
        }
        $this->spMetaDao = new ShortPixelCustomMetaDao(new WpShortPixelDb());
    }

    public static function getNewFromRow($item) {
        return new ShortPixelMetaFacade("C-" . $item->id);
    }

    function setRawMeta($rawMeta) {
        if($this->type == self::MEDIA_LIBRARY_TYPE) {
            $this->rawMeta = $rawMeta;
            $this->meta = self::rawMetaToMeta($this->ID, $rawMeta);
        }
    }

    function getMeta($refresh = false) {
        if($refresh || !isset($this->meta)) {
            if($this->type == self::CUSTOM_TYPE) {
                $this->meta = $this->spMetaDao->getMeta($this->ID);
            } else {
                $rawMeta = $this->sanitizeMeta(wp_get_attachment_metadata($this->ID));
                $this->meta = self::rawMetaToMeta($this->ID, $rawMeta);
                $this->rawMeta = $rawMeta;
            }
        }
        return $this->meta;
    }

    private static function rawMetaToMeta($ID, $rawMeta) {
        $path = get_attached_file($ID);
        return new ShortPixelMeta(array(
                    "id" => $ID,
                    "name" => ShortPixelAPI::MB_basename($path),
                    "path" => $path,
                    "webPath" => (isset($rawMeta["file"]) ? $rawMeta["file"] : null),
                    "thumbs" => (isset($rawMeta["sizes"]) ? $rawMeta["sizes"] : array()),
                    "message" =>(isset($rawMeta["ShortPixelImprovement"]) ? $rawMeta["ShortPixelImprovement"] : null),
                    "png2jpg" => (isset($rawMeta["ShortPixelPng2Jpg"]) ? $rawMeta["ShortPixelPng2Jpg"] : false),
                    "compressionType" =>(isset($rawMeta["ShortPixel"]["type"])
                            ? ($rawMeta["ShortPixel"]["type"] == 'glossy' ? 2 : ($rawMeta["ShortPixel"]["type"] == "lossy" ? 1 : 0) )
                            : null),
                    "thumbsOpt" =>(isset($rawMeta["ShortPixel"]["thumbsOpt"]) ? $rawMeta["ShortPixel"]["thumbsOpt"] : null),
                    "thumbsOptList" =>(isset($rawMeta["ShortPixel"]["thumbsOptList"]) ? $rawMeta["ShortPixel"]["thumbsOptList"] : array()),
                    'excludeSizes' =>(isset($rawMeta["ShortPixel"]["excludeSizes"]) ? $rawMeta["ShortPixel"]["excludeSizes"] : null),
                    "thumbsMissing" =>(isset($rawMeta["ShortPixel"]["thumbsMissing"]) ? $rawMeta["ShortPixel"]["thumbsMissing"] : null),
                    "retinasOpt" =>(isset($rawMeta["ShortPixel"]["retinasOpt"]) ? $rawMeta["ShortPixel"]["retinasOpt"] : null),
                    "thumbsTodo" =>(isset($rawMeta["ShortPixel"]["thumbsTodo"]) ? $rawMeta["ShortPixel"]["thumbsTodo"] : false),
                    "tsOptimized" => (isset($rawMeta["ShortPixel"]["date"]) ? $rawMeta["ShortPixel"]["date"] : false),
                    "backup" => !isset($rawMeta['ShortPixel']['NoBackup']),
                    "status" => (!isset($rawMeta["ShortPixel"]) ? 0
                                 : (isset($rawMeta["ShortPixelImprovement"]) && is_numeric($rawMeta["ShortPixelImprovement"])
                                   && !(   $rawMeta['ShortPixelImprovement'] == 0
                                        && (   isset($rawMeta['ShortPixel']['WaitingProcessing'])
                                            || isset($rawMeta['ShortPixel']['date']) && $rawMeta['ShortPixel']['date'] == '1970-01-01')) ? 2
                                    : (isset($rawMeta["ShortPixel"]["WaitingProcessing"]) ? 1
                                       : (isset($rawMeta["ShortPixel"]['ErrCode']) ? $rawMeta["ShortPixel"]['ErrCode'] : -500)))),
                    "retries" =>(isset($rawMeta["ShortPixel"]["Retries"]) ? $rawMeta["ShortPixel"]["Retries"] : 0),
                ));
    }

    function check() {
        if($this->type == self::CUSTOM_TYPE) {
            $this->meta = $this->spMetaDao->getMeta($this->ID);
            return $this->meta;
        } else {
            return wp_get_attachment_url($this->ID);
        }
    }

    static function sanitizeMeta($rawMeta, $createSPArray = true){
        if(!is_array($rawMeta)) {
            if($rawMeta == '') { return $createSPArray ? array('ShortPixel' => array()) : array(); }
            else {
                $meta = @unserialize($rawMeta);
                if(is_array($meta)) {
                    return $meta;
                }
                return array("previous_meta" => $rawMeta, 'ShortPixel' => array());
            }
        }
        return $rawMeta;
    }

    //  Update MetaData of Image.
    public function updateMeta($newMeta = null, $replaceThumbs = false) {
        if($newMeta) {
            $this->meta = $newMeta;
        }
        if($this->type == self::CUSTOM_TYPE) {
            $this->spMetaDao->update($this->meta);
            if($this->meta->getExtMetaId()) {
                ShortPixelNextGenAdapter::updateImageSize($this->meta->getExtMetaId(), $this->meta->getPath());
            }
        }
        elseif($this->type == ShortPixelMetaFacade::MEDIA_LIBRARY_TYPE) {
            $duplicates = ShortPixelMetaFacade::getWPMLDuplicates($this->ID);
            Log::addDebug('Update Meta Duplicates Query', array($duplicates));
            foreach($duplicates as $_ID) {
                $rawMeta = $this->sanitizeMeta(wp_get_attachment_metadata($_ID));

                if(isset($rawMeta['sizes']) && is_array($rawMeta['sizes'])) {
                    if($replaceThumbs) {
                        $rawMeta['sizes'] = $this->meta->getThumbs();
                    } else {
                        //use this instead of array_merge because we don't want to duplicate numeric keys
                        foreach($this->meta->getThumbs() as $key => $val) {
                            if(!isset($rawMeta['sizes'][$key])) {
                                $rawMeta['sizes'][$key] = $val;
                            }
                        }
                    }
                }

                if(null === $this->meta->getCompressionType()) {
                    unset($rawMeta['ShortPixel']['type']);
                } else {
                    $rawMeta['ShortPixel']['type'] = ShortPixelAPI::getCompressionTypeName($this->meta->getCompressionType());
                }

                if(null === $this->meta->getKeepExif()) {
                    unset($rawMeta['ShortPixel']['exifKept']);
                } else {
                    $rawMeta['ShortPixel']['exifKept'] = $this->meta->getKeepExif();
                }

                if(null === $this->meta->getTsOptimized()) {
                    unset($rawMeta['ShortPixel']['date']);
                } else {
                    $rawMeta['ShortPixel']['date'] = date("Y-m-d H:i:s", strtotime($this->meta->getTsOptimized()));
                }

                //thumbs were processed if settings or if they were explicitely requested
                if(null === $this->meta->getThumbsOpt()) {
                    unset($rawMeta['ShortPixel']['thumbsOpt']);
                    unset($rawMeta['ShortPixel']['thumbsOptList']);
                } else {
                    $rawMeta['ShortPixel']['thumbsOpt'] = $this->meta->getThumbsOpt();
                    $rawMeta['ShortPixel']['thumbsOptList'] = $this->meta->getThumbsOptList();
                }

                //thumbs that were explicitely excluded from settings
                if(null === $this->meta->getExcludeSizes()) {
                    unset($rawMeta['ShortPixel']['excludeSizes']);
                } else {
                    $rawMeta['ShortPixel']['excludeSizes'] = $this->meta->getExcludeSizes();
                }

                $thumbsMissing = $this->meta->getThumbsMissing();
                if(is_array($thumbsMissing) && count($thumbsMissing)) {
                    $rawMeta['ShortPixel']['thumbsMissing'] = $this->meta->getThumbsMissing();
                } else {
                    unset($rawMeta['ShortPixel']['thumbsMissing']);
                }

                if(null === $this->meta->getRetinasOpt()) {
                    unset($rawMeta['ShortPixel']['retinasOpt']);
                } else {
                    $rawMeta['ShortPixel']['retinasOpt'] = $this->meta->getRetinasOpt();
                }
                //if thumbsTodo - this means there was an explicit request to process thumbs for an image that was previously processed without
                // don't update the ShortPixelImprovement ratio as this is only calculated based on main image
                if($this->meta->getThumbsTodo()) {
                    $rawMeta['ShortPixel']['thumbsTodo'] = true;
                } else {
                    if($this->meta->getStatus() > 1) {
                        $rawMeta['ShortPixelImprovement'] = "".round($this->meta->getImprovementPercent(),2);
                    }
                    unset($rawMeta['ShortPixel']['thumbsTodo']);
                }
                if($this->meta->getActualWidth() && $this->meta->getActualHeight()) {
                    $rawMeta['width'] = $this->meta->getActualWidth();
                    $rawMeta['height'] = $this->meta->getActualHeight();
                }
                if(!$this->meta->getBackup()) {
                    $rawMeta['ShortPixel']['NoBackup'] = true;
                }
                if($this->meta->getStatus() !== 1) {
                    unset($rawMeta['ShortPixel']['WaitingProcessing']);
                }
                if($this->meta->getStatus() >= 0) {
                    unset($rawMeta['ShortPixel']['ErrCode']);
                }

                update_post_meta($_ID, '_wp_attachment_metadata', $rawMeta);
                //wp_update_attachment_metadata($_ID, $rawMeta);
                //status and optimization percent in the same time, for sorting purposes :)
                $status = $this->meta->getStatus();
                if($status == 2) {
                    $status += 0.01 * intval($rawMeta['ShortPixelImprovement']);
                }
                update_post_meta($_ID, '_shortpixel_status', number_format($status, 4));

                if($_ID == $this->ID) {
                    $this->rawMeta = $rawMeta;
                }
            } // duplicates loop
        }
    }


    /** Clean meta set by Shortpixel
    * This function only hits with images that were optimized, pending or have an error state.
    */
    public function cleanupMeta($fakeOptPending = false) {
        if($this->type == ShortPixelMetaFacade::MEDIA_LIBRARY_TYPE) {
            if(!isset($this->rawMeta)) {
                $rawMeta = $this->sanitizeMeta(wp_get_attachment_metadata($this->getId()));
            } else {
                $rawMeta = $this->rawMeta;
            }

            if($fakeOptPending && !isset($rawMeta['ShortPixel']['WaitingProcessing'])) {
                return;
            } elseif ($fakeOptPending) {
                unset($rawMeta['ShortPixel']['WaitingProcessing']);
                $rawMeta["ShortPixelImprovement"] = '999';
            } else {
                unset($rawMeta["ShortPixelImprovement"]);
                unset($rawMeta['ShortPixel']);
                unset($rawMeta['ShortPixelPng2Jpg']);
            }

            $this->removeSPFoundMeta();
            unset($this->meta);
            update_post_meta($this->ID, '_wp_attachment_metadata', $rawMeta);
            //wp_update_attachment_metadata($this->ID, $rawMeta);
            $this->rawMeta = $rawMeta;
        } else {
            throw new Exception("Not implemented 1");
        }
    }

    // remove SPFoudnMeta from image. Dirty. @todo <--
    public function removeSPFoundMeta()
    {
      if($this->type == ShortPixelMetaFacade::MEDIA_LIBRARY_TYPE) {
          if(!isset($this->rawMeta)) {
              $rawMeta = $this->sanitizeMeta(wp_get_attachment_metadata($this->getId()));
          } else {
              $rawMeta = $this->rawMeta;
          }
          if (isset($rawMeta['sizes'])) // search for custom sizes set by SP.
          {
            foreach($rawMeta['sizes'] as $size => $data)
            {
                if (strpos($size, ShortPixelMeta::FOUND_THUMB_PREFIX) !== false)
                {
                  unset($rawMeta['sizes'][$size]);
                  Log::addDebug('Unset sp-found- size' . $size);
                }
            }
          }
          $this->rawMeta = $rawMeta;
          update_post_meta($this->ID, '_wp_attachment_metadata', $rawMeta);
      }
    }

    function deleteMeta() {
        if($this->type == self::CUSTOM_TYPE) {
            throw new Exception("Not implemented 1");
        } else {
            unset($this->rawMeta['ShortPixel']);
            update_post_meta($this->ID, '_wp_attachment_metadata', $this->rawMeta);
            //wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }
    }

    function deleteAllSPMeta() {
        if($this->type == self::CUSTOM_TYPE) {
            throw new Exception("Not implemented 1");
        } else {
            unset($this->rawMeta["ShortPixelImprovement"]);
            unset($this->rawMeta['ShortPixel']);
            unset($this->rawMeta['ShortPixelPng2Jpg']);
            unset($this->meta);
            update_post_meta($this->ID, '_wp_attachment_metadata', $this->rawMeta);
            //wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }
    }

    function incrementRetries($count = 1, $errorCode = ShortPixelAPI::ERR_UNKNOWN, $errorMessage = '') {
        if($this->type == self::CUSTOM_TYPE) {
            $this->meta->setRetries($this->meta->getRetries() + $count);
        } else {
            if(!isset($this->rawMeta['ShortPixel'])) {$this->rawMeta['ShortPixel'] = array();}
            $this->rawMeta['ShortPixel']['Retries'] = isset($this->rawMeta['ShortPixel']['Retries']) ? $this->rawMeta['ShortPixel']['Retries'] + $count : $count;
            $this->meta->setRetries($this->rawMeta['ShortPixel']['Retries']);
        }
        $this->setError($errorCode, $errorMessage);
    }

    function setWaitingProcessing($status = true) {
        if($status) {
            $this->meta->setStatus(1);
        }
        if($this->type == self::CUSTOM_TYPE) {
            $this->updateMeta();
        } else {
            if($status) {
                if(!isset($this->rawMeta['ShortPixel']['WaitingProcessing']) || !$this->rawMeta['ShortPixel']['WaitingProcessing']) {
                    self::optimizationStarted($this->getId());
                }
                $this->rawMeta['ShortPixel']['WaitingProcessing'] = true;
                unset($this->rawMeta['ShortPixel']['ErrCode']);
            } else {
                unset($this->rawMeta['ShortPixel']['WaitingProcessing']);
            }
            update_post_meta($this->ID, '_wp_attachment_metadata', $this->rawMeta);
            //wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }
    }

    function setError($errorCode, $errorMessage) {
        $this->meta->setMessage(__('Error','shortpixel-image-optimiser') . ': <i>' . $errorMessage . '</i>');
        $this->meta->setStatus($errorCode);
        if($this->type == self::CUSTOM_TYPE) {
            if($errorCode == ShortPixelAPI::ERR_FILE_NOT_FOUND) {
                $this->spMetaDao->delete($this->meta);
            } else {
                $this->updateMeta();
            }
        } else {
            $this->rawMeta['ShortPixelImprovement'] = $this->meta->getMessage();
            $this->rawMeta['ShortPixel']['ErrCode'] = $errorCode;
            unset($this->rawMeta['ShortPixel']['WaitingProcessing']);
            update_post_meta($this->ID, '_wp_attachment_metadata', $this->rawMeta);
            //wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }
    }

    function setMessage($message) {
        $this->meta->setMessage($message);
        $this->meta->setStatus(-1);
        if($this->type == self::CUSTOM_TYPE) {
            $this->spMetaDao->update($this->meta);
        } else {
            $this->rawMeta['ShortPixelImprovement'] = $this->meta->getMessage();
            unset($this->rawMeta['ShortPixel']['WaitingProcessing']);
            update_post_meta($this->ID, '_wp_attachment_metadata', $this->rawMeta);
            //wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }
    }

    public static function getHomeUrl() {
        //trim is because we found a site set up with a tab, like this: https://modernpeasantcooking.com\t
        return trailingslashit((function_exists("is_multisite") && is_multisite()) ? trim(network_site_url("/")) : trim(home_url()));
    }

    //this is in test
    public static function getHomeUrl2() {
        return trailingslashit(ShortPixelTools::commonPrefix(self::getHomeUrl(), content_url()));
    }

    /**
     * @param $id
     * @return false|string
     * @throws Exception
     */
    public static function safeGetAttachmentUrl($id) {
        $attURL = wp_get_attachment_url($id);
        Log::addDebug('Attachment URL - safeGotten - ' . $attURL);
        if(!$attURL || !strlen($attURL)) {
            throw new Exception("Post metadata is corrupt (No attachment URL for $id)", ShortPixelAPI::ERR_POSTMETA_CORRUPT);
        }
        if ( !parse_url($attURL, PHP_URL_SCHEME) ) {//no absolute URLs used -> we implement a hack
           return self::getHomeUrl() . ltrim($attURL,'/');//get the file URL
        }
        else {
            return $attURL;//get the file URL
        }
    }

    public function getURLsAndPATHs($processThumbnails, $onlyThumbs = false, $addRetina = true, $excludeSizes = array(), $includeOptimized = false) {
        $sizesMissing = array();
        $fs = new \ShortPixel\FileSystemController();

        if($this->type == self::CUSTOM_TYPE) {
            $meta = $this->getMeta();

            //fix for situations where site_url is lala.com/en and home_url is lala.com - if using the site_url will get a duplicated /en in the URL
            $homeUrl = self::getHomeUrl();
            $urlList[] = self::replaceHomePath($meta->getPath(), $homeUrl);

            $filePaths[] = $meta->getPath();
        } else {
            $path = get_attached_file($this->ID);//get the full file PATH
            $fsFile = $fs->getFile($path);
            $mainExists = apply_filters('shortpixel_image_exists', file_exists($path), $path, $this->ID);
            try
            {
              $predownload_url = $url = self::safeGetAttachmentUrl($this->ID); // This function *can* return an PHP error.
              Log::addDebug('Resulting URL -- ' . $url);
            }
            catch(Exception $e)
            {
              Log::addWarn('Attachment seems corrupted', array($e->getMessage() ));
              return array("URLs" => array(), "PATHs" => array(), "sizesMissing" => array());
            }
            $urlList = array(); $filePaths = array();

            Log::addDebug('attached file path: ' . $path, array( (string) $fsFile->getFileDir() )  );

            if(!$mainExists) {
               list($url, $path) = $this->attemptRemoteDownload($url, $path, $this->ID);
               $downloadFile = $fs->getFile($path);
               if ($downloadFile->exists()) // check for success.
               {
                $mainExists = true;
                $fsFile = $downloadFile; // overwrite.
              }
            }

            if($mainExists) {
                $urlList[] = $url;
                $filePaths[] = $path;
                if($addRetina) {
                    $this->addRetina($path, $url, $filePaths, $urlList);
                }
            }

            Log::addDebug('Main file turnout - ', array($url, $path));

            $meta = $this->getMeta();
            $sizes = $meta->getThumbs();

            //it is NOT a PDF file and thumbs are processable
            if (  /*  strtolower(substr($path,strrpos($path, ".")+1)) != "pdf"
                 &&*/ ($processThumbnails || $onlyThumbs)
                 && count($sizes))
            {
                $Tmp = explode("/", SHORTPIXEL_UPLOADS_BASE);
                $TmpCount = count($Tmp);
                $StichString = $Tmp[$TmpCount-2] . "/" . $Tmp[$TmpCount-1];

                $count = 1;
                foreach( $sizes as $thumbnailName => $thumbnailInfo ) {

                    // Reasons for skipping the thumb.
                    if(!isset($thumbnailInfo['file'])) { //cases when $thumbnailInfo is NULL
                        continue;
                    }

                    if(isset($thumbnailInfo['mime-type']) && $thumbnailInfo['mime-type'] == "image/webp") {
                        continue; // found a case when there were .jpg.webp thumbnails registered in sizes
                    }

                    if(in_array($thumbnailName, $excludeSizes)) {
                        continue;
                    }

                    if(strpos($thumbnailName, ShortPixelMeta::WEBP_THUMB_PREFIX) === 0) {
                        continue;
                    }

                    if(!$includeOptimized && in_array($thumbnailInfo['file'], $meta->getThumbsOptList())) {
                        continue;
                    }

                    if($count >= SHORTPIXEL_MAX_THUMBS) break;
                    $count++;

                    $origPath = $tPath = str_replace(ShortPixelAPI::MB_basename($path), $thumbnailInfo['file'], $path);
                    $origFile = $fs->getFile($origPath);

                    if ($origFile->getExtension() == 'webp') // never include any webp extension.
                      continue;

                    $file_exists = apply_filters('shortpixel_image_exists', file_exists($origPath), $origPath, $this->ID);
                    $tUrl = str_replace(ShortPixelAPI::MB_basename($predownload_url), $thumbnailInfo['file'], $predownload_url);

                    // Working on low-key replacement for path handling via FileSystemController.
                    // This specific fix is related to the possibility of URLs' in metadata
                    if ( !$file_exists && !file_exists($tPath) )
                    {
                        $file = $fs->getFile($thumbnailInfo['file']);

                        if ($file->exists())
                        {
                          $tPath = $file->getFullPath();
                          $fsUrl = $fs->pathToUrl($file);
                          if ($fsUrl !== false)
                            $tUrl = $fsUrl; // more secure way of getting url
                        }
                    }

                    if ( !$file_exists && !file_exists($tPath) ) {
                        $try_path = SHORTPIXEL_UPLOADS_BASE . substr($origPath, strpos($origPath, $StichString) + strlen($StichString));
                        if (file_exists($try_path))
                          $tPath = $try_path; // found!
                    }

                    if ( !$file_exists && !file_exists($tPath) ) {
                        $try_path = trailingslashit(SHORTPIXEL_UPLOADS_BASE) . $origPath;
                        if (file_exists($try_path))
                          $tPath = $try_path; // found!
                    }

                    if ( !$file_exists && !file_exists($tPath) ) {
                        //try and download the image from the URL (images present only on CDN)
                      //  Log::addDebug('URLs and Paths - File didnt exists, trying to download', array($tUrl, $origPath));
                      //  $tempThumb = download_url($tUrl, $downloadTimeout);

                        list($tUrl, $tPath) = $this->attemptRemoteDownload($tUrl, $tPath, $this->ID);

                        Log::addDebug('New TPath after download', array($tUrl, $tPath, $origPath, filesize($tPath)));
                    }

                    if ($file_exists || file_exists($tPath)) {
                        if(in_array($tUrl, $urlList)) continue;
                        $urlList[] = $tUrl;
                        $filePaths[] = $tPath;
                        if($addRetina) {
                            $this->addRetina($tPath, $tUrl, $filePaths, $urlList);
                        }
                    }
                    else {
                        Log::addInfo('Missing Thumbnail :' . $tPath);
                        $sizesMissing[$thumbnailName] = ShortPixelAPI::MB_basename($tPath);
                    }
                }
            }
            if(!count($sizes)) {
                Log::addInfo("getURLsAndPATHs: no meta sizes for ID " . $this->ID . " : " . json_encode($this->rawMeta));
            }

            if($onlyThumbs && $mainExists && count($urlList) >= 1) { //remove the main image
                array_shift($urlList);
                array_shift($filePaths);
            }
        }


        //convert the + which are replaced with spaces by wp_remote_post
        array_walk($urlList, array( &$this, 'replacePlusChar') );

        $filePaths = ShortPixelAPI::CheckAndFixImagePaths($filePaths);//check for images to make sure they exist on disk
        return array("URLs" => $urlList, "PATHs" => $filePaths, "sizesMissing" => $sizesMissing);
    }

    private function attemptRemoteDownload($url, $path, $attach_id)
    {
        $downloadTimeout = max(SHORTPIXEL_MAX_EXECUTION_TIME - 10, 15);
        $fs = new \ShortPixel\FileSystemController();
        $pathFile = $fs->getFile($path);

        $args_for_get = array(
          'stream' => true,
          'filename' => $pathFile->getFullPath(),
        );

        $response = wp_remote_get( $url, $args_for_get );

        if(is_wp_error( $response )) {
          Log::addError('Download file failed', array($url, $response->get_error_messages(), $response->get_error_codes() ));

          // Try to get it then via this way.
          $response = download_url($url, $downloadTimeout);
          if (!is_wp_error($response)) // response when alright is a tmp filepath. But given path can't be trusted since that can be reason for fail.
          {
            $tmpFile = $fs->getFile($response);
            $post = get_post($attach_id);
            $post_date = get_the_date('Y/m', $post); // get the date for the uploads tree.

            $upload_dir = wp_upload_dir($post_date);
            $upload_dir = $fs->getDirectory($upload_dir['path']); // get the upload dir.

            $fixedFile = $fs->getFile($upload_dir->getPath() . $pathFile->getFileName() );
            // try to move
            $result = $tmpFile->move($fixedFile);

            Log::addDebug('Fixed File', array($post_date, $fixedFile->getFullPath() ));

            if ($result && $fixedFile->exists())
            {
              $path = $fixedFile->getFullPath(); // overwrite path with new fixed path.
              $url = $fs->pathToUrl($fixedFile);
              $pathFile = $fixedFile;
            }
          } // download_url ..
          else {
            Log::addError('Secondary download failed', array($url, $response->get_error_messages(), $response->get_error_codes() ));
          }
        }
        else { // success
            $pathFile = $fs->getFile($response['filename']);
        }

        $fsUrl = $fs->pathToUrl($pathFile);
        if ($fsUrl !== false)
            $url = $fsUrl; // more secure way of getting url

        Log::addDebug('Remote Download attempt result', array($url, $path));
        return array($url, $path);
    }

    protected function replacePlusChar(&$url) {
        $url = str_replace("+", "%2B", $url);
    }

    protected function addRetina($path, $url, &$fileList, &$urlList) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $retinaPath = substr($path, 0, strlen($path) - 1 - strlen($ext)) . "@2x." . $ext;
        if(file_exists($retinaPath)) {
            $urlList[] = substr($url, 0, strlen($url) -1 - strlen($ext)) . "@2x." . $ext;
            $fileList[] = $retinaPath;
        }
    }

    public static function isRetina($path) {
        $baseName = pathinfo(ShortPixelAPI::MB_basename($path), PATHINFO_FILENAME);
        return (substr($baseName, -3) === '@2x');
    }

    // @todo Not clear what this function does.
    public static function getWPMLDuplicates( $id ) {
        global $wpdb;

        $parentId = get_post_meta ($id, '_icl_lang_duplicate_of', true );
        if($parentId) $id = $parentId;

        $mainFile = get_attached_file($id);

        $duplicates = $wpdb->get_col( $wpdb->prepare( "
            SELECT pm.post_id FROM {$wpdb->postmeta} pm
            WHERE pm.meta_value = %s AND pm.meta_key = '_icl_lang_duplicate_of'
        ", $id ) );

        //Polylang
        $moreDuplicates = $wpdb->get_results( $wpdb->prepare( "
            SELECT p.ID, p.guid FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->posts} pbase ON p.guid = pbase.guid
         WHERE pbase.ID = %s
        ", $id ) );
        //MySQL is doing a CASE INSENSITIVE join on p.guid!! so double check the results.
        $guid = false;
        foreach($moreDuplicates as $duplicate) {
            if($duplicate->ID == $id) {
                $guid = $duplicate->guid;
            }
        }
        foreach($moreDuplicates as $duplicate) {
            if($duplicate->guid == $guid) {
                $duplicates[] = $duplicate->ID;
            }
        }

        $duplicates = array_unique($duplicates);

        if(!in_array($id, $duplicates)) $duplicates[] = $id;

        $transTable = $wpdb->get_results("SELECT COUNT(1) hasTransTable FROM information_schema.tables WHERE table_schema='{$wpdb->dbname}' AND table_name='{$wpdb->prefix}icl_translations'");
        if(isset($transTable[0]->hasTransTable) && $transTable[0]->hasTransTable > 0) {
            $transGroupId = $wpdb->get_results("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id = {$id}");
            if(count($transGroupId)) {
                $transGroup = $wpdb->get_results("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = " . $transGroupId[0]->trid);
                foreach($transGroup as $trans) {
                    if($mainFile == get_attached_file($trans->element_id)){
                        $duplicates[] = $trans->element_id;
                    }
                }
            }
        }
        return array_unique($duplicates);
    }

    public static function pathToWebPath($path) {
        //$upl = wp_upload_dir();
        //return str_replace($upl["basedir"], $upl["baseurl"], $path);
        return self::replaceHomePath($path, self::getHomeUrl()."/");
    }

    public static function pathToRootRelative($path) {
        //$upl = wp_upload_dir();
        $pathParts = explode('/', $path);
        unset($pathParts[count($pathParts) - 1]);
        $path = implode('/', $pathParts);
        return self::filenameToRootRelative($path);
    }

    public static function filenameToRootRelative($path) {
        return self::replaceHomePath($path, "");
    }

    private static function replaceHomePath($path, $with) {
        if(strpos($path, get_home_path()) === 0) {
            return str_replace(get_home_path(), $with, $path);
        } else { //try also the realpath
            return str_replace(trailingslashit(realpath(get_home_path())), $with, $path);
        }
    }

    public function getWebpSizeMeta($path) {
        $meta = $this->getMeta();
        $thumbs = $meta->getThumbs(); $thumbs = is_array($thumbs) ? $thumbs : array();
        foreach($thumbs as $thumbKey => $thumbMeta) {
            if(isset($thumbMeta['file']) && strpos($path, $thumbMeta['file']) !== false) {
                $thumbMeta['file'] = preg_replace( '/\.' . pathinfo($path, PATHINFO_EXTENSION) . '$/', '.webp', $thumbMeta['file']);
                $thumbMeta['mime-type'] = 'image/webp';
                return array('key' => ShortPixelMeta::WEBP_THUMB_PREFIX . $thumbKey, 'val' => $thumbMeta);
            }
        }
        $name = $meta->getName();
        if(strpos($path, $name) !== false) {
            if(!file_exists($path)) {
                return false;
            }
            $size = getimagesize($path);
            return array('key' => ShortPixelMeta::WEBP_THUMB_PREFIX . 'main',
                'val' => array( // it's a file that has no corresponding thumb so it's the WEBP for the main file
                    'file' => pathinfo(ShortPixelAPI::MB_basename($path), PATHINFO_FILENAME) . '.webp',
                    'width' => $size[0],
                    'height' => $size[1],
                    'mime-type' => 'image/webp'
            ));
        }
        return false;
    }

    public static function getMaxMediaId() {
        global  $wpdb;
        $queryMax = "SELECT max(post_id) as QueryID FROM " . $wpdb->prefix . "postmeta";
        $resultQuery = $wpdb->get_results($queryMax);
        return $resultQuery[0]->QueryID;
    }

    public static function getMinMediaId() {
        global  $wpdb;
        $queryMax = "SELECT min(post_id) as QueryID FROM " . $wpdb->prefix . "postmeta";
        $resultQuery = $wpdb->get_results($queryMax);
        return $resultQuery[0]->QueryID;
    }

    public static function isCustomQueuedId($id) {
        return substr($id, 0, 2) == "C-";
    }

    public static function stripQueuedIdType($id) {
        return intval(substr($id, 2));
    }

    public function getQueuedId() {
        return self::queuedId($this->type, $this->ID);
    }

    public static function queuedId($type, $id) {
        return ($type == self::CUSTOM_TYPE ? "C-" : "") . $id;
    }

    function getId() {
        return $this->ID;
    }

    function getType() {
        return $this->type;
    }

    function setId($ID) {
        $this->ID = $ID;
    }

    function setType($type) {
        $this->type = $type;
    }

    function getRawMeta() {
        return $this->rawMeta;
    }

    /**
     * return subdir for that particular attached file - if it's media library then last 3 path items, otherwise substract the uploads path
     * Has trailing directory separator (/)
     * @param type $file
     * @return string
     */
    static public function returnSubDirOld($file)
    {
        if(strstr($file, get_home_path())) {
            $path = str_replace( get_home_path(), "", $file);
        } else {
            $path = (substr($file, 1));
        }
        $pathArr = explode('/', $path);
        unset($pathArr[count($pathArr) - 1]);
        return implode('/', $pathArr) . '/';
    }

    /**
     * return subdir for that particular attached file - if it's media library then last 3 path items, otherwise substract the uploads path
     * Has trailing directory separator (/)
     * @param type $file
     * @return string
     */
    static public function returnSubDir($file)
    {

        // Experimental FS handling for relativePath. Should be able to cope with more exceptions.  See Unit Tests
        $fs = new ShortPixel\FileSystemController();
        $directory = $fs->getDirectory($file);
        if ($relpath = $directory->getRelativePath())
          return $relpath;


        $homePath = get_home_path();
        if($homePath == '/') {
            $homePath = ABSPATH;
        }
        $hp = wp_normalize_path($homePath);
        $file = wp_normalize_path($file);


      //  $sp__uploads = wp_upload_dir();

        if(strstr($file, $hp)) {
            $path = str_replace( $hp, "", $file);
        } elseif( strstr($file, dirname( WP_CONTENT_DIR ))) { //in some situations the content dir is not inside the root, check this also (ex. single.shortpixel.com)
            $path = str_replace( trailingslashit(dirname( WP_CONTENT_DIR )), "", $file);
        } elseif( (strstr(realpath($file), realpath($hp)))) {
            $path = str_replace( realpath($hp), "", realpath($file));
        } elseif( strstr($file, trailingslashit(dirname(dirname( SHORTPIXEL_UPLOADS_BASE )))) ) {
            $path = str_replace( trailingslashit(dirname(dirname( SHORTPIXEL_UPLOADS_BASE ))), "", $file);
        } else {
            $path = (substr($file, 1));
        }
        $pathArr = explode('/', $path);
        unset($pathArr[count($pathArr) - 1]);
        return implode('/', $pathArr) . '/';
    }

    public static function isMediaSubfolder($path, $orParent = true) {
        $uploadDir = wp_upload_dir();
        $uploadBase = SHORTPIXEL_UPLOADS_BASE;
        $uploadPath = $uploadDir["path"];
        //contains the current media upload path
        if($orParent && ShortPixelFolder::checkFolderIsSubfolder($uploadPath, $path)) {
            return true;
        }
        //contains one of the year subfolders of the media library
        if(strpos($path, $uploadPath) == 0) {
            $pathArr = explode('/', str_replace($uploadBase . '/', "", $path));
            if(   count($pathArr) >= 1
               && is_numeric($pathArr[0]) && $pathArr[0] > 1900 && $pathArr[0] < 2100 //contains the year subfolder
               && (   count($pathArr) == 1 //if there is another subfolder then it's the month subfolder
                   || (is_numeric($pathArr[1]) && $pathArr[1] > 0 && $pathArr[1] < 13) )) {
                return true;
            }
        }
        return false;
    }

    public function optimizationSucceeded() {
        if($this->getType() == self::MEDIA_LIBRARY_TYPE) {
            do_action( 'shortpixel_image_optimised', $this->getId() );
        }
    }

    public static function optimizationStarted($id) {
        do_action( 'shortpixel_start_image_optimisation', $id );
    }
}
