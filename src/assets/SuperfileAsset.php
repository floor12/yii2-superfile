<?php

namespace floor12\superfile;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class SuperfileAsset extends AssetBundle
{

    public $sourcePath = '@vendor/floor12/yii2-superfile/assets/';
    public $css = [
        'superfilefield.css'
    ];
    public $js = [
        'superfilefield.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\jui\JuiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
        'floor12\superfile\AngularAsset',
        'rmrevin\yii\fontawesome\AssetBundle',
        'floor12\superfile\UploaderAsset'
    ];

}
