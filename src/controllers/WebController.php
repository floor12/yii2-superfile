<?php

namespace floor12\superfile;

use yii\rest\ActiveController;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;
use Yii;
use yii\web\Response;

class WebController extends ActiveController
{
    public $modelClass = 'floor12\superfile\File';


    public function behaviors()
    {
        return [

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['DELETE'],
                    'create' => ['POST'],
                    'crop' => ['PATCH'],
                    'rotate' => ['PATCH'],
                ],
            ],
            'format' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/xml' => Response::FORMAT_XML,
                    'application/json' => Response::FORMAT_JSON,
                ],
                'languages' => [
                    'en',
                    'ru',
                ],
            ],
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex($class, $field, $object_id = 0)
    {
        if (!$object_id)
            return false;
        $classname = $class;
        $object = $classname::findOne($object_id);
        return $object->superFiles[$field];
    }

    public function actionDelete($id)
    {
        $file = File::findOne((int)$id);
        if (!$file)
            throw new NotFoundHttpException('This file not found.');
        if (!$file->delete())
            throw new BadRequestHttpException('Delete error');


    }

    public function actionCrop()
    {
        $id = (int)$this->_requestPrams()->id;
        $width = (int)$this->_requestPrams()->width;
        $height = (int)$this->_requestPrams()->height;
        $top = (int)$this->_requestPrams()->top;
        $left = (int)$this->_requestPrams()->left;

        if (!$id || !$height || !$width)
            throw new BadRequestHttpException;

        $file = File::findOne($id);
        if (!$file)
            throw new NotFoundHttpException('This file is not found.');

        if ($file->type != File::TYPE_IMAGE)
            throw new BadRequestHttpException('This file is not an image.');

        if ($file->crop($width, $height, $top, $left))
            return $file;
        else
            throw new BadRequestHttpException;
    }

    public
    function actionCreate()
    {
        $ret = [];

        $className = Yii::$app->request->post('class');
        $field = \Yii::$app->request->post('field');

        $owner = \Yii::createObject($className, []);
        if (!$owner)
            throw new BadRequestHttpException('Owner class not found');
        $form = \Yii::createObject(SuperfileForm::class, [$className, 0, $field, $owner->fields]); 


        $files = UploadedFile::getInstancesByName('file');


        if ((sizeof($files) > 0) && ($className) && ($field)) {
            $response = \Yii::$app->getResponse();
            $response->setStatusCode(201);
            foreach ($files as $file) {
                $ret[] = $model = File::createFromInstance($file, $form);
            }
        } else {
            throw new BadRequestHttpException("Empty files array.");
        }
        if (sizeof($ret) == 1)
            return $ret[0];
        return $ret;
    }

    public function actionUpdate()
    {
        $id = $this->_requestPrams()->id;
        $title = $this->_requestPrams()->title;

        if (!$id || !$title)
            throw new BadRequestHttpException;
        $file = File::findOne($id);
        if (!$file)
            throw new NotFoundHttpException('This file not found.');
        $file->title = $title;
        if (!$file->save())
            throw new BadRequestHttpException('Cant update file');
    }

    public function actionRotate()
    {
        $id = $this->_requestPrams()->id;
        $direction = $this->_requestPrams()->direction;

        if (!$id || !$direction)
            throw new BadRequestHttpException("File id or direction is empty");
        $file = File::findOne($id);
        if (!$file)
            throw new NotFoundHttpException('This file not found.');
        if ($file->type != File::TYPE_IMAGE)
            throw new NotFoundHttpException('Its not an image.');
        $file->rotate($direction);
    }

    private function _requestPrams()
    {
        return json_decode(\Yii::$app->request->rawBody);
    }

    public function actions()
    {
        return [

        ];
    }

}
