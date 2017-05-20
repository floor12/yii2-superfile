<?php
/**
 * Created by PhpStorm.
 * User: floor12
 * Date: 23.06.2016
 * Time: 11:23
 */

namespace floor12\superfile;


use Yii;
use yii\base\ErrorException;
use yii\validators\FileValidator;
use yii\web\BadRequestHttpException;

/**
 * Основная модель файла, подключаемая через поведение к моделям.
 *
 * @property integer $id
 * @property string $class
 * @property string $field
 * @property integer $object_id
 * @property string $title
 * @property string $filename
 * @property string $content_type
 * @property integer $type
 * @property integer $video_status
 * @property string $rootPath
 * @property string $rootPrevirePath
 * @property integer $size
 */
class File extends \yii\db\ActiveRecord
{
    const TYPE_FILE = 0;
    const TYPE_IMAGE = 1;
    const TYPE_VIDEO = 2;

    const VIDEO_STATUS_QUEUE = 0;
    const VIDEO_STATUS_CONVERTING = 1;
    const VIDEO_STATUS_READY = 2;

    const DIRECTORY_SEPARATOR = "/";

    const FOLDER_NAME = 'uploadedfiles';

    /**
     * Generated rand path to file saving
     * @return string
     */

    public static function generatePath()
    {
        $folder0 = rand(10, 99);
        $folder1 = rand(10, 99);

        $path0 = self::FOLDER_NAME . self::DIRECTORY_SEPARATOR . $folder0;
        $path1 = self::FOLDER_NAME . self::DIRECTORY_SEPARATOR . $folder0 . self::DIRECTORY_SEPARATOR . $folder1;

        $fullPath0 = Yii::getAlias('@app') . self::DIRECTORY_SEPARATOR . 'web' . self::DIRECTORY_SEPARATOR . $path0;
        $fullPath1 = Yii::getAlias('@app') . self::DIRECTORY_SEPARATOR . 'web' . self::DIRECTORY_SEPARATOR . $path1;

        if (!file_exists($fullPath0))
            mkdir($fullPath0);
        if (!file_exists($fullPath1))
            mkdir($fullPath1);

        return self::DIRECTORY_SEPARATOR . $path1 . self::DIRECTORY_SEPARATOR . md5(rand(0, 1000) . time());
    }

    public static function createFromBase64($string, $class, $field)
    {
        $data = explode(',', $string);
        $info = explode(';', $data[0]);
        $tmp = explode('/', $info[0]);
        $extansion = $tmp[1];

        $classname = $class;
        $filename = self::generatePath() . "." . $extansion;

        $path = Yii::getAlias('@webroot') . $filename;

        $ifp = fopen($path, "wb");
        fwrite($ifp, base64_decode($data[1]));
        fclose($ifp);

        $file = new File();
        $file->field = $field;
        $file->class = $classname;
        $file->filename = $filename;
        $file->title = 'file';
        $file->content_type = mime_content_type($path);
        $file->type = $file->detectType();
        $file->size = filesize($path);
        $file->created = time();
        $file->user_id = (isset(\Yii::$app->user) && \Yii::$app->user->id) ? \Yii::$app->user->id : 0;
        if ($file->type == self::TYPE_VIDEO)
            $file->video_status = 0;
        if ($file->save()) {
            $file->updatePreview();
            return $file->id;
        }

    }


