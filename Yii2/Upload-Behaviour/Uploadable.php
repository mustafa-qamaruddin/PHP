<?php
/**
 * Upload Behavior
 *
 * Mixin / Trait / Behavior for uploading images / files with models when a model attribute is marked as file
 *
 * PHP version 5.4
 *
 * @author     Mustafa Qamar-ud-Din <m.qamaruddin@nilecode.com>
 * @author     Another Author <another@example.com>
 * @copyright  2016 Nilecode
 */

namespace common\helpers;

use yii\base\Behavior;
use yii\base\Event;
use yii\web\UploadedFile;
use yii\db\ActiveRecord;
use Yii;

class Uploadable extends Behavior
{
    /**
     * @var String
     */
    public $file_attribute;

    /**
     * The path inside uploads dir only
     * e.x. /var/www/total/dev/uploads->/users/115/xxx
     * @var String
     */
    public $model_dir;

    /**
     * Holds the file name between calls
     * @var String
     */
    public $file_name;

    /**
     * @var UploadedFile
     */
    public $objUploadedFile;

    /**
     * @return array
     */
    public function events()
    {
        $ret = parent::events();

        $ret[ActiveRecord::EVENT_BEFORE_VALIDATE] = 'beforeValidate';
        $ret[ActiveRecord::EVENT_AFTER_INSERT] = 'afterInsert';
        $ret[ActiveRecord::EVENT_AFTER_UPDATE] = 'afterUpdate';

        return $ret;
    }

    /**
     * @param \yii\base\Event $event
     */
    public function beforeValidate(Event $event)
    {
        // get file object
        $attribute = $this->file_attribute;
        $this->objUploadedFile = UploadedFile::getInstance($this->owner, $this->file_attribute);

        // No file was uploaded
        if (empty($this->objUploadedFile)) {
            return true;
        }

        // New File Name to be saved into database
        $extension = $this->extractExtension($this->objUploadedFile->name);
        $new_random_name = $this->generateRandomFileName();

        // Set attribute on parent model / component
        $this->file_name = $this->owner->$attribute = $new_random_name . $extension;

        return true;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function generateRandomFileName()
    {
        return Yii::$app->security->generateRandomString() . time();
    }

    /**
     * @param $file_name
     * @return mixed
     */
    public function extractExtension($file_name)
    {
        $last_dot = strpos($file_name, '.');
        $extension = substr($file_name, $last_dot);
        return $extension;
    }

    public function afterInsert(Event $event)
    {
        $this->saveFileToDisk();
    }

    public function afterUpdate(Event $event)
    {
        $this->saveFileToDisk();
    }

    public function saveFileToDisk()
    {
        if (empty($this->objUploadedFile)) {
            return true;
        }

        $uploads_dir = Yii::$app->params['uploadPath'];
        $uploads_sub_dir = sprintf($this->model_dir, $this->owner->id);

        $arr_dirs = explode(DIRECTORY_SEPARATOR, $uploads_sub_dir);

        // create dirs recursively if not exists
        foreach ($arr_dirs as $dir) {
            $uploads_dir .= $dir;

            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir);
            }

            $uploads_dir .= DIRECTORY_SEPARATOR;
        }

        $full_file_name = $uploads_dir . $this->file_name;

        $result = move_uploaded_file($this->objUploadedFile->tempName, $full_file_name);

        return $result;
    }
}