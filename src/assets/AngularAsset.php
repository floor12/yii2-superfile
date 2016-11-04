<?php
/**
 * Created by PhpStorm.
 * User: floor12
 * Date: 04.11.2016
 * Time: 12:20
 */

namespace floor12\superfile;

use yii\web\AssetBundle;


class AngularAsset extends AssetBundle
{
    public $sourcePath = '@vendor/tesjin/yii2-angularjs';
    public $js = [
        'js/angular.js',
    ];
}