    public static function createFromUrl($url, $class, $field)
    {
        if (!$url)
            return false;

        $content = file_get_contents($url);
        $tmp_extansion = explode('?', pathinfo($url, PATHINFO_EXTENSION));
        $extansion = $tmp_extansion[0];
        $classname = $class;
        $filename = self::generatePath() . "." . $extansion;

        $path = Yii::getAlias('@webroot') . $filename;

        $fileOnDisk = fopen($path, "w");
        if (!$fileOnDisk)
            throw new BadRequestHttpException("Unable write file");
        fwrite($fileOnDisk, $content);
        fclose($fileOnDisk);

        $file = new File();
        $file->field = $field;
        $file->class = $classname;
        $file->filename = $filename;
        $file->title = $url;
        $file->content_type = mime_content_type($path);
        $file->type = $file->detectType();
        $file->size = filesize($path);
        $file->created = time();
        $file->user_id = (isset(\Yii::$app->user) && \Yii::$app->user->id) ? \Yii::$app->user->id : 0;
        if ($file->type == self::TYPE_VIDEO)
            $file->video_status = 0;
        if ($file->save()) {

            if ($file->type == self::TYPE_IMAGE) {
                $exif = '';
                @$exif = exif_read_data($path);
                if (isset($exif['Orientation'])) {
                    $ort = $exif['Orientation'];
                    $rotatingImage = new SimpleImage();
                    $rotatingImage->load($path);
                    switch ($ort) {

                        case 3: // 180 rotate left
                            $rotatingImage->rotateDegrees(180);
                            break;


                        case 6: // 90 rotate right
                            $rotatingImage->rotateDegrees(270);
                            break;

                        case 8:    // 90 rotate left
                            $rotatingImage->rotateDegrees(90);
                    }
                    $rotatingImage->save($path);
                }

            }


            $file->updatePreview();
            return $file->id;
        }

    }

    /**
     * @param $instance
     * @param SuperfileForm $form
     * @return File
     * @throws ErrorException
     */

    public static function createFromInstance($instance, SuperfileForm $form)
    {
        if ($instance->error)
            throw new ErrorException("No file instance found.");

        if ($form->validator->validate($instance, $error)) {
            $filename = self::generatePath() . '.' . $instance->extension;
            $path = Yii::getAlias('@webroot') . $filename;
            $file = new File();
            $file->field = $form->field;
            $file->class = $form->classname;
            if ($form->object_id)
                $file->object_id = $form->object_id;
            $file->filename = $filename;
            $file->title = $instance->name;
            $file->content_type = $instance->type;
            $file->type = $file->detectType();
            $file->size = $instance->size;
            $file->created = time();
            $file->user_id = \Yii::$app->user->id;
            if ($file->type == self::TYPE_VIDEO)
                $file->video_status = 0;
            if ($file->save()) {
                $instance->saveAs($path);

                if ($file->type == self::TYPE_IMAGE && ($instance->extension == 'jpg' || $instance->extension == 'jpeg')) {
                    $exif = '';
                    @$exif = exif_read_data($path);
                    if (isset($exif['Orientation'])) {
                        $ort = $exif['Orientation'];
                        $rotatingImage = new SimpleImage();
                        $rotatingImage->load($path);
                        switch ($ort) {

                            case 3: // 180 rotate left
                                $rotatingImage->rotateDegrees(180);
                                break;


                            case 6: // 90 rotate right
                                $rotatingImage->rotateDegrees(270);
                                break;

                            case 8:    // 90 rotate left
                                $rotatingImage->rotateDegrees(90);
                        }
                        $rotatingImage->save($path);
                    }

                }


                $file->updatePreview();
                if ($form->processor)
                    \Yii::createObject($form->processor, [$path])->execute();
                return $file;
            } else
                throw new ErrorException("Error white saving file model.");
        } else {
            throw new ErrorException("File validation error.");
        }
    }

    /**
     * Return file type from content type
     * @return int
     */

    public function detectType()
    {
        $contentTypeArray = explode('/', $this->content_type);
        if ($contentTypeArray[0] == 'image')
            return self::TYPE_IMAGE;
        if ($contentTypeArray[0] == 'video')
            return self::TYPE_VIDEO;
        return self::TYPE_FILE;
    }

    /**
     * Rotate current file if it is image
     * @param 1|2 $direction
     */

    public function rotate($direction)
    {
        if ($this->type == self::TYPE_IMAGE) {
            $image = new SimpleImage();
            $image->load($this->rootPath);
            $image->rotate($direction);
            $image->save($this->rootPath);
            $this->updatePreview();
        }
    }

    /**
     * Crop image and convert to jpeg
     * @param $width
     * @param $height
     * @param $top
     * @param $left
     * @return string
     * @throws ErrorException
     */

