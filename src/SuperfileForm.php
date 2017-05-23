<?php
/**
 * Created by PhpStorm.
 * User: floor12
 * Date: 31.10.2016
 * Time: 11:49
 */


namespace floor12\superfile;

use yii\base\ErrorException;
use yii\validators\FileValidator;

class SuperfileForm
{
    public $field;
    public $object_id;
    public $classname;
    public $showControl = true;
    public $showName = true;
    public $ratio = NULL;
    public $preview = false;
    public $bricked = false;
    public $title = 'Файлы';
    public $required = false;
    public $multiply = false;
    public $button = 'Добавить файлы';
    public $label = true;
    public $processor = null;
    private $mimeTypes = null;
    private $extentions = null;
    private $maxSize = null;
    public $validator;
    public $watermark;
    public $successFunction = "";
    public $errorFunction = "";
    public $deleteFunction = "";


    /**
     * SuperFileForm constructor.
     * @param $classname string
     * @param $object_id integer
     * @param $field string
     * @param $data array
     */

    public function __construct($classname, $object_id, $field, $data)
    {

        if (!array_key_exists($field, $data))
            throw new ErrorException("Current field '{$field}' not found in owner");

        $this->classname = $classname;
        $this->classname_slahed = str_replace('\\', '\\\\', $classname);
        $this->classname_short = (substr($classname, strrpos($this->classname, '\\') + 1));
        $this->object_id = (int)$object_id;
        $this->field = $field;


        if (is_array($data[$field])) {
            foreach ($data[$field] as $key => $value)
                $this->$key = $value;
        } else {
            $this->title = $data[$field];
        }

        $this->validator = new FileValidator();
        $this->validator->extensions = $this->extentions;
        $this->validator->mimeTypes = $this->mimeTypes;
        $this->validator->maxSize = $this->maxSize;
    }

    public function getJson()
    {
        $vars = get_object_vars($this);
        unset($vars['validator']);
        return json_encode($vars);
    }


}