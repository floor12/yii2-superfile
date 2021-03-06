/**
 * Created by floor12 on 23.06.2016.
 */

console.log('superfile init');

$(document).ready(function () {
    setInterval(function () {

        $(".superfiles-list").sortable({
            opacity: 0.5,
            revert: 1,
            items: "div.superfile",
        });
    }, 2000)
});


(function () {

    var app = angular.module('files', ['ngFileUpload']);


    app.config(['$provide', function ($provide) {
        $provide.decorator('$browser', ['$delegate', function ($delegate) {
            $delegate.onUrlChange = function () {
            };
            $delegate.url = function () {
                return ""
            };
            return $delegate;
        }]);
    }]);


    app.directive('myEnter', function () {
        return function (scope, element, attrs) {
            element.bind("keydown keypress", function (event) {
                if (event.which === 13) {
                    scope.$apply(function () {
                        scope.$eval(attrs.myEnter);
                    });
                    event.preventDefault();
                }
            });
        };
    });


    app.controller('filesController', function ($scope, $http, Upload, $timeout, $rootElement) {

        $scope.progress = 0;
        $scope.loading = false;
        $scope.files = [];
        $scope.config = {};
        $scope.form = $($rootElement).parents('form');

        $scope.loadFiles = function () {

            if ($scope.config.required) {

                $(document).on('submit', $scope.form, function () {
                    if ($scope.files.length == 0)
                        return false;
                })
            }

            $http.get("/superfile/index", {
                    params: {
                        class: $scope.config.classname,
                        field: $scope.config.field,
                        object_id: $scope.config.object_id
                    }
                })
                .success(function (response) {
                    if ($scope.config.multiply)
                        angular.forEach(response, function (object) {
                            object.filename_preview = object.filename + ".jpg?" + Math.random();
                            $scope.files.push(object);
                        })
                    else {
                        if (response.id) {
                            response.filename_preview = response.filename + ".jpg?" + Math.random();
                            $scope.files.push(response);
                        }
                    }
                })
        }

        $scope.turnFile = function (file, direction) {
            $http.patch('/superfile/rotate', {
                    id: file.id,
                    direction: direction
                })
                .success(function () {
                    file.filename_preview = file.filename + "?" + Math.random();
                })
        }

        $scope.deleteFile = function (file) {
            $http.delete('/superfile/delete', {params: {id: file.id}})
                .success(function () {
                    $scope.files.splice($scope.files.indexOf(file), 1)
                    eval($scope.config.deleteFunction);
                });

        }

        $scope.renameFile = function (file, event) {
            file.title_new = file.title;
            file.edit = true;
            setTimeout(function () {
                $(event.currentTarget).parent().parent().find('input').focus();
            }, 200);

        }

        $scope.saveFilename = function (file) {
            $http.patch('/superfile/update', {
                id: file.id,
                title: file.title_new
            }).success(function () {
                file.edit = false;
                file.title = file.title_new;
            })
        }

        $scope.uploadFiles = function (files, errFiles) {

            $scope.errFiles = errFiles;

            angular.forEach(files, function (file) {
                file.upload = Upload.upload({
                    url: '/superfile/create',
                    data: {'file[]': file, 'class': $scope.config.classname, field: $scope.config.field}
                });

                file.upload.then(function (response) {
                    console.log($scope.config);
                    $timeout(function () {
                        response.data.filename_preview = response.data.filename + ".jpg?" + Math.random();
                        $scope.loading = false;
                        if ($scope.config.ratio) {
                            $scope.cropFile(response.data);
                        } else {
                            eval($scope.config.successFunction);
                            if ($scope.config.multiply)
                                $scope.files.push(response.data);
                            else
                                $scope.files = [response.data];
                        }

                    });
                }, function (response) {
                    if (response.status > 0)
                        $scope.errorMsg = response.status + ': ' + response.data;
                }, function (evt) {
                    $scope.loading = true;
                    $scope.progress = Math.min(100, parseInt(100.0 *
                        evt.loaded / evt.total));
                    console.log($scope.progress);
                });
            });

            // angular.forEach(files, function (file) {
            //     eval($scope.config.errorFunction);
            // })

        }

        $scope.stopCrop = function () {
            $('div.cropperModal' + $scope.config.field).modal('hide');
            if ($('div.modal-backdrop').hasClass('in')) {
                setTimeout(function () {
                    $('body').addClass('modal-open');
                }, 600);
            }
        }

        $scope.cropFile = function (file) {
            current_modal = $('div.cropperModal' + $scope.config.field);
            current_modal.modal();
            $scope.currentCropImage = $('<img>').attr('src', file.filename).addClass('cropedImage');
            $scope.currentCropImage.attr('src', file.filename);
            $('div.cropArea' + $scope.config.field).html("");
            $('div.cropArea' + $scope.config.field).append($scope.currentCropImage);
            $scope.currentCropFile = file;
            setTimeout(function () {
                $scope.cropper = $scope.currentCropImage.cropper({
                    viewMode: 1,
                });

                if (!$scope.config.ratio) {
                    $('#superfield-control-01').click(function () {
                        $scope.cropper.cropper('setAspectRatio', 1 / 1);
                    });
                    $('#superfield-control-02').click(function () {
                        $scope.cropper.cropper('setAspectRatio', 3 / 4);
                    });

                    $('#superfield-control-03').click(function () {
                        $scope.cropper.cropper('setAspectRatio', 4 / 3);
                    });

                    $('#superfield-control-04').click(function () {
                        $scope.cropper.cropper('setAspectRatio', 16 / 9);
                    });
                } else {
                    $scope.cropper.cropper('setAspectRatio', eval($scope.config.ratio));
                }

                parent_modal = current_modal.parentsUntil("div.modal-content").parent();
                if (parent_modal.length) {
                    setTimeout(function () {
                        current_height = current_modal.find('.modal-content').height()
                        parent_height = parent_modal.height();
                        if (parent_height < current_height)
                            parent_modal.height(current_height + 80);
                        current_modal.parent().parent().height('auto');
                    }, 500);

                }

            }, 500)

        }

        $scope.crop = function () {
            сropBoxData = $scope.cropper.cropper('getCropBoxData');
            imageData = $scope.cropper.cropper('getImageData');
            canvasData = $scope.cropper.cropper('getCanvasData');
            ratio = imageData.height / imageData.naturalHeight;
            cropLeft = (сropBoxData.left - canvasData.left) / ratio;
            cropTop = (сropBoxData.top - canvasData.top) / ratio;
            cropWidth = сropBoxData.width / ratio;
            cropHeight = сropBoxData.height / ratio;

            data = {
                id: $scope.currentCropFile.id,
                width: cropWidth,
                height: cropHeight,
                top: cropTop,
                left: cropLeft,
            }


            $http.patch('/superfile/crop', data)
                .success(function (response) {
                    $scope.currentCropFile.filename = response.filename;
                    $scope.currentCropFile.filename_preview = $scope.currentCropFile.filename + "?" + Math.random();
                    $('div.cropArea' + $scope.config.field).html('');
                    $('div.cropperModal' + $scope.config.field).modal('hide');
                    if ($('div.modal-backdrop').hasClass('in')) {
                        setTimeout(function () {
                            $('body').addClass('modal-open');
                        }, 600);
                    }

                    if ($scope.config.ratio) {
                        eval($scope.config.successFunction);
                        if ($scope.config.multiply)
                            $scope.files.push($scope.currentCropFile);
                        else
                            $scope.files = [$scope.currentCropFile];
                    }
                })
        }
    })
})
();

function heightFix() {
    $.each($('.previewed'), function () {
        height = $(this).width() / eval($(this).attr('data-ratio'));
        $(this).css('padding-top', height - 30).height(0);
        $(this).find('div.superfile-title-block').css('margin-top', -30);
        if (!$(this).hasClass('hidecontroled'))
            $(this).find('div.superfile-title-block.hovered').css('margin-top', -65);
    })

    $.each($('.brick'), function () {
        height = $(this).width() * (4/3);
        $(this).css('padding-top', height - 30).height(0);
        $(this).find('div.superfile-title-block').css('margin-top', -27);
        if (!$(this).hasClass('hidecontroled'))
            $(this).find('div.superfile-title-block.hovered').css('margin-top', -50);
    })
}

setInterval(function () {
    heightFix();
}, 300);

$(document).on('mouseover', 'div.superfile', function () {
    $(this).find('div.superfile-title-block').addClass('hovered')

})

$(document).on('mouseout', 'div.superfile', function () {
    $(this).find('div.superfile-title-block').removeClass('hovered')
})




