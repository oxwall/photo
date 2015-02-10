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
 * @since 1.6.1
 */
(function( $ )
{
    var _vars = $.extend({}, (albumParams || {}), {albumNameList: [], photoIdList: [], mode: 'view'}),
    _elements = {},
    _methods = {
        setEditMode: function()
        {
            _vars.photoIdList.length = 0;
            _vars.mode = 'edit';
            
            $('.ow_photo_item_wrap', $(document.getElementById('browse-photo')).addClass('ow_photo_edit_mode')).each(function()
            {
                _methods.converPhotoItemToEditMode.call(this);
            });
            
            _elements.editBtn.detach();
            _elements.doneCont.appendTo($('.ow_photo_album_toolbar', _elements.editCont)).show();
            _elements.editForm.appendTo(_elements.albumInfo).show();
            _elements.coverBtn.appendTo($('.ow_photo_album_cover', _elements.albumInfo)).show();
            _elements.menu.insertAfter(_elements.editCont).show();

            if ( _vars.album.name != OW.getLanguageText('photo', 'newsfeed_album').trim() )
            {
                _elements.editCont.addClass('ow_photo_album_edit');
            }
            else
            {
                _elements.editCont.find('.ow_photo_album_description').hide();
                _elements.editCont.find('.ow_photo_album_description_textarea').show();
            }
        },
        setViewMode: function()
        {
            try
            {
                owForms.albumEditForm.validate();
            }
            catch ( e )
            {
                return;
            }
            
            if ( _vars.albumNameList.indexOf(owForms.albumEditForm.elements.albumName.getValue().trim()) !== -1 )
            {
                OW.error(OW.getLanguageText('photo', 'album_name_error'));
                
                return;
            }
            
            $('.ow_photo_item_wrap', $(document.getElementById('browse-photo')).removeClass('ow_photo_edit_mode')).each(function()
            {
                _methods.converPhotoItemToViewMode.call(this);
            });

            owForms.albumEditForm.submitForm();
            history.pushState(null, document.title, window.location.pathname);
            _vars.photoIdList.length = 0;
            _vars.mode = 'view';
            
            $('.set_as_cover', _elements.menu).addClass('ow_bl_disabled').off();

            _elements.doneCont.detach();
            _elements.coverBtn.detach();
            _elements.editBtn.appendTo($('.ow_photo_album_toolbar', _elements.editCont));
            _elements.menu.detach();
            _elements.editForm.detach();
            _elements.albumInfo.find('.ow_photo_album_name').html(owForms.albumEditForm.elements.albumName.getValue());
            _elements.albumInfo.find('.ow_photo_album_description').html(owForms.albumEditForm.elements.desc.getValue());

            if ( _vars.album.name != OW.getLanguageText('photo', 'newsfeed_album').trim() )
            {
                _elements.editCont.removeClass('ow_photo_album_edit');
            }
            else
            {
                _elements.editCont.find('.ow_photo_album_description').show();
                _elements.editCont.find('.ow_photo_album_description_textarea').hide();
            }
            
            OW.info(OW.getLanguageText('photo', 'photo_album_updated'));
        },
        converPhotoItemToEditMode: function()
        {
            var self = $(this);
            
            if ( _elements.selectAll[0].checked )
            {
                self.find('.ow_photo_item').addClass('ow_photo_item_checked');
                _vars.photoIdList.push(+self.data('photoId'));
            }
            
            self.find('img:first').after(_elements.checkbox.clone().on('click', function()
            {
                var closest = $(this).closest('.ow_photo_item');
                
                if ( closest.hasClass('ow_photo_item_checked') )
                {
                     closest.removeClass('ow_photo_item_checked');
                     _vars.photoIdList.splice(_vars.photoIdList.indexOf(+closest.parent().data('photoId')), 1);
                }
                else
                {
                    closest.addClass('ow_photo_item_checked');
                     _vars.photoIdList.push(+closest.parent().data('photoId'));
                }
                
                _vars.photoIdList.length === 1 ? $('.set_as_cover', _elements.menu).removeClass('ow_bl_disabled').on('click', _methods.makeAsCover) : $('.set_as_cover', _elements.menu).addClass('ow_bl_disabled').off();
            }));
        },
        converPhotoItemToViewMode: function()
        {
            $(this).find('.ow_photo_item').removeClass('ow_photo_item_checked').find('.ow_photo_chekbox_area').remove();
        },
        makeAsCover: function( event )
        {
            event.stopImmediatePropagation();
            
            var img, item = document.getElementById('photo-item-' + _vars.photoIdList[0]), data = $(item).data(), dim;
            
            if ( _vars.isClassic )
            {
                img = $('img.ow_hidden', item)[0];
            }
            else
            {
                img = $('img', item)[0];
            }
            
            if ( data.dimension && data.dimension.length )
            {
                try
                {
                    var dimension = JSON.parse(data.dimension);

                    dim = dimension.main;
                }
                catch( e )
                {
                    dim = [img.naturalWidth, img.naturalHeight];
                }
            }
            else
            {
                dim = [img.naturalWidth, img.naturalHeight];
            }

            if ( dim[0] < 330 || dim[1] < 330 )
            {
                OW.error(OW.getLanguageText('photo', 'to_small_cover_img'));

                return;
            }
    
            window.albumCoverMakerFB = OW.ajaxFloatBox('PHOTO_CMP_MakeAlbumCover', [_vars.album.id, _vars.photoIdList[0]], {
                title: OW.getLanguageText('photo', 'set_as_cover_label'),
                width: '700',
                onLoad: function()
                {
                    window.albumCoverMaker.init();
                }
            });
        },
        checkPhotoIsSelected: function()
        {
            if ( _vars.photoIdList.length === 0 )
            {
                alert(OW.getLanguageText('photo', 'no_photo_selected'));

                return false;
            }
            
            return true;
        },
        createNewAlbumAndMove: function()
        {
            var fb = OW.ajaxFloatBox('PHOTO_CMP_CreateAlbum', [_vars.album.id, _vars.photoIdList.join(',')], {
                title: OW.getLanguageText('photo', 'move_to_new_album'),
                width: '500',
                onLoad: function()
                {
                    owForms['add-album'].bind('success', function( data )
                    {
                        fb.close();

                        _methods.movePhotoSuccess(data);
                    });
                }
            });
        },
        movePhoto: function()
        {
            $('.ow_context_action_list li', _elements.menu).slice(1).remove();
            
            $.ajax({
                url: _vars.url,
                type: 'POST',
                dataType: 'json',
                cache: false,
                data: 
                {
                    "ajaxFunc": 'ajaxMoveToAlbum',
                    "from-album": _vars.album.id,
                    "to-album": $(this).attr('rel'),
                    "photos": _vars.photoIdList.join(','),
                    "album-name": $(this).html()
                },
                success: _methods.movePhotoSuccess,
                error: function( jqXHR, textStatus, errorThrown )
                {
                    OW.error(textStatus);

                    throw textStatus;
                }
            });
        },
        movePhotoSuccess: function( data )
        {
            if ( data.result )
            {
                OW.info(OW.getLanguageText('photo', 'photo_success_moved'));
                $('.ow_photo_album_cover', _elements.albumInfo).css('background-image', 'url(' + data.coverUrl + ')');
                
                if ( data.isHasCover === false )
                {
                    _elements.coverBtn.remove();
                    _elements.coverBtn = $();
                }
                
                if ( !$.isEmptyObject(data.albumNameList) )
                {
                    _vars.albumNameList.length = 0;
                    $('.ow_context_action_list li', _elements.menu).slice(1).remove();
                    
                    var li = document.createElement('li');
                    li.appendChild((function()
                    {
                        var div = document.createElement('div');
                        div.className = 'ow_console_divider';
                        return div;
                    })());
                    
                    var list = $('ul.ow_context_action_list', _elements.menu);
                    list.append(li);
                    
                    $.each(data.albumNameList, function( id, albumName )
                    {
                        _vars.albumNameList.push(albumName);
                        
                        var li = document.createElement('li');
                        li.appendChild((function()
                        {
                            var a = document.createElement('a');
                            a.setAttribute('href', 'javascript://');
                            a.setAttribute('rel', id);
                            a.appendChild(document.createTextNode(albumName));
                            $(a).on('click', function()
                            {
                                if ( _methods.checkPhotoIsSelected() )
                                {
                                    _methods.movePhoto.call(this);
                                }
                            });
                            
                            return  a;
                        })());
                        
                        list.append(li);
                    });
                }
                
                browsePhoto.removePhotoItems(_vars.photoIdList.map(function( item )
                {
                    return 'photo-item-' + item;
                }));
                
                _vars.photoIdList.length = 0;
            }
            else
            {
                if ( data.msg )
                {
                    OW.error(data.msg);
                }
                else
                {
                    alert(OW.getLanguageText('photo', 'no_photo_selected'));
                }
            }
        },
        init: function()
        {
            OW.bind('photo.onRenderPhotoItem', function()
            {
                if ( _vars.mode !== 'edit' || $('.ow_photo_chekbox_area', this).length !== 0 )
                {
                    return;
                }
                
                _methods.converPhotoItemToEditMode.call(this);
            });
            OW.bind('photo.afterPhotoEdit', function( data )
            {
                if ( data && data.albumName && _vars.album.name.trim() != data.albumName.trim() )
                {
                    window.browsePhoto.removePhotoItems(['photo-item-' + data.id]);
                }
            });
            
            _elements.checkbox = $((function()
            {
                var e = document.createElement('div');
                e.className = 'ow_photo_chekbox_area';
                e.appendChild((function()
                {
                    var e = document.createElement('div');
                    e.className = 'ow_photo_checkbox';

                    return e;
                })());

                return e;
            })());

            _elements.editCont = $(document.getElementById('album-edit'));
            _elements.albumInfo = $('.ow_photo_album_info', _elements.editCont);
            _elements.editForm = $('form', _elements.albumInfo);
            (_elements.editBtn = $('.edit_btn', _elements.editCont)).find('a').on('click', _methods.setEditMode);
            _elements.coverBtn = $('.ow_lbutton', _elements.albumInfo).on('click', function()
            {
                var img = $('img.cover_orig', _elements.editCont)[0]

                if ( img.naturalHeight < 330 || img.naturalWidth < 330 )
                {
                    OW.error(OW.getLanguageText('photo', 'to_small_cover_img'));

                    return;
                }
                
                window.albumCoverMakerFB = OW.ajaxFloatBox('PHOTO_CMP_MakeAlbumCover', [_vars.album.id], {
                    title: OW.getLanguageText('photo', 'crop_photo_title'),
                    width: '700',
                    onLoad: function()
                    {
                        window.albumCoverMaker.init();
                    }
                });
            }).detach();
            _elements.albumCropBtn = $('#album-crop-btn').on('click', _methods.saveCover);

            (_elements.doneCont = $('.edit_done', _elements.editCont).detach()).find('.done').on('click', _methods.setViewMode);
            _elements.doneCont.find('.delete_album').on('click', function()
            {
                if ( !confirm(OW.getLanguageText('photo', 'are_you_sure')) )
                {
                    return;
                }

                $.ajax({
                    url: _vars.url,
                    type: 'POST',
                    dataType: 'json',
                    cache: false,
                    data: 
                    {
                        ajaxFunc: 'ajaxDeletePhotoAlbum',
                        entityId: _vars.album.id
                    },
                    success: function( data )
                    {
                        if ( data.result )
                        {
                            OW.info(data.msg);
                            window.location = data.url;
                        }
                        else
                        {
                            alert(OW.getLanguageText('photo', 'no_photo_selected'));
                        }
                    },
                    error: function( jqXHR, textStatus, errorThrown )
                    {
                        OW.error(textStatus);

                        throw textStatus;
                    }
                });
            });
            _elements.menu = $(document.getElementById('photo-menu')).detach();

            (_elements.selectAll = _elements.menu.find('input:checkbox')).on('click', function()
            {
                $('.set_as_cover', _elements.menu).addClass('ow_bl_disabled').off();
                _vars.photoIdList.length = 0;

                if ( this.checked )
                {
                    $('.ow_photo_item', document.getElementById('browse-photo')).addClass('ow_photo_item_checked');
                    $('.ow_photo_item_wrap', document.getElementById('browse-photo')).each(function()
                    {
                        _vars.photoIdList.push(+$(this).data('photoId'));
                    });

                    _vars.photoIdList.length === 1 ? $('.set_as_cover', _elements.menu).removeClass('ow_bl_disabled').on('click', _methods.makeAsCover) : $('.set_as_cover', _elements.menu).addClass('ow_bl_disabled').off();
                }
                else
                {
                    $('.ow_photo_item', document.getElementById('browse-photo')).removeClass('ow_photo_item_checked');
                }
            });

            $('.delete', _elements.menu).on('click', function()
            {
                if ( _vars.photoIdList.length === 0 )
                {
                    alert(OW.getLanguageText('photo', 'no_photo_selected'));

                    return;
                }

                if ( !confirm(OW.getLanguageText('photo', 'confirm_delete_photos')) )
                {
                    return;
                }

                $.ajax({
                    url: _vars.url,
                    type: 'POST',
                    dataType: 'json',
                    cache: false,
                    data: 
                    {
                        ajaxFunc: 'ajaxDeletePhotos',
                        albumId: _vars.album.id,
                        photoIdList: _vars.photoIdList
                    },
                    success: function( data )
                    {
                        if ( data.result )
                        {
                            $('.ow_photo_album_cover', _elements.albumInfo).css('background-image', 'url(' + data.coverUrl + ')');
                
                            if ( data.isHasCover === false )
                            {
                                _elements.coverBtn.remove();
                                _elements.coverBtn = $();
                            }
                            
                            if ( _vars.photoIdList.length === 1 )
                            {
                                OW.info(OW.getLanguageText('photo', 'photo_deleted'));
                            }
                            else
                            {
                                OW.info(OW.getLanguageText('photo', 'photos_deleted'));
                            }

                            if ( data.url !== undefined )
                            {
                                window.location = data.url;
                            }
                            else
                            {
                                browsePhoto.removePhotoItems(_vars.photoIdList.map(function( item )
                                {
                                    return 'photo-item-' + item;
                                }));

                                _vars.photoIdList.length = 0;
                            }
                        }
                        else
                        {
                            alert(OW.getLanguageText('photo', 'no_photo_selected'));
                        }
                    },
                    error: function( jqXHR, textStatus, errorThrown )
                    {
                        OW.error(textStatus);

                        throw textStatus;
                    }
                });
            });

            $('.ow_context_action_list a', _elements.menu)
                .on('click', function( event )
                {
                    if ( !_methods.checkPhotoIsSelected() )
                    {
                        event.stopImmediatePropagation();

                        return false;
                    }
                })
                .eq(0).on('click', _methods.createNewAlbumAndMove)
                .end().slice(1).on('click', _methods.movePhoto);

            if ( window.location.hash === '#edit' )
            {
                _methods.setEditMode();
            }
        }
    };

    window.photoAlbum = Object.defineProperties({}, {
        init: {value: _methods.init},
        setCoverUrl: {
            value: function( url, isHasCover )
            {
                $('.ow_photo_album_cover', _elements.albumInfo).css('background-image', 'url(' + url + ')');
                
                if ( isHasCover === false )
                {
                    _elements.coverBtn.remove();
                    _elements.coverBtn = $();
                }
            }
        }
    });
})(jQuery);
