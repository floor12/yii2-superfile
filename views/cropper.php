<?php
/**
 * Created by PhpStorm.
 * User: floor12
 * Date: 31.05.2016
 * Time: 12:42
 */


?>


<div class="modal fade cropperModal" id="cropperModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Кадрирование изображения</h4>
            </div>
            <div class="modal-body" id='cropArea'></div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-md-6 text-left">
                        <div ng-show="!config.ratio">
                            <button type="button" class="btn btn-primary" id='superfield-control-01'>1/1</button>
                            <button type="button" class="btn btn-primary" id='superfield-control-02'>3/4</button>
                            <button type="button" class="btn btn-primary" id='superfield-control-03'>4/3</button>
                            <button type="button" class="btn btn-primary" id='superfield-control-04'>16/9</button>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-default" ng-click="stopCrop()">Отмена</button>
                        <button type="button" class="btn btn-success" ng-click="crop()">Кадрировать</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
