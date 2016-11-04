<?php
/**
 * Created by PhpStorm.
 * User: floor12
 * Date: 04.11.2016
 * Time: 12:20
 */

namespace floor12\superfile;

use yii\web\AssetBundle;


class UploaderAsset extends AssetBundle
{
    public $sourcePath = '@bower';
    public $js = [
        'ng-file-upload/ng-file-upload.min.js',
    ];

}