    public function crop($width, $height, $top, $left)
    {
        if ($this->type == self::TYPE_IMAGE) {

            $src = $this->imageCreateFromAny();
            $dest = imagecreatetruecolor($width, $height);

            imagecopy($dest, $src, 0, 0, $left, $top, $width, $height);

            $newName = $filename = self::generatePath() . '.jpeg';
            $path = Yii::getAlias('@webroot') . $newName;

            @unlink($this->rootPath);
            @unlink($this->rootPreviewPath);

            imagejpeg($dest, $path, 80);

            imagedestroy($dest);
            imagedestroy($src);

            $this->filename = $newName;
            $this->content_type = mime_content_type($path);
            $this->size = filesize($path);
            if ($this->save()) {
                $this->updatePreview();
                return $this->filename;
            } else
                throw new ErrorException("Error while saving file model");
        } else
            throw new ErrorException("This file is not an image");

    }

    /**
     * @inheritdoc
     */

    public static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
     */

    public function rules()
    {
        return [
            [['class', 'field', 'filename', 'content_type', 'type'], 'required'],
            [['object_id', 'type', 'video_status', 'ordering'], 'integer'],
            [['class', 'field', 'title', 'filename', 'content_type'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */

    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'class' => Yii::t('app', 'Class'),
            'field' => Yii::t('app', 'Field'),
            'object_id' => Yii::t('app', 'Object ID'),
            'title' => Yii::t('app', 'Title'),
            'filename' => Yii::t('app', 'Filename'),
            'content_type' => Yii::t('app', 'Con tent Type'),
            'type' => Yii::t('app', 'Type'),
            'video_status' => Yii::t('app', 'Video Status'),
        ];
    }

    /**
     * Updating preview
     */

    public function updatePreview()
    {
        if ($this->type == self::TYPE_VIDEO) {
            if (file_exists($this->rootPath)) {
                exec(Yii::getAlias('@ffmpeg') . " -i {$this->rootPath} -ss 00:00:15.000 -vframes 1  {$this->rootPreviewPath}");
            }
            if (!file_exists($this->rootPreviewPath)) {
                exec(Yii::getAlias('@ffmpeg') . " -i {$this->rootPath} -ss 00:00:6.000 -vframes 1  {$this->rootPreviewPath}");
            }
            if (!file_exists($this->rootPreviewPath)) {
                exec(Yii::getAlias('@ffmpeg') . " -i {$this->rootPath} -ss 00:00:2.000 -vframes 1  {$this->rootPreviewPath}");
            }
        }

        if ($this->type == self::TYPE_IMAGE)
            if (file_exists($this->rootPath)) {
                $image = new SimpleImage();
                $image->load($this->rootPath);

                if ($image->getWidth() > 1920 || $image->getHeight() > 1080) {
                    $image->resizeToWidth(1920);
                    $image->save($this->rootPath);
                }
                $image->resizeToWidth(350);
                $image->save($this->rootPreviewPath);
            }
    }

    /**
     * Return root path of image
     * @return string
     */

    public function getRootPath()
    {
        return Yii::getAlias('@app') . self::DIRECTORY_SEPARATOR . 'web' . $this->filename;
    }

    /**
     * Return root path of preview
     * @return string
     */

    public function getRootPreviewPath()
    {
        return Yii::getAlias('@app') . self::DIRECTORY_SEPARATOR . 'web' . $this->filename . '.jpg';
    }

    /**
     * Return web path of preview
     * @return string
     */


    public function getFilenamePreview()
    {
        return $this->filename . ".jpg";
    }

    /**
     * Delete files from disk
     */

    public function afterDelete()
    {
        @unlink($this->rootPath);
        @unlink($this->rootPreviewPath);
        parent::afterDelete();
    }

    /**
     * Method to read files from any mime types
     * @return bool
     */

    public function imageCreateFromAny()
    {
        $type = exif_imagetype($this->rootPath);
        $allowedTypes = array(
            1, // [] gif
            2, // [] jpg
            3, // [] png
            6   // [] bmp
        );
        if (!in_array($type, $allowedTypes)) {
            return false;
        }
        switch ($type) {
            case 1 :
                $im = imageCreateFromGif($this->rootPath);
                break;
            case 2 :
                $im = imageCreateFromJpeg($this->rootPath);
                break;
            case 3 :
                $im = imageCreateFromPng($this->rootPath);
                break;
            case 6 :
                $im = imageCreateFromBmp($this->rootPath);
                break;
        }
        return $im;
    }
}
