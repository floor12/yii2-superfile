<?php
/**
 * Created by PhpStorm.
 * User: floor12
 * Date: 23.06.2016
 * Time: 14:02
 *
 * @var \floor12\superfile\SuperfileForm $form
 *
 */

use \floor12\superfile\CropperAsset;

CropperAsset::register($this);
$template = Yii::$app->getAssetManager()->publish("@vendor/floor12/yii2-superfile/templates/files.html");
?>
<div class="form-group files-form" ng-controller="filesController">
    <div ng-include="'<?= $template[1] ?>'" onload='config = <?= $form->getJson() ?>; loadFiles()'></div>
    <?= $this->render('cropper'); ?>
</div>

<script>
    setTimeout(function () {
        $.each($('.files-form'), function (key, val) {
            angular.bootstrap(val, ['files']);
        })
    }, 1000)
</script>


