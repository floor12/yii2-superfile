<?php
/**
 * Created by PhpStorm.
 * User: floor12
 * Date: 23.06.2016
 * Time: 13:43
 */


namespace floor12\superfile;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\validators\Validator;


class SuperfileBehavior extends Behavior
{

    public $superfilesArray;
    public $fields = [];


    public function fileForm($field)
    {
        SuperfileAsset::register(\Yii::$app->view);
        return \Yii::$app->view->renderFile('@vendor/floor12/yii2-superfile/views/fileForm.php', [
            'form' => \Yii::createObject(SuperfileForm::class, [$this->owner->className(), $this->owner->id, $field, $this->fields])]);
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'superfilesUpdate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'superfilesUpdate'
        ];
    }

    public function superfilesUpdate()
    {
        $order = 0;
        if ($this->superfilesArray) {


            foreach ($this->superfilesArray as $key => $field) {
                \Yii::$app->db->createCommand("UPDATE file SET `object_id`=0 WHERE `class`='" . str_replace('\\', '\\\\', $this->owner->className()) . "' AND `object_id`='{$this->owner->id}' AND `field`='{$key}'")->query();
                if ($field) foreach ($field as $id) {
                    $file = File::findOne($id);
                    if ($file) {
                        $file->object_id = $this->owner->id;
                        $file->ordering = $order;
                        $file->save();
                        $order++;
                        if (!$file->save()) {
                            print_r($file->getErrors());
                        }
                    }

                }
            }
        }
    }

    public
    function attach($owner)
    {
        parent::attach($owner);
        $validators = $owner->validators;
        $validator = Validator::createValidator('safe', $owner, ['superfilesArray']);
        $validators->append($validator);
    }

    public function getFiles()
    {
        return $this->owner->hasMany(File::className(), ['object_id' => 'id'])->orderBy('ordering ASC')->onCondition(['class' => $this->owner->className()]);
    }

    public function getSuperfiles()
    {
        $files = $this->owner->files;
        $ret = [];
        if ($this->fields) foreach ($this->fields as $key => $field) {
            $ret[$key] = [];
        }
        /** @var $file File */
        if ($files) foreach ($files as $file) {
            if ((isset($this->fields[$file->field]['multiply']) && $this->fields[$file->field]['multiply'] == false))
                $ret[$file->field] = $file;
            else
                $ret[$file->field][] = $file;
        }
        return $ret;
    }

    public
    function getAllSuperfilesAsArray()
    {
        $ret = [];
        if ($this->superFiles)
            foreach ($this->superFiles as $field => $array)
                foreach ($array as $file) {
                    $ret[] = $file->id;
                }
        return $ret;
    }

}