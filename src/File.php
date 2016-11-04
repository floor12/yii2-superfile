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


    const FOLDER_NAME = 'uploadedfiles';

    /**
     * Статический метод создания экземпляра класса из загружженого файла
     *
     * @param $instance \yii\web\UploadedFile;
     * @param $class string
     * @param $field string
     * @param $id integer
     *
     * @return \floor12\superfile\File
     */


    public static function generatePath()
    {
        $folder0 = rand(10, 99);
        $folder1 = rand(10, 99);

        $path0 = self::FOLDER_NAME . "/" . $folder0;
        $path1 = self::FOLDER_NAME . "/" . $folder0 . "/" . $folder1;

        $fullPath0 = Yii::getAlias('@frameworkbase') . "/" . 'backend/web/' . $path0;
        $fullPath1 = Yii::getAlias('@frameworkbase') . "/" . 'backend/web/' . $path1;

        if (!file_exists($fullPath0))
            mkdir($fullPath0);
        if (!file_exists($fullPath1))
            mkdir($fullPath1);

        return "/" . $path1 . "/" . md5(rand(0, 1000) . time());
    }

    public static function createFromInstance($instance, SuperFileForm $form)
    {
        if ($instance->error)
            return false;

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
                $file->updatePreview();
                return $file;
            }
        } else {
            throw new ErrorException($error);
        }
    }

    public function detectType()
    {
        $tmp = explode('/', $this->content_type);
        $mainType = $tmp[0];
        if ($mainType == 'image')
            return self::TYPE_IMAGE;
        if ($mainType == 'video')
            return self::TYPE_VIDEO;
        return self::TYPE_FILE;
    }

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

    public function crop($width, $height, $top, $left)
    {
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
        }

    }


    /**
     * @inheritdoc
     */
    public
    static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
     */
    public
    function rules()
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
    public
    function attributeLabels()
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

    public
    function updatePreview()
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


    public
    function getRootPath()
    {
        return Yii::getAlias('@app') . "/" . 'web' . $this->filename;
    }

    public
    function getRootPreviewPath()
    {
        return Yii::getAlias('@app') . "/" . 'web' . $this->filename . '.jpg';
    }


    /**
     * Подчищаем за собой
     */

    public
    function afterDelete()
    {
        @unlink($this->rootPath);
        @unlink($this->rootPreviewPath);

        parent::afterDelete();
    }


    public
    function imageCreateFromAny()
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
