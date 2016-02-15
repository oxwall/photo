/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.photo
 * @since 1.7.6
 */
(function( $, params, logic )
{
    $.event.props.push('dataTransfer');

    var parameters = $.extend({
        fileType: ['image/jpeg', 'image/png', 'image/gif']
    }, params);

    this.ajaxPhotoUploader = logic.call(this, $, parameters);
}.call(window, jQuery, window.ajaxPhotoUploadParams, function( $, params, undf )
{
    var root = this, UPLOAD_THREAD_COUNT = 3;

    var PhotoFile = (function()
    {
        var index = 0;

        function PhotoFile( file )
        {
            if ( !(this instanceof PhotoFile) )
            {
                return new PhotoFile(file);
            }

            if ( !(file instanceof File) )
            {
                throw new TypeError('"File" required');
            }

            this.node = $('#slot-prototype').clone();
            this.file = file;
            this.index = ++index;
        }

        PhotoFile.prototype.isAvailableFileSize = function()
        {
            return this.file.size <= params.maxFileSize;
        };

        PhotoFile.prototype.isAvailableFileType = function()
        {
            return params.fileType.indexOf(this.file.type.toLowerCase()) !== -1;
        };

        PhotoFile.prototype.createSlot = fluent(function()
        {
            this.node.attr('id', 'slot-' + this.index);
            FileManager().node.append(this.node);
        });

        PhotoFile.prototype.initHashtagEditor = fluent(function()
        {
            var file = this;
            var editor = this.editor = root.CodeMirror.fromTextArea(this.node.find('textarea')[0], {
                mode: 'text/hashtag',
                lineWrapping: true,
                extraKeys: {Tab: false}
            });

            editor.setValue(OW.getLanguageText('photo', 'describe_photo'));
            editor.on('blur', function( editor )
            {
                var value = file.description = editor.getValue().trim(), lineCount;

                if ( value.length === 0 || value === OW.getLanguageText('photo', 'describe_photo') )
                {
                    $(editor.display.wrapper).addClass('invitation');
                    editor.setValue(OW.getLanguageText('photo', 'describe_photo'));
                }
                else if ( (lineCount = editor.lineCount()) > 3 )
                {
                    editor.setLine(2, editor.getLine(2).substring(0, 20) + '...');

                    for ( var i = 3; i < lineCount; i++ )
                    {
                        editor.removeLine(3);
                    }
                }
                else
                {
                    var limit;

                    switch ( lineCount )
                    {
                        case 1: limit = 70; break;
                        case 2: limit = 50; break;
                        case 3: limit = 20; break;
                    }

                    if ( value.length > limit )
                    {
                        editor.setValue(value.substring(0, limit) + '...');
                    }
                }

                editor.setSize('100%', 58 + 'px');
                file.node.find('.ow_photo_preview_image').removeClass('ow_photo_preview_image_active');

                if ( FileManager().node.find('.ow_photo_preview_image_active').length === 0 )
                {
                    FileManager().node.removeClass('ow_photo_preview_image_filtered');
                }
            });
            editor.on('focus', function( editor )
            {
                $(editor.display.wrapper).removeClass('invitation');

                if ( file.description )
                {
                    editor.setValue(file.description);
                }
                else
                {
                    var value = editor.getValue().trim();

                    if ( value === OW.getLanguageText('photo', 'describe_photo') )
                    {
                        editor.setValue('');
                    }
                }

                var height = editor.doc.height;

                switch ( true )
                {
                    case height <= 42:
                        editor.setSize('100%', 58 + 'px');
                        break;
                    case height > 42 && height < 108:
                        editor.setSize('100%', height + 14 + 'px');
                        editor.scrollTo(0, height + 14);
                        break;
                    default:
                        editor.setSize('100%', '108px');
                        editor.scrollTo(0, 108);
                        break;
                }

                setTimeout(function()
                {
                    editor.setCursor(editor.lineCount(), 0);
                }, 1);

                FileManager().node.addClass('ow_photo_preview_image_filtered');
                file.node.find('.ow_photo_preview_image').addClass('ow_photo_preview_image_active');
            });
            editor.on('change', function( editor )
            {
                var height = editor.doc.height;

                switch ( true )
                {
                    case height <= 42:
                        editor.setSize('100%', 58 + 'px');
                        break;
                    case height > 42 && height < 108:
                        editor.setSize('100%', height + 14 + 'px');
                        break;
                    default:
                        editor.setSize('100%', '108px');
                        break;
                }
            });
            editor.setSize('100%', 58 + 'px');
        });

        PhotoFile.prototype.updateSlot = fluent(function( fileUrl, id )
        {
            if ( !fileUrl || !id ) return;

            this.id = id;

            var self = bind(this);

            this.node.find('.ow_photo_preview_x').on('click', self(this.destroySlot));
            this.node.find('.ow_photo_preview_rotate').on('click', self(function()
            {
                var rotate = (this.rotate || 0) + 90;
                var rotateStr = 'rotate(' + rotate + 'deg)';

                this.rotate = rotate;
                this.node.find('.ow_photo_preview_image').css({
                    "-ms-transform": rotateStr,
                    "-webkit-transform": rotateStr,
                    "transform": rotateStr
                });
            }));

            var img = new Image();

            img.onload = img.onerror = self(function()
            {
                 this.node.find('.ow_photo_preview_image')
                    .hide(0, function()
                    {
                        $(this)
                            .css('background-image', 'url(' + img.src + ')')
                            .removeClass('ow_photo_preview_loading')
                            .fadeIn(300);
                    });
                OW.trigger('photo.onRenderUploadSlot', [this.editor], this.node);
            });
            img.src = fileUrl;
        });

        PhotoFile.prototype.destroySlot = fluent(function()
        {
            this.node.animate({opacity: 0}, 300, function()
            {
                this.editor.setValue('');
                this.editor.clearHistory();
                this.node.remove();

                var fileManager = FileManager();

                if ( fileManager.cache.hasOwnProperty(this.index) )
                {
                    delete fileManager.cache[this.index];
                }
            }.bind(this));

            if ( this.id !== undf )
            {
                $.ajax({
                    url: params.deleteAction,
                    data: {id: this.id},
                    cache: false,
                    type: 'POST'
                });
            }
        });

        return PhotoFile;
    })();

    var FileManager = (function()
    {
        var instance = null;

        function FileManager()
        {
            if ( instance !== null )
            {
                return instance;
            }

            if ( !(this instanceof FileManager) )
            {
                return new FileManager();
            }

            this.node = $('#slot-area');
            this.files = [];
            this.cache = {};
            this.isRunned = false;
            instance = this;
        }

        FileManager.prototype.pushFiles = fluent(function( files )
        {
            if ( !files || !(files instanceof FileList) || files.length === 0 ) return;

            this.files = this.files.concat([].slice.call(files));
        });

        FileManager.prototype.uploadFiles = fluent(function( count )
        {
            if ( this.isRunned ) return;

            var files = this.files.splice(0, (+count || UPLOAD_THREAD_COUNT));

            if ( files.length === 0 )
            {
                this.isRunned = false;

                return;
            }

            this.isRunned = true;
            files.forEach(function( file )
            {
                this.upload(file);
            }, this);
        });

        FileManager.prototype.upload = fluent(function( file )
        {
            if ( !file || !(file instanceof File) ) return;

            var photoFile = PhotoFile(file), self = bind(this);

            if ( !photoFile.isAvailableFileSize() )
            {
                OW.error(OW.getLanguageText('photo', 'size_limit', {
                    name: photoFile.file.name,
                    size: (params.maxFileSize / 1048576) // 1024 * 1024 Convert to readable format
                }));
                this.uploadNewOne();
            }
            else if ( !photoFile.isAvailableFileType() )
            {
                OW.error(OW.getLanguageText('photo', 'type_error', {
                    name: photoFile.file.name
                }));
                this.uploadNewOne();
            }
            else
            {
                var formData = new FormData();

                formData.append('file', file);

                $.ajax({
                    isPhotoUpload: true,
                    url: params.actionUrl,
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    type: 'POST',
                    timeout: 60000,
                    beforeSend: function( jqXHR, settings )
                    {
                        photoFile.createSlot().initHashtagEditor();
                    },
                    success: self(function( data, textStatus, jqXHR )
                    {
                        if ( data && data.status )
                        {
                            switch ( data.status )
                            {
                                case 'success':
                                    this.caching(
                                        photoFile.updateSlot(data.fileUrl, data.id)
                                    );
                                    break;
                                case 'error':
                                default:
                                    photoFile.destroySlot();

                                    OW.error(data.msg);
                                    break;
                            }
                        }
                        else
                        {
                            photoFile.destroySlot();
                            OW.error(OW.getLanguageText('photo', 'not_all_photos_uploaded'));
                        }
                    }),
                    error: function( jqXHR, textStatus, errorThrown )
                    {
                        OW.error(textStatus + ': ' + file.name);
                        photoFile.destroySlot();

                        throw textStatus;
                    },
                    complete: self(function( jqXHR, textStatus )
                    {
                        if ( textStatus === 'success' && jqXHR.responseText.length === 0 )
                        {
                            photoFile.destroySlot();
                        }

                        this.uploadNewOne();
                    })
                });
            }
        });

        FileManager.prototype.uploadNewOne = fluent(function()
        {
            this.isRunned = false;
            this.uploadFiles(1);
        });

        FileManager.prototype.caching = fluent(function( photoFile )
        {
            if ( !(photoFile instanceof PhotoFile) )
            {
                throw new TypeError('"PhotoFile" required');
            }

            this.cache[photoFile.index] = photoFile;
        });

        FileManager.prototype.hasFiles = function()
        {
            return root.Object.keys(this.cache).length !== 0;
        };

        FileManager.prototype.destroy = function()
        {
            instance = null;
        };

        return FileManager;
    })();

    var AlbumListManager = (function()
    {
        function AlbumListManager( form )
        {
            this.form = form;
            this.albumList = $('.ow_dropdown_list', form);
            this.albumInput = $('input[name="album"]', form);
            this.spinner = $('.upload_photo_spinner', form);
        }

        AlbumListManager.prototype.bindEvents = fluent(function()
        {
            var self = bind(this);

            this.spinner.add(this.albumInput).on('click', self(function( event )
            {
                if ( this.albumList.is(':visible') )
                {
                    this.hideAlbumList();
                }
                else
                {
                    this.showAlbumList();
                }

                event.stopPropagation();
            }));

            this.albumList.find('li').on('click', self(function()
            {
                this.hideAlbumList();
                root.owForms['ajax-upload'].removeErrors();
            })).eq(0).on('click', self(function()
            {
                $('.new-album', this.form).show();
                this.albumInput.val(root.OW.getLanguageText('photo', 'create_album'));
                $('input[name="album-name"]', this.albumForm).val('');
                $('textarea', this.albumForm).val('');
            })).end().slice(2).on('click', self(function( event )
            {
                $('.new-album', this.albumForm).hide();
                this.albumInput.val($(event.target).data('name'));
                $('input[name="album-name"]', this.albumForm).val(this.albumInput.val());
                $('textarea', this.albumForm).val('');
            }));

            $(root.document).on('click',':not(#photo-album-list)', self(function()
            {
                if ( this.albumList.is(':visible') )
                {
                    this.hideAlbumList();
                }
            }));
        });

        AlbumListManager.prototype.hideAlbumList = fluent(function()
        {
            this.albumList.hide();
            this.spinner.removeClass('ow_dropdown_arrow_up').addClass('ow_dropdown_arrow_down');
        });

        AlbumListManager.prototype.showAlbumList = fluent(function()
        {
            this.albumList.show();
            this.spinner.removeClass('ow_dropdown_arrow_down').addClass('ow_dropdown_arrow_up');
        });

        return AlbumListManager;
    })();

    root.OW.bind('base.onFormReady.ajax-upload', function()
    {
        var getValue = function( key )
        {
            return function()
            {
                var value = this.input.value.trim();

                if ( value.length === 0 || value === OW.getLanguageText('photo', key).trim() )
                {
                    return  '';
                }

                return value;
            };
        };

        this.getElement('album-name').getValue = getValue('album_name');
        this.getElement('description').getValue = getValue('album_desc');

        this.bind('submit', function( data )
        {
            var invitation = OW.getLanguageText('photo', 'describe_photo').trim();
            var fileManager = FileManager();

            root.Object.keys(fileManager.cache).forEach(function( index )
            {
                var file = this.cache[index];
                var value = (file.description || file.editor.getValue()).trim();

                if ( value.length === 0 || value === invitation )
                {
                    data['desc[' + file.id + ']'] = '';
                }
                else
                {
                    data['desc[' + file.id + ']'] = value;
                }

                data['rotate[' + file.id + ']'] = file.rotate;
            }, fileManager);
        });

    });

    var _a = $('<a>', {class: 'ow_hidden ow_content a'}).appendTo(root.document.body);
    root.OW.addCss('.cm-hashtag{cursor:pointer;color:' + _a.css('color') + '}');
    _a.remove();

    return root.Object.freeze({
        init: function()
        {
            var dropArea = $('#drop-area').off(),
                dropAreaLabel = $('#drop-area-label').off(),
                uploadInputFile = $('input:file', '#upload-form').off();

            var fileManager = new FileManager();

            dropArea.add(dropAreaLabel).on({
                click: function()
                {
                    uploadInputFile.trigger('click');
                },
                drop: function( event )
                {
                    event.preventDefault();
                    fileManager.pushFiles(event.dataTransfer.files).uploadFiles();

                    dropArea.css('border', 'none');
                    dropAreaLabel.html(OW.getLanguageText('photo', 'dnd_support'));
                },
                dragenter: function()
                {
                    dropArea.css('border', '1px dashed #E8E8E8');
                    dropAreaLabel.html(OW.getLanguageText('photo', 'drop_here'));
                },
                dragleave: function()
                {
                    dropArea.css('border', 'none');
                    dropAreaLabel.html(OW.getLanguageText('photo', 'dnd_support'));
                }
            });

            uploadInputFile.on('change', function()
            {
                fileManager.pushFiles(this.files).uploadFiles();
            });

            var albumManager = new AlbumListManager($('#photo-album-form'));
            albumManager.bindEvents();

            OW.bind('photo.onCloseUploaderFloatBox', function()
            {
                fileManager.destroy();
            });

            $.ajaxPrefilter(function(options, origOptions, jqXHR)
            {
                if ( fileManager.isRunned && options.isPhotoUpload !== true )
                {
                    jqXHR.abort();

                    typeof origOptions.success == 'function' && (origOptions.success.call(options, {}));
                    typeof origOptions.complete == 'function' && (origOptions.complete.call(options, {}));

                }
            });
        },
        isHasData: function()
        {
            return FileManager().hasFiles();
        }
    });

    function bind( context )
    {
        return function( f )
        {
            return f.bind(context);
        };
    }

    function fluent( f )
    {
        return function()
        {
            f.apply(this, arguments);

            return this;
        };
    }
}));
