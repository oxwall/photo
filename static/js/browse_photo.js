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
{'use strict';
    var actionStr;

    switch ( params.listType )
    {
        case 'albums':
            actionStr = 'setURL,setDescription';
            break;
        case 'albumPhotos':
            actionStr = 'setRate,setCommentCount,setDescription,bindEvents';
            break;
        case 'userPhotos':
            actionStr = 'setAlbumInfo,setRate,setCommentCount,setDescription,bindEvents';
            break;
        default:
            actionStr = 'setAlbumInfo,setUserInfo,setRate,setCommentCount,setDescription,bindEvents';
            break;
    }

    this.browsePhoto = logic.call(this, $, $.extend({actions: actionStr.split(',')}, params));
}.call(window, window.jQuery, window.browsePhotoParams, function( $, params, undf )
{'use strict';
    var root = this, utils = photoUtils;

    var BaseObject = (function()
    {
        function BaseObject()
        {
            this.self = utils.bind(this);
        }

        return BaseObject;
    })();

    var Slot = (function( BaseObject )
    {
        utils.extend(Slot, BaseObject);

        function Slot( photo, listType )
        {
            Slot._super.call(this);

            this.node = $('#browse-photo-item-prototype').clone();
            this.node.attr('id', 'photo-item-' + photo.id);
            this.data = $.extend({listType: listType}, photo);
        }

        Slot.prototype.setInfo = utils.fluent(function( data )
        {
            $.extend(this.data, data);

            params.actions.forEach(function( action )
            {
                this[action]();
            }, this);
        });

        Slot.prototype.setURL = utils.fluent(function()
        {
            this.node.off().on('click', this.self(function()
            {
                window.location = this.data.albumUrl;
            }));
        });

        Slot.prototype.setDescription = utils.fluent(function()
        {
            var description, data = this.data;

            if ( data.listType != 'albums' )
            {
                description = utils.descToHashtag(utils.truncate(data.description), utils.getHashtags(data.description));
            }
            else
            {
                description = OW.getLanguageText('photo', 'album_description', {
                    desc: utils.truncate(data.name, 50),
                    count: data.count || 0
                });
            }

            var event = {text: description};
            OW.trigger('photo.onSetDescription', event);
            description = event.text.trim();

            if ( description )
            {
                this.node.find('.ow_photo_item_info_description').html(description).show();
            }
            else
            {
                this.node.find('.ow_photo_item_info_description').hide();
            }
        });

        Slot.prototype.setRate = utils.fluent(function()
        {
            var data = this.data;

            if ( !data.rateInfo || data.userScore === undf )
            {
                return;
            }

            if ( data.userScore > 0 )
            {
                this.node.find('.rate_title').html(OW.getLanguageText('photo', 'rating_your', {
                    count: data.rateInfo.rates_count,
                    score: data.userScore
                }));
            }
            else
            {
                this.node.find('.rate_title').html(OW.getLanguageText('photo', 'rating_total', {
                    count: data.rateInfo.rates_count
                }));
            }

            this.node.find('.active_rate_list').css('width', (data.rateInfo.avg_score * 20) + '%');

            var self = this;

            var event = {
                canRate: true,
                photo: this.data
            };
            OW.trigger('photo.canRate', event, this.node);

            if ( !event.canRate ) return;

            $('.rate_item', this.node).off().on('click', function( event )
            {
                event.stopPropagation();

                if ( params.rateUserId === 0 )
                {
                    OW.error(OW.getLanguageText('base', 'rate_cmp_auth_error_message'));

                    return;
                }
                else if ( data.userId == params.rateUserId )
                {
                    OW.error(OW.getLanguageText('base', 'rate_cmp_owner_cant_rate_error_message'));

                    return;
                }

                var rate = $(this).index() + 1;

                $.ajax({
                    url: params.getPhotoURL,
                    dataType: 'json',
                    data: {
                        ajaxFunc: 'ajaxRate',
                        entityId: data.id,
                        rate: rate,
                        ownerId: data.userId
                    },
                    cache: false,
                    type: 'POST',
                    success: function( result, textStatus, jqXHR )
                    {
                        if ( result )
                        {
                            switch ( result.result )
                            {
                                case true:
                                    OW.info(result.msg);
                                    self.node.find('.active_rate_list').css('width', (result.rateInfo.avg_score * 20) + '%');
                                    self.node.find('.rate_title').html(OW.getLanguageText('photo', 'rating_your', {
                                        count: result.rateInfo.rates_count,
                                        score: rate
                                    }));
                                    self.data.rateInfo.avg_score = result.rateInfo.avg_score;
                                    break;
                                case false:
                                default:
                                    OW.error(result.error);
                                    break;
                            }
                        }
                        else
                        {
                            OW.error('Server error');
                        }
                    },
                    error: function( jqXHR, textStatus, errorThrown )
                    {
                        throw textStatus;
                    }
                });
            }).hover(function()
            {
                $(this).prevAll().add(this).addClass('active');
                self.node.find('.active_rate_list').css('width', '0px');
            }, function()
            {
                $(this).prevAll().add(this).removeClass('active');
                self.node.find('.active_rate_list').css('width', (data.rateInfo.avg_score * 20) + '%');
            });
        });

        Slot.prototype.setCommentCount = utils.fluent(function()
        {
            this.node.find('.ow_photo_comment_count a').html('<b>' + this.data.commentCount + '</b>');
        });

        Slot.prototype.setAlbumInfo = utils.fluent(function()
        {
            var info = this.node.find('.ow_photo_item_info_album').show()
                .find('a').attr('href', this.data.albumUrl);

            if ( params.classicMode )
            {
                info.find('b').html(this.data.albumName);
            }
            else
            {
                info.html(this.data.albumName);
            }
        });

        Slot.prototype.setUserInfo = utils.fluent(function()
        {
            var user = this.node.find('.ow_photo_by_user').show()
                .find('a').attr('href', this.data.userUrl);

            if ( params.classicMode )
            {
                user.find('b').html(this.data.userName);
            }
            else
            {
                user.html(this.data.userName);
            }
        });

        Slot.prototype.bindEvents = utils.fluent(function()
        {
            var event = {
                selectors: [
                    '.ow_photo_item img'
                ]
            };
            OW.trigger('photo.collectShowPhoto', event);
            this.node.off().on('click', event.selectors.join(','), this.self(function()
            {
                var data = this.data,
                    img = this.node.find('img')[0],
                    dim = {mainUrl: data.url};

                if ( data.dimension && data.dimension.length )
                {
                    try
                    {
                        dim.main = JSON.parse(data.dimension).main;
                    }
                    catch( e )
                    {
                        dim.main = [img.naturalWidth, img.naturalHeight];
                    }
                }
                else
                {
                    dim.main = [img.naturalWidth, img.naturalHeight];
                }

                photoView.setId(data.id, data.listType, {}, dim, data);
            }));
        });

        return Slot;
    })(BaseObject);

    var SlotManager = (function( BaseObject )
    {
        utils.extend(SlotManager, BaseObject);

        var instance;

        function SlotManager()
        {
            if ( instance !== undf )
            {
                return instance;
            }

            if ( !(this instanceof SlotManager) )
            {
                return new SlotManager();
            }

            SlotManager._super.call(this);

            this.list = new List(this);
            this.request = null;
            this.listType = params.listType || 'latest';

            this.reset();

            instance = this;
        }

        SlotManager.prototype.load = utils.fluent(function( data )
        {
            if ( this.isCompleted ) return;

            if ( this.request && this.request.readyState !== 4 )
            {
                try
                {
                    this.request.abort();
                }
                catch ( ignore ) { }
            }

            this.request = $.ajax({
                url: params.getPhotoURL,
                dataType: 'json',
                data: $.extend({
                    ajaxFunc: params.action || 'getPhotoList',
                    listType: params.listType || 'latest',
                    offset: ++this.offset
                }, this.getMoreData(), data || {}),
                cache: false,
                type: 'POST',
                beforeSend: this.self(function( jqXHR, settings )
                {
                    this.list.showLoader();
                    this.data = null;
                }),
                success: this.self(function( data, textStatus, jqXHR )
                {
                    if ( data && data.status )
                    {
                        utils.includeScriptAndStyle(data.scripts);

                        switch ( data.status )
                        {
                            case 'success':
                                this.buildSlotList(data.data);
                                break;
                            case 'error':
                            default:
                                OW.error(data.msg);
                                break;
                        }
                    }
                    else
                    {
                        OW.error('Server error');
                    }
                }),
                error: function( jqXHR, textStatus, errorThrown )
                {
                    throw textStatus;
                }
            });
        });

        SlotManager.prototype.getMoreData = function()
        {
            switch ( this.listType )
            {
                case 'albums':
                case 'userPhotos':
                    return $.extend({}, (this.modified ? {offset: 1, idList: Object.keys(this.cache)} : {}), {
                        userId: params.userId
                    });
                case 'albumPhotos':
                    return $.extend({}, (this.modified ? {offset: 1, idList: Object.keys(this.cache)} : {}), {
                        albumId: params.albumId
                    });
                default:
                    if ( ['user', 'hash', 'desc', 'all', 'tag'].indexOf(this.listType) !== -1 )
                    {
                        return {searchVal: SearchEngine().getSearchValue()};
                    }

                    return {};
            }
        };

        SlotManager.prototype.buildSlotList = utils.fluent(function( data )
        {
            if ( !data || utils.isEmptyArray(data.photoList) )
            {
                this.list.hideLoader();

                return;
            }
            else if ( data.photoList.length < 20 )
            {
                this.isCompleted = true;
            }

            this.data = data;
            this.uniqueList = data.unique;

            this.backgroundLoad(this.data.photoList).buildNewOne();
        });

        SlotManager.prototype.buildPhotoItem = utils.fluent(function( slot )
        {
            if ( slot === undf )
            {
                this.list.hideLoader();

                this.isTimeToLoad() ? this.unbindUI().load() : this.bindUI();

                return;
            }
            else if ( slot.unique !== this.uniqueList )
            {
                return;
            }

            var slotItem = new Slot(slot, this.listType);
            var slotData = this.getSlotData(slot);

            slotItem.setInfo(slotData);
            OW.trigger('photo.onRenderPhotoItem', [slot], slotItem.node);

            this.list.buildByMode(slotItem);
            this.cache[slotItem.data.id] = slotItem;
        });

        SlotManager.prototype.removePhotoItems = function( idList )
        {
            if ( utils.isEmptyArray(idList) )
            {
                return false;
            }

            var removed = idList.reduce(this.self(function( result, item )
            {
                if ( this.cache.hasOwnProperty(item) )
                {
                    var slot = this.cache[item];

                    result.push(slot.data.id);
                    slot.node.remove();
                    delete this.cache[item];
                }

                return result;
            }), []);

            if ( removed.length !== 0 )
            {
                this.modified = true;
                this.list.reorder();

                if ( this.isTimeToLoad() )
                {
                    this.load();
                }

                OW.trigger('photo.onRemovePhotoItems', {removed: removed});
            }

            return removed;
        };

        SlotManager.prototype.getSlotData = function( slot )
        {
            if ( !slot )
            {
                throw new TypeError('"Slot" required');
            }

            return params.actions.reduce(this.self(function( data, action )
            {
                switch ( action )
                {
                    case 'setUserInfo':
                        data.userUrl = this.data.userUrlList[slot.userId];
                        data.userName = this.data.displayNameList[slot.userId];
                        break;
                    case 'setAlbumInfo':
                        data.albumUrl = this.data.albumUrlList[slot.albumId];
                        data.albumName = this.data.albumNameList[slot.albumId].name;
                        break;
                    case 'setRate':
                        data.rateInfo = this.data.rateInfo[slot.id];
                        data.userScore = this.data.userScore[slot.id];
                        break;
                    case 'setCommentCount':
                        data.commentCount = this.data.commentCount[slot.id];
                        break;
                }

                return data;
            }), {});
        };

        SlotManager.prototype.buildNewOne = utils.fluent(function()
        {
            if ( this.data && this.data.photoList )
            {
                this.buildPhotoItem(this.data.photoList.shift());
            }
        });

        SlotManager.prototype.isTimeToLoad = function()
        {
            var win = $(root), max = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.body.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight,
                document.documentElement.clientHeight
            );

            return max - (win.scrollTop() + win.height()) <= 200;
        };

        SlotManager.prototype.unbindUI = utils.fluent(function()
        {
            $(root).off('scroll.browse_photo resize.browse_photo');
        });

        SlotManager.prototype.bindUI = utils.fluent(function()
        {
            $(root).on('scroll.browse_photo resize.browse_photo', this.self(function()
            {
                if ( this.isTimeToLoad() )
                {
                    this.unbindUI().load();
                }
            }));
        });

        SlotManager.prototype.backgroundLoad = utils.fluent(function( list )
        {
            if ( utils.isEmptyArray(list) ) return;

            list.forEach(function( photo )
            {
                setTimeout(function( url )
                {
                    new Image().src = url;
                }, 1, photo.url);
            })
        });

        SlotManager.prototype.reset = utils.fluent(function()
        {
            this.data = this.uniqueList = null;
            this.offset = 0;
            this.isCompleted = false;
            this.cache = {};
        });

        SlotManager.prototype.updateSlot = utils.fluent(function( slotId, data )
        {
            if ( !this.cache.hasOwnProperty(slotId) ) return;

            this.cache[slotId].setInfo(data);
        });

        SlotManager.prototype.updateRate = utils.fluent(function( data )
        {
            var keys = ['entityId', 'userScore', 'avgScore', 'ratesCount'];
            var values = utils.getObjectValue(keys, data);

            if ( keys.length !== Object.keys(values).length ) return;

            this.updateSlot(values.entityId, {
                userScore: values.userScore,
                rateInfo: values
            });
        });

        SlotManager.prototype.updateAlbumPhotos = utils.fluent(function( photoId )
        {
            if ( photoId === undf || !this.cache.hasOwnProperty(photoId) ) return;

            var slot = this.cache[photoId];
            var albumId = slot.data.albumId;
            var slots = Object.keys(this.cache).reduce(this.self(function( result, slotId )
            {
                var slot = this.cache[slotId];

                if ( slot.data.albumId == albumId )
                {
                    result.push(slot.data.id);
                }

                return result;
            }), []);

            OW.trigger('photo.deleteCache', [slots]);

            this.getPhotoInfo(albumId, slots, this.self(function( data, textStatus, jqXHR )
            {
                if ( data && data.status === 'success' )
                {
                    utils.includeScriptAndStyle(data.scripts);
                    this.rebuildSlots(data.data);
                }
                else
                {
                    OW.error('Server error');
                }
            }));
        });

        SlotManager.prototype.getPhotoInfo = utils.fluent(function( albumId, photos, then )
        {
            $.ajax({
                url: params.getPhotoURL,
                dataType: 'json',
                data: {
                    ajaxFunc: 'getPhotoInfo',
                    albumId: albumId,
                    photos: photos
                },
                cache: false,
                type: 'POST',
                success: then,
                error: function( jqXHR, textStatus, errorThrown )
                {
                    throw textStatus;
                }
            });
        });

        SlotManager.prototype.rebuildSlots = utils.fluent(function( data )
        {
            if ( !data || utils.isEmptyArray(data.photoList) ) return;

            this.data = data;
            this.uniqueList = data.unique;
            this.backgroundLoad(data.photoList);

            this.data.photoList.forEach(function( photo )
            {
                if ( !this.cache.hasOwnProperty(photo.id) ) return;

                var slot = this.cache[photo.id];

                $.extend(slot.data, photo);
                this.updateSlot(photo.id, this.getSlotData(photo));
                OW.trigger('photo.onRenderPhotoItem', [photo], slot.node);

                if ( params.classicMode )
                {
                    slot.node.find('.ow_photo_item').css('background-image', 'url(' + slot.data.url + ')');
                    slot.node.find('img.ow_hidden').attr('src', slot.data.url);
                }
                else
                {
                    var img = slot.node.find('img').show()[0];

                    img.onload = img.onerror = this.self(function()
                    {
                        this.list.reorder();
                    });
                    img.src = slot.data.url;
                }

            }, this);
        });

        return SlotManager;
    })(BaseObject);

    var List = (function( BaseObject )
    {
        var instance, SLOT_OFFSET = 16;

        utils.extend(List, BaseObject);

        function List( slotManager )
        {
            if ( instance !== undf )
            {
                return instance;
            }

            if ( !(this instanceof List) )
            {
                return new List(slotManager);
            }

            List._super.call(this);

            this.slotManager = slotManager;

            this.content = $('#browse-photo');
            this.loader = $('#browse-photo-preloader');
            this.reset();

            instance = this;
        }

        List.prototype.showLoader = utils.fluent(function()
        {
            this.loader.insertAfter(this.content);
        });

        List.prototype.hideLoader = utils.fluent(function()
        {
            this.loader.detach();

            if ( this.content.is(':empty') )
            {
                this.content.append(
                    $('<div>', {style: 'text-align:center; padding-top: 24px;'}).html(OW.getLanguageText('photo', 'no_items'))
                );
            }
        });

        List.prototype.buildByMode = (function()
        {
            if ( params.classicMode )
            {
                return function( slot )
                {
                    slot.node.find('.ow_photo_item').css('background-image', 'url(' + slot.data.url + ')');
                    slot.node.find('img.ow_hidden').attr('src', slot.data.url);
                    slot.node.appendTo(this.content);
                    slot.node.fadeIn(100, this.self(function()
                    {
                        this.slotManager.buildNewOne();
                    }));
                };
            }
            else
            {
                if ( params.listType == 'albums' )
                {
                    return function( slot )
                    {
                        var img = slot.node.find('img')[0];

                        img.onerror = img.onload = this.self(function(){this.buildComplete(slot)});
                        img.src = slot.data.url;
                        slot.node.appendTo(this.content);
                    };
                }

                return function( slot )
                {
                    var offset = this.getOffset();

                    slot.node.css({
                        top: offset.top,
                        left: offset.left / (params.level || 4) * 100 + '%'
                    });

                    var img = slot.node.find('img')[0];

                    img.onload = img.onerror = this.self(function(){this.buildComplete(slot)});
                    img.src = slot.data.url;
                    slot.node.appendTo(this.content);
                    this.content.height(Math.max.apply(Math, this.photoListOrder));
                };
            }
        })();

        List.prototype.buildComplete = utils.fluent(function( slot )
        {
            slot.node.fadeIn(100, this.self(function()
            {
                if ( slot.data.unique !== this.slotManager.uniqueList )
                {
                    return;
                }

                var offset = this.getOffset();

                this.photoListOrder[offset.left] += slot.node.height() + SLOT_OFFSET;
                this.content.height(Math.max.apply(Math, this.photoListOrder));
                this.slotManager.buildNewOne();
            }));
        });

        List.prototype.reorder = utils.fluent(function()
        {
            if ( params.classicMode ) return;

            this.photoListOrder = this.photoListOrder.map(Number.prototype.valueOf, 0);

            $('.ow_photo_item_wrap', this.content).each(this.self(function( index, node )
            {
                var self = $(node), offset = this.getOffset();

                self.css({top: offset.top + 'px', left: offset.left / (params.level || 4) * 100 + '%'});
                this.photoListOrder[offset.left] += self.height() + SLOT_OFFSET;
            }));

            this.content.height(Math.max.apply(Math, this.photoListOrder));
        });

        List.prototype.reset = utils.fluent(function()
        {
            this.photoListOrder = Array.apply(Array, Array(params.level || 4)).map(Number.prototype.valueOf, 0);
            this.content.hide().empty().css('height', 'auto').show();
        });

        List.prototype.getOffset = function()
        {
            var top = Math.min.apply(Math, this.photoListOrder);
            var left = this.photoListOrder.indexOf(top);

            return {
                top: top,
                left: left
            };
        };

        return List;
    })(BaseObject);

    var SearchResultItem = (function( BaseObject )
    {
        utils.extend(SearchResultItem, BaseObject);

        function SearchResultItem( searchEngine, type )
        {
            if ( !(this instanceof SearchResultItem) )
            {
                return new SearchResultItem(searchEngine, type);
            }

            SearchResultItem._super.call(this);

            this.searchEngine = searchEngine;
            this.data = {};

            switch ( type )
            {
                case 'user':
                    this.node = searchEngine.userItemPrototype.clone();
                    break;
                default:
                    this.node = searchEngine.hashItemPrototype.clone();
                    break;
            }
        }

        SearchResultItem.prototype.setSearchResultItemInfo = utils.fluent(function()
        {
            var reg, searchVal = this.getData('searchVal'), label = this.getData('label');

            switch ( this.getData('searchType') )
            {
                case 'user':
                    reg = new RegExp(searchVal.substring(1), 'i');

                    this.node.find('img').attr('src', this.getData('avatar'));
                    this.node.find('.ow_searchbar_username').html(label.replace(reg, function( p1 )
                    {
                        return '<b>' + p1 + '</b>';
                    }));
                    break;
                case 'hash':
                    reg = new RegExp(searchVal.substring(1), 'gi');

                    this.node.find('.ow_search_result_tag').html(label.replace(reg, function( p1 )
                    {
                        return '<b>' + p1 + '</b>';
                    }));
                    break;
                case 'desc':
                    reg = new RegExp(searchVal, 'gi');

                    this.node.find('.ow_search_result_tag').html(label.replace(reg, function( p1 )
                    {
                        return '<b>' + p1 + '</b>';
                    }));
                    break;
            }

            this.node.find('.ow_searchbar_ac_count').html(this.getData('count'));
            this.node.on('click', this.self(function()
            {
                this.searchEngine.getSearchResultPhotos(this);
            }));
        });

        SearchResultItem.prototype.setData = utils.fluent(function( data )
        {
            $.extend(this.data, data);
        });

        SearchResultItem.prototype.getData = function( key )
        {
            return this.data[key] || null;
        };

        return SearchResultItem;
    })(BaseObject);

    var SearchEngine = (function( BaseObject )
    {
        utils.extend(SearchEngine, BaseObject);

        var timerId;

        function SearchEngine()
        {
            SearchEngine._super.call(this);

            this.searchBox = document.getElementById('photo-list-search');
            this.searchResultList = $('.ow_searchbar_ac', this.searchBox);
            this.hashItemPrototype = $('li.hash-prototype', this.searchBox).removeClass('hash-prototype');
            this.userItemPrototype = $('li.user-prototype', this.searchBox).removeClass('user-prototype');
            this.searchInput = $('input:text', this.searchBox);
            this.listBtns = $('.ow_fw_btns > a');
        }

        SearchEngine.prototype.init = utils.fluent(function()
        {
            this.searchInput.on({
                keyup: this.self(function( event )
                {
                    timerId && (clearTimeout(timerId), this.abortSearchRequest(), timerId = null);

                    if ( event.keyCode === 13 )
                    {
                        this.destroySearchResultList().searchAll(this.searchInput.val());
                    }
                    else
                    {
                        timerId = setTimeout(function()
                        {
                            this.search(this.searchInput.val());
                        }.bind(this), 300);
                    }
                }),
                focus: this.self(function()
                {
                    if ( !this.searchResultList.is(':empty') )
                    {
                        this.searchResultList.show();
                    }
                })
            });

            this.listBtns.on('click', this.self(function( event )
            {
                event.preventDefault();
                this.loadList(event.currentTarget.getAttribute('list-type'));
            }));

            $('.ow_btn_close_search', this.searchBox).on('click', this.self(function()
            {
                this.loadList(this.serachInitList);
            }));

            $('.ow_searchbar_btn', this.searchBox).on('click', this.self(function( event )
            {
                var value = this.searchInput.val().trim();

                if ( value.length === 0 || value === OW.getLanguageText('photo', 'search_invitation') )
                {
                    event.stopPropagation();

                    return;
                }

                this.abortSearchRequest().searchAll(value);
            }));

            $(document).on('click', this.self(function( event )
            {
                if ( event.target.id === 'search-photo' )
                {
                    event.stopPropagation();
                }
                else if ( this.searchResultList.is(':visible') )
                {
                    this.searchResultList.hide();
                }
            }));
        });

        SearchEngine.prototype.search = utils.fluent(function( searchVal )
        {
            searchVal = searchVal.trim();

            if ( searchVal.length <= 2 || searchVal === this.preSearchVal )
            {
                return;
            }

            this.request = $.ajax(
            {
                url: params.getPhotoURL,
                dataType: 'json',
                data: {
                    ajaxFunc: 'getSearchResult',
                    searchVal: searchVal
                },
                cache: false,
                type: 'POST',
                beforeSend: this.self(function( jqXHR, settings )
                {
                    this.preSearchVal = searchVal;
                    this.destroySearchResultList().showSearchProcess();
                }),
                success: this.self(function( data, textStatus, jqXHR )
                {
                    if ( data && data.result )
                    {
                        this.buildSearchResultList(data.type, data.list, searchVal, data.avatarData);
                    }
                    else
                    {
                        OW.error('Server error');
                    }
                }),
                error: function( jqXHR, textStatus, errorThrown )
                {
                    throw textStatus;
                },
                complete: function()
                {
                    timerId = null
                }
            });
        });

        SearchEngine.prototype.searchAll = utils.fluent(function( searchVal )
        {
            searchVal = searchVal.trim();

            if ( searchVal.length <= 2 || searchVal === this.preAllSearchVal )
            {
                return;
            }

            this.preAllSearchVal = searchVal;
            this.getSearchResultPhotos('all');
        });

        SearchEngine.prototype.buildSearchResultList = utils.fluent(function( type, list, searchVal, avatarData )
        {
            this.hideSearchProcess();

            var keys;

            if ( (keys = Object.keys(list)).length === 0 )
            {
                $('#search-no-items').clone().removeAttr('id').appendTo(this.searchResultList);

                return;
            }

            this.searchVal = searchVal;
            this.changeListType(type);

            keys.forEach(function( item )
            {
                var data = list[item];
                var searchItem = new SearchResultItem(this, this.searchType);

                searchItem.setData({
                    searchType: this.searchType,
                    searchVal: searchVal,

                    id: data.id,
                    label: data.label,
                    avatar: avatarData !== undf ? avatarData[data.id].src : null,
                    count: data.count
                });
                searchItem.setSearchResultItemInfo();

                searchItem.node.appendTo(this.searchResultList).slideDown(200);
            }, this);
        });

        SearchEngine.prototype.changeListType = utils.fluent(function( type )
        {
            OW.trigger('photo.onChangeListType', [type]);

            if ( this.searchType === type ) return;

            this.searchType = type;

            if ( ['user', 'hash', 'desc', 'all'].indexOf(type) === -1 )
            {
                root.history.replaceState(null, null, type);
                root.document.title = OW.getLanguageText('photo', 'meta_title_photo_' + type);
                this.searchInput.val('');
            }
        });

        SearchEngine.prototype.getSearchResultPhotos = utils.fluent(function( mixed )
        {
            this.resetPhotoListData();
            $('.ow_searchbar_input', this.searchBox).addClass('active');

            if ( mixed instanceof SearchResultItem )
            {
                this.changeListType(mixed.getData('searchType'));
                this.preAllSearchVal = '';
            }
            else
            {
                this.changeListType(mixed);
            }

            if ( !this.serachInitList )
            {
                this.serachInitList = SlotManager().listType;
            }

            var data = {
                listType: this.searchType
            };


            switch ( this.searchType )
            {
                case 'desc':
                    data.searchVal = mixed.getData('searchVal');
                case 'user':
                case 'hash':
                    data.id = mixed.getData('id');
                    break;
                case 'all':
                    data.searchVal = this.searchInput.val().trim();
                    data.ajaxFunc = 'getSearchAllResult';
                    break;
            }

            SlotManager().load(data);
        });

        SearchEngine.prototype.loadList = utils.fluent(function( listType )
        {
            this.resetPhotoListData();

            $('.ow_searchbar_input', this.searchBox).removeClass('active');
            this.listBtns.filter('[list-type="' + listType + '"]').addClass('active');

            this.serachInitList = listType;
            this.changeListType(listType);
            this.destroySearchResultList();
            SlotManager().load({listType: listType});
        });

        SearchEngine.prototype.getSearchValue = function()
        {
            return this.searchVal || '';
        };

        SearchEngine.prototype.resetPhotoListData = utils.fluent(function()
        {
            this.listBtns.removeClass('active');

            SlotManager().reset();
            List().reset();
        });

        SearchEngine.prototype.abortSearchRequest = utils.fluent(function()
        {
            if ( this.request && this.request.readyState !== 4 )
            {
                try
                {
                    this.request.abort();
                }
                catch ( ignore ) { }
            }
        });

        SearchEngine.prototype.destroySearchResultList = utils.fluent(function()
        {
            this.searchResultList.hide().empty();
        });

        SearchEngine.prototype.showSearchProcess = utils.fluent(function()
        {
            this.searchResultList.append($('<li>').addClass('browse-photo-search clearfix ow_preloader')).show();
        });

        SearchEngine.prototype.hideSearchProcess = utils.fluent(function()
        {
            this.searchResultList.find('.ow_preloader').detach();
        });

        return SearchEngine;
    })(BaseObject);

    return root.Object.freeze({
        init: function()
        {
            var slotManager = new SlotManager();

            slotManager.load();

            if ( ['albums', 'userPhotos', 'albumPhotos'].indexOf(params.listType) === -1 )
            {
                var searchEngine = new SearchEngine();

                searchEngine.init();
            }

            OW.bind('photo.onSetRate', function( data )
            {
                slotManager.updateRate(data);
            });

            OW.bind('photo.updateAlbumPhotos', function( photoId )
            {
                SlotManager().updateAlbumPhotos(photoId);
            });

            var updateCommentCount = function( data )
            {
                slotManager.updateSlot(data.entityId, data);
            };

            OW.bind('base.comment_delete', updateCommentCount);
            OW.bind('base.comment_added', updateCommentCount);
            OW.bind('photo.onBeforeLoadFromCache', function()
            {
                OW.bind('base.comment_delete', updateCommentCount);
                OW.bind('base.comment_added', updateCommentCount);
            });
        },
        getMoreData: function()
        {
            return SlotManager().getMoreData();
        },
        reorder: function()
        {
            List().reorder();
        },
        removePhotoItems: function( idList )
        {
            SlotManager().removePhotoItems(idList);
        },
        updateSlot: function( slotId, data )
        {
            OW.trigger('photo.onUpdateSlot', [slotId, data]);

            SlotManager().updateSlot(slotId, data);
        }
    });
}));  /*
(function( $ ) {'use strict';

    var _vars = $.extend({}, (browsePhotoParams || {}), {offset: 0, idList: [], modified: false, getListRequest: null, uniqueList: null, hashtagPattern: /#(?:\w|[^\u0000-\u007F])+/g}),
    _elements = {},
    _methods = {
        showPreloader: function()
        {
            _elements.preloader.insertAfter(_elements.content);
        },
        hidePreloader: function()
        {
            _elements.preloader.detach();
        },
        getPhotos: function()
        {
            if ( _vars.completed === true )
            {
                return;
            }

            if ( _elements.getListRequest && _elements.getListRequest.readyState !== 4 )
            {
                try
                {
                    _elements.getListRequest.abort();
                }
                catch ( e ) { }
            }

            var data = {
                ajaxFunc: _vars.action || 'getPhotoList',
                listType: _vars.listType || 'latest',
                offset: ++_vars.offset
            };

            $.extend(data, _methods.getMoreData());

            _elements.getListRequest = $.ajax(
            {
                url: _vars.getPhotoURL,
                dataType: 'json',
                data: data,
                cache: false,
                type: 'POST',
                beforeSend: function( jqXHR, settings )
                {
                    _methods.showPreloader();

                    delete _vars.data;
                },
                success: function( data, textStatus, jqXHR )
                {
                    if ( data && data.status )
                    {
                        switch ( data.status )
                        {
                            case 'success':
                                _methods.buildPhotoList(data.data);
                                break;
                            case 'error':
                            default:
                                OW.error(data.msg);
                                break;
                        }
                    }
                    else
                    {
                        OW.error('Server error');
                    }
                },
                error: function( jqXHR, textStatus, errorThrown )
                {
                    throw textStatus;
                }
            });
        },
        createPhotoItem: function( photoObj )
        {
            var photo = _elements.photoItemPrototype.clone();
            var data = {
                photoId: photoObj.id,
                listType: _vars.listType,
                dimension: photoObj.dimension,
                photoUrl: photoObj.url
            };

            photo.attr('id', 'photo-item-' + photoObj.id);
            photo.data(data);

            return photo;
        },
        removePhotoItems: function( idList )
        {
            if ( !Array.isArray(idList) || idList.length === 0 )
            {
                return false;
            }

            var result = false, iDs = [];

            idList.forEach(function( item )
            {
                var photo;

                if ( (photo = document.getElementById(item)) !== null )
                {
                    var self = $(photo);

                    iDs.push(+self.data('photoId'));
                    self.remove();
                    result = true;
                }
            });

            if ( result )
            {
                _vars.modified = true;
                _vars.idList = _vars.idList.filter(function( item )
                {
                    return iDs.indexOf(item) === -1;
                });

                _methods.reorder();

                if ( _methods.isTimeToLoad() )
                {
                    _methods.getPhotos();
                }

                OW.trigger('photo.onRemovePhotoItems', iDs);
            }

            return result;
        },
        buildPhotoList: function( data )
        {
            if ( data.photoList.length === 0 )
            {
                _methods.hidePreloader();

                if ( _elements.content[0].childNodes.length === 0 )
                {
                    _elements.content.append($('<div>', {style: 'text-align:center; padding-top: 24px;'}).html(OW.getLanguageText('photo', 'no_items')));
                }

                return;
            }
            else if ( data.photoList.length < 20 )
            {
                _vars.completed = true;
            }

            _vars.data = data;
            _vars.uniqueList = data.unique;

            for ( var i = 1; i < _vars.data.photoList.length; i++ )
            {
                _methods.asyncLoadPhoto(_vars.data.photoList[i].url);
            }

            _methods.buildPhotoItem(data.photoList.shift());
        },
        buildPhotoItem: function( photo )
        {
            if ( photo === undefined )
            {
                _methods.hidePreloader();

                if ( _methods.isTimeToLoad() )
                {
                    _methods.unbindUI();
                    _methods.getPhotos();
                }
                else
                {
                    _methods.bindUI();
                }

                return;
            }
            else if ( photo.unique != _vars.uniqueList )
            {
                return;
            }

            var photoItem = _methods.createPhotoItem(photo);

            _methods.setInfo.call(photoItem, photo);
            OW.trigger('photo.onRenderPhotoItem', [$.extend({}, photo)], photoItem);

            _methods.buildPhotoItem.buildByMode(photoItem, photo);
        },
        isTimeToLoad: function()
        {
            var win = $(window);

            return Math.max(document.body.scrollHeight, document.documentElement.scrollHeight, document.body.offsetHeight, document.documentElement.offsetHeight, document.body.clientHeight, document.documentElement.clientHeight) - (win.scrollTop() + win.height()) <= 200;
        },
        bindUI: function()
        {
            $(window).on('scroll.browse_photo resize.browse_photo', function()
            {
                if ( _methods.isTimeToLoad() )
                {
                    _methods.unbindUI();
                    _methods.getPhotos();
                }
            });
        },
        unbindUI: function()
        {
            $(window).off('scroll.browse_photo resize.browse_photo');
        },
        asyncLoadPhoto: function( url )
        {
            setTimeout(function()
            {
                new Image().src = url;
            }, 1);
        },
        truncate: function( value, limit )
        {
            if ( !value )
            {
                return '';
            }

            var parts;

            limit = +limit || 50;

            if ( (parts = value.split(/\n/)).length >= 3 )
            {
                value = parts.slice(0, 3).join('\n') + '...';
            }
            else if ( value.length > limit )
            {
                value = value.toString().substring(0, limit) + '...';
            }

            return value;
        },
        getHashtags: function( text )
        {
            var result = {};

            text.replace(_vars.hashtagPattern, function( str, offest )
            {
                result[offest] = str;
            });

            return result;
        },
        descToHashtag: function( description, hashtags )
        {
            var url = '<a href="' + _vars.tagUrl + '">{$tagLabel}</a>';

            return description.replace(_vars.hashtagPattern, function( str, offest )
            {
                return (url.replace('-tag-', encodeURIComponent(hashtags[offest]))).replace('{$tagLabel}', str);
            }).replace(/\n/g, '<br>');
        },
        reorder: function()
        {
            if ( _vars.classicMode )
            {
                return;
            }

            _vars.photoListOrder = _vars.photoListOrder.map(Number.prototype.valueOf, 0);

            $('.ow_photo_item_wrap', _elements.content).each(function()
            {
                var self = $(this), top, left;

                top = Math.min.apply(0, _vars.photoListOrder);
                left = _vars.photoListOrder.indexOf(top);

                self.css({top: top + 'px', left: left / (_vars.level || 4) * 100 + '%'});
                _vars.photoListOrder[left] += self.height() + 16;
            });

            _elements.content.height(Math.max.apply(0, _vars.photoListOrder));
        },
        setAlbumInfo: function( photo, data )
        {
            if ( !data || !data.albumUrl || !data.albumName )
            {
                return;
            }

            if ( _vars.classicMode )
            {
                this.find('.ow_photo_item_info_album').show().find('a').attr('href', data.albumUrl).find('b').html(data.albumName);
            }
            else
            {
                this.find('.ow_photo_item_info_album').show().find('a').attr('href', data.albumUrl).html(data.albumName);
            }
        },
        setUserInfo: function( photo, data)
        {
            if ( !data || !data.userUrl || !data.userName )
            {
                return;
            }

            if ( _vars.classicMode )
            {
                this.find('.ow_photo_by_user').show().find('a').attr('href', data.userUrl).find('b').html(data.userName);
            }
            else
            {
                this.find('.ow_photo_by_user').show().find('a').attr('href', data.userUrl).html(data.userName);
            }
        },
        setDescription: function( entity )
        {
            var description;

            if ( _vars.listType != 'albums' )
            {
                description = _methods.descToHashtag(_methods.truncate(entity.description), _methods.getHashtags(entity.description));
            }
            else
            {
                description = entity.name + '&nbsp;(' + (entity.count || 0) + ')';
            }

            var event = {text: description};
            OW.trigger('photo.onSetDescription', event);
            description = event.text;

            if ( description && description.length !== 0 )
            {
                this.find('.ow_photo_item_info_description').html(description).show();
            }
            else
            {
                this.find('.ow_photo_item_info_description').hide();
            }
        },
        setURL: function( photo, data )
        {
            if ( !data || !data.url )
            {
                return;
            }

            this.data('url', data.url);
            this.on('click', function( event )
            {
                window.location = data.url;
            });
        },
        setRate: function( photo, data )
        {
            if ( !data || !data.rateInfo || data.userScore === undefined )
            {
                return;
            }

            var self = $(this), rateItems = $('.rate_item', this);

            if ( +data.userScore > 0 )
            {
                this.find('.rate_title').html(
                    OW.getLanguageText('photo', 'rating_your', {count: data.rateInfo.rates_count, score: data.userScore})
                );
            }
            else
            {
                this.find('.rate_title').html(
                    OW.getLanguageText('photo', 'rating_total', {count: data.rateInfo.rates_count})
                );
            }

            this.find('.active_rate_list').css('width', (data.rateInfo.avg_score * 20) + '%');

            rateItems.each(function( index )
            {
                $(this)
                    .on('click', function()
                    {
                        var ownerError;

                        if ( +_vars.rateUserId === 0 || (ownerError = (+photo.userId === +_vars.rateUserId)) )
                        {
                            if ( ownerError === undefined )
                            {
                                OW.error(OW.getLanguageText('base', 'rate_cmp_auth_error_message'));
                            }
                            else
                            {
                                OW.error(OW.getLanguageText('base', 'rate_cmp_owner_cant_rate_error_message'));
                            }

                            return false;
                        }

                        $.ajax(
                        {
                            url: _vars.getPhotoURL,
                            dataType: 'json',
                            data: {
                                ajaxFunc: 'ajaxRate',
                                entityId: photo.id,
                                rate: index + 1,
                                ownerId: photo.userId
                            },
                            cache: false,
                            type: 'POST',
                            success: function( result, textStatus, jqXHR )
                            {
                                if ( result )
                                {
                                    switch ( result.result )
                                    {
                                        case true:
                                            OW.info(result.msg);
                                            self.find('.active_rate_list').css('width', (result.rateInfo.avg_score * 20) + '%');
                                            self.find('.rate_title').html(
                                                OW.getLanguageText('photo', 'rating_your', {count: result.rateInfo.rates_count, score: index + 1})
                                            );
                                            data.rateInfo.avg_score = result.rateInfo.avg_score;
                                            break;
                                        case false:
                                        default:
                                            OW.error(result.error);
                                            break;
                                    }
                                }
                                else
                                {
                                    OW.error('Server error');
                                }
                            },
                            error: function( jqXHR, textStatus, errorThrown )
                            {
                                throw textStatus;
                            }
                        });
                    })
                    .hover(function()
                    {
                        var f = $(this).index();
                        rateItems.slice(0, index + 1).addClass('active');
                        self.find('.active_rate_list').css('width', '0px');
                    }, function()
                    {
                        rateItems.slice(0, index + 1).removeClass('active');
                        self.find('.active_rate_list').css('width', (data.rateInfo.avg_score * 20) + '%');
                    });
            });
        },
        updateRate: function( data )
        {
            var diff = ['entityId', 'userScore', 'avgScore', 'ratesCount'].every(function( item )
            {
                return data.hasOwnProperty(item);
            });

            var photoItem;

            if ( diff && (photoItem = document.getElementById('photo-item-' + data.entityId)) !== null)
            {
                $('.active_rate_list', photoItem).css('width', (data.avgScore * 20) + '%');
                $('.rate_title', photoItem).html(
                    OW.getLanguageText('photo', 'rating_your', {count: data.ratesCount, score: data.userScore})
                );
            }
        },
        setCommentCount: function( photo, data )
        {
            this.find('.ow_photo_comment_count a').html('<b>' + parseInt(data.commentCount) + '</b>').on('click', function()
            {
                var data = this.data(), _data = {}, img = this.find('img')[0];

                if ( data.dimension && data.dimension.length )
                {
                    try
                    {
                        var dimension = JSON.parse(data.dimension);

                        _data.main = dimension.main;
                    }
                    catch( e )
                    {
                        _data.main = [img.naturalWidth, img.naturalHeight];
                    }
                }
                else
                {
                    _data.main = [img.naturalWidth, img.naturalHeight];
                }

                _data.mainUrl = data.photoUrl;

                photoView.setId(data.photoId, _vars.listType, _methods.getMoreData(), _data);
            }.bind(this));
        },
        getData: function( keys, entity )
        {
            _vars.idList.push(+entity.id);

            var resultData = {};

            keys.forEach(function( item )
            {
                switch ( item )
                {
                    case 'setUserInfo':
                        resultData.userUrl = _vars.data.userUrlList[+entity.userId];
                        resultData.userName = _vars.data.displayNameList[+entity.userId];
                        break;
                    case 'setAlbumInfo':
                        resultData.albumUrl = _vars.data.albumUrlList[+entity.albumId];
                        resultData.albumName = _vars.data.albumNameList[+entity.albumId].name;
                        break;
                    case 'setRate':
                        resultData.rateInfo = _vars.data.rateInfo[+entity.id];
                        resultData.userScore = _vars.data.userScore[+entity.id];
                        break;
                    case 'setURL':
                        resultData.url = _vars.data.photoUrlList[+entity.id];
                        break;
                    case 'setCommentCount':
                        resultData.commentCount = _vars.data.commentCount[+entity.id];
                        break;
                }
            });

            return resultData;
        },
        initSearchEngine: function()
        {
            var timerId;

            _elements.searchBox = document.getElementById('photo-list-search');
            _elements.searchResultList = $('.ow_searchbar_ac', _elements.searchBox);
            _elements.hashItemPrototype = $('li.hash-prototype', _elements.searchBox).removeClass('hash-prototype');
            _elements.userItemPrototype = $('li.user-prototype', _elements.searchBox).removeClass('user-prototype');
            _elements.searchInput = $('input:text', _elements.searchBox).on({
                keyup: function( event )
                {
                    if ( timerId )
                    {
                        clearTimeout(timerId);
                        _methods.abortSearchRequest();
                    }

                    if ( event.keyCode === 13 )
                    {
                        _methods.destroySearchResultList();
                        _methods.searchAll(this.value);
                    }
                    else
                    {
                        timerId = setTimeout(function()
                        {
                            _methods.search(_elements.searchInput.val());
                        }, 300);
                    }
                },
                focus: function()
                {
                    $(this).removeClass('invitation');

                    if ( _elements.searchResultList.children().length !== 0 )
                    {
                        _elements.searchResultList.show();
                    }

                    if ( this.value.trim() === OW.getLanguageText('photo', 'search_invitation') )
                    {
                        $(this).val('');
                    }
                },
                blur: function()
                {
                    $(this).addClass('invitation');

                    if ( this.value.trim().length === 0 )
                    {
                        $(this).val(OW.getLanguageText('photo', 'search_invitation'));
                    }
                }
            });

            _elements.listBtns = $('.ow_fw_btns > a').on('click', function( event )
            {
                _methods.loadList($(this).attr('list-type'));

                event.preventDefault();
            });

            $('.ow_btn_close_search', _elements.searchBox).on('click', function()
            {
                _methods.loadList(_vars.serachInitList);
            });

            $('.ow_searchbar_btn', _elements.searchBox).on('click', function()
            {
                var value = _elements.searchInput.val().trim();

                if ( value.length === 0 || value === OW.getLanguageText('photo', 'search_invitation') )
                {
                    return false;
                }

                _methods.abortSearchRequest();
                _methods.searchAll(_elements.searchInput[0].value);
            });

            $(document).on('click', function( event )
            {
                if ( event.target.id === 'search-photo' )
                {
                    event.stopPropagation();
                }
                else if ( _elements.searchResultList.is(':visible') )
                {
                    _elements.searchResultList.hide();
                }
            });

            $.extend(_methods, {
                createSearchResultItem: function( type )
                {
                    switch ( type )
                    {
                        case 'user':
                            return _elements.userItemPrototype.clone();
                        default:
                            return _elements.hashItemPrototype.clone();
                    }
                },
                buildSearchResultList: function( type, list, searchVal, avatarData )
                {
                    _methods.hideSearchProcess();

                    var keys;

                    if ( (keys = Object.keys(list)).length === 0 )
                    {
                        $('<li class="browse-photo-search clearfix"><span style="line-height: 20px;">' + OW.getLanguageText('photo', 'search_result_empty') + '</span></li>')
                            .appendTo(_elements.searchResultList);

                        return;
                    }

                    _vars.searchVal = searchVal;
                    _methods.changeListType(type);

                    keys.forEach(function( item )
                    {
                        var listItem = _methods.createSearchResultItem(type);

                        listItem.data('id', list[item].id);
                        listItem.data('searchType', type);
                        listItem.find('.ow_searchbar_ac_count').html(list[item].count);
                        listItem.on('click', function()
                        {
                            _methods.getSearchResultPhotos.call(this);
                        });

                        _methods.setSearchResultItemInfo.call(listItem, searchVal, list[item].label, avatarData !== undefined ? avatarData[list[item].id].src : null);

                        listItem.appendTo(_elements.searchResultList).slideDown(200);
                    });
                },
                destroySearchResultList: function()
                {
                    _elements.searchResultList.hide().empty();
                },
                showSearchProcess: function()
                {
                    _elements.searchResultList.append($('<li>', {class: 'browse-photo-search clearfix ow_preloader'})).show();
                },
                hideSearchProcess: function()
                {
                    _elements.searchResultList.find('.ow_preloader').remove();
                },
                search: function( searchVal )
                {
                    searchVal = searchVal.trim();

                    if ( searchVal.length <= 2 || searchVal === _vars.preSearchVal )
                    {
                        return;
                    }

                    _elements.searchRequest = $.ajax(
                    {
                        url: _vars.getPhotoURL,
                        dataType: 'json',
                        data:
                        {
                            ajaxFunc: 'getSearchResult',
                            searchVal: searchVal
                        },
                        cache: false,
                        type: 'POST',
                        beforeSend: function( jqXHR, settings )
                        {
                            _vars.preSearchVal = searchVal;
                            _methods.destroySearchResultList();
                            _methods.showSearchProcess();
                        },
                        success: function( data, textStatus, jqXHR )
                        {
                            if ( data && data.result )
                            {
                                _methods.buildSearchResultList(data.type, data.list, searchVal, data.avatarData);
                            }
                            else
                            {
                                OW.error('Server error');
                            }
                        },
                        error: function( jqXHR, textStatus, errorThrown )
                        {
                            throw textStatus;
                        }
                    });
                },
                searchAll: function( searchVal )
                {
                    searchVal = searchVal.trim();

                    if ( searchVal.length <= 2 || searchVal === _vars.preAllSearchVal )
                    {
                        return;
                    }

                    _vars.preAllSearchVal = searchVal;
                    _methods.getSearchResultPhotos('all');
                },
                getSearchResultPhotos: function( type )
                {
                    _methods.resetPhotoListData();

                    if ( type )
                    {
                        _methods.changeListType(type);
                    }
                    else
                    {
                        _methods.changeListType($(this).data('searchType'));
                        _vars.preAllSearchVal = '';
                    }

                    if ( _vars.serachInitList == null )
                    {
                        _vars.serachInitList = _vars.listType;
                        $('.ow_searchbar_input', _elements.searchBox).addClass('active');
                    }

                    _vars.listType = _vars.searchType;

                    switch ( _vars.searchType )
                    {
                        case 'desc':
                            _vars.searchVal = $(this).data('searchVal');
                        case 'user':
                        case 'hash':
                            _vars.id = +$(this).data('id');
                            break;
                        case 'all':
                            _vars.searchVal = _elements.searchInput.val().trim();
                            break;
                    }

                    _methods.getPhotos();
                },
                changeListType: function( type )
                {
                    OW.trigger('photo.onChangeListType', [type]);

                    if ( _vars.searchType === type )
                    {
                        return;
                    }

                    _vars.searchType = type;

                    switch ( type )
                    {
                        case 'user':
                            _methods.getMoreData = function()
                            {
                                return {
                                    id: _vars.id,
                                    searchVal: _vars.searchVal
                                };
                            };

                            _methods.setSearchResultItemInfo = function( searchVal, info, avatarUrl )
                            {
                                var reg = new RegExp(searchVal.substring(1), 'i');

                                this.find('img').attr('src', avatarUrl);
                                this.find('.ow_searchbar_username').html(info.replace(reg, function( p1 )
                                {
                                    return '<b>' + p1 + '</b>';
                                }));
                            };
                            break;
                        case 'hash':
                            _methods.getMoreData = function()
                            {
                                return {
                                    id: _vars.id,
                                    searchVal: _vars.searchVal
                                };
                            };

                            _methods.setSearchResultItemInfo = function( searchVal, info )
                            {
                                var reg = new RegExp(searchVal.substring(1), 'gi');

                                this.find('.ow_search_result_tag').html(info.replace(reg, function( p1 )
                                {
                                    return '<b>' + p1 + '</b>';
                                }));
                            };
                            break;
                        case 'desc':
                            _methods.getMoreData = function()
                            {
                                return {
                                    id: _vars.id,
                                    searchVal: _vars.searchVal
                                };
                            };

                            _methods.setSearchResultItemInfo = function( searchVal, info )
                            {
                                var reg = new RegExp(searchVal, 'gi');

                                this.find('.ow_search_result_tag').html(info.replace(reg, function( p1 )
                                {
                                    return '<b>' + p1 + '</b>';
                                }));
                                this.data('searchVal', searchVal);
                            };
                            break;
                        case 'all':
                            _methods.getMoreData = function()
                            {
                                return {
                                    ajaxFunc: 'getSearchAllResult',
                                    searchVal: _vars.searchVal
                                };
                            };
                            break;
                        default:
                            window.history.replaceState(null, null, type);
                            _elements.searchInput.val(OW.getLanguageText('photo', 'search_invitation'));
                            document.title = OW.getLanguageText('photo', 'meta_title_photo_' + _vars.searchType);

                            _methods.getMoreData = function()
                            {
                                return {};
                            };
                            break;
                    }
                },
                resetPhotoListData: function()
                {
                    _elements.listBtns.removeClass('active');
                    _vars.uniqueList = null;
                    _vars.offset = 0;
                    _vars.preSearchVal = '';
                    _vars.photoListOrder = _vars.photoListOrder.map(Number.prototype.valueOf, 0);
                    _vars.completed = false;
                    _elements.content.hide().empty().css('height', 'auto').show();
                },
                abortSearchRequest: function()
                {
                    if ( _elements.searchRequest && _elements.searchRequest.readyState !== 4 )
                    {
                        try
                        {
                            _elements.searchRequest.abort();
                        }
                        catch ( e ) { }
                    }
                },
                loadList: function( listType )
                {
                    _methods.resetPhotoListData();
                    $('.ow_searchbar_input', _elements.searchBox).removeClass('active');
                    _elements.listBtns.filter('[list-type="' + listType + '"]').addClass('active');
                    _vars.listType = listType;
                    _vars.serachInitList = null;
                    _methods.changeListType(_vars.listType);
                    _methods.destroySearchResultList();
                    _methods.getPhotos();
                }
            });
        }
    };

    _methods.setInfo = (function()
    {
        var action = [];

        switch ( _vars.listType )
        {
            case 'albums':
                action.push('setURL');
                action.push('setDescription');
                break;
            case 'albumPhotos':
                if ( !_vars.classicMode )
                {
                    action.push('setDescription');
                }

                action.push('setRate');
                action.push('setCommentCount');
                break;
            case 'userPhotos':
                if ( !_vars.classicMode )
                {
                    action.push('setDescription');
                }

                action.push('setAlbumInfo');
                action.push('setRate');
                action.push('setCommentCount');
                break;
            default:
                if ( !_vars.classicMode )
                {
                    action.push('setDescription');
                }

                action.push('setAlbumInfo');
                action.push('setUserInfo');
                action.push('setRate');
                action.push('setCommentCount');
                break;
        }

        return function( entity )
        {
            var data = _methods.getData(action, entity);

            action.forEach(function( item )
            {
                _methods[item].call(this, entity, data);
            }, this);
        };
    })();

    _methods.getMoreData = (function()
    {
        switch ( _vars.listType )
        {
            case 'albums':
            case 'userPhotos':
                return function()
                {
                    return $.extend({}, (_vars.modified ? {offset: 1, idList: _vars.idList} : {}), {
                        userId: _vars.userId
                    });
                };
            case 'albumPhotos':
                return function()
                {
                    return $.extend({}, (_vars.modified ? {offset: 1, idList: _vars.idList} : {}), {
                        albumId: _vars.albumId
                    });
                };
            default:
                _methods.initSearchEngine();

                return function()
                {
                    if ( ['user', 'hash', 'desc', 'all', 'tag'].indexOf(_vars.listType) !== -1 )
                    {
                        return {searchVal: _vars.searchVal};
                    }

                    return {};
                };
        }
    })();

    _methods.buildPhotoItem.buildByMode = (function()
    {
        if ( _vars.classicMode )
        {
            return function( photoItem, photo )
            {
                photoItem.find('.ow_photo_item')[0].style.backgroundImage = 'url(' + photo.url + ')';
                photoItem.find('img.ow_hidden').attr('src', photo.url);
                photoItem.appendTo(_elements.content);
                photoItem.fadeIn(100, function()
                {
                    if ( _vars.data && _vars.data.photoList )
                    {
                        _methods.buildPhotoItem(_vars.data.photoList.shift());
                    }
                });
            };
        }
        else
        {
            if ( _vars.listType == 'albums' )
            {
                return function( photoItem, photo )
                {
                    var top = Math.min.apply(0, _vars.photoListOrder);
                    var left = _vars.photoListOrder.indexOf(top);
                    var img = photoItem.find('img')[0];

                    img.onerror = img.onload = function(){_methods.buildPhotoItem.complete(left, photoItem)};
                    img.src = photo.url;
                    photoItem.appendTo(_elements.content);
                };
            }

            return function( photoItem, photo )
            {
                var top = Math.min.apply(0, _vars.photoListOrder);
                var left = _vars.photoListOrder.indexOf(top);

                photoItem.css({top: top + 'px', left: left / (_vars.level || 4) * 100 + '%'});
                photoItem.find('img').attr('src', photo.url);
                photoItem.appendTo(_elements.content);
                _elements.content.height(Math.max.apply(0, _vars.photoListOrder));

                var img = new Image();
                img.onload = img.onerror = function(){_methods.buildPhotoItem.complete(left, photoItem, photo)};
                img.src = photo.url;
            };
        }
    })();

    _methods.buildPhotoItem.complete = function( left, photoItem, photo )
    {
        photoItem.fadeIn(100, function()
        {
            if ( photo && photo.unique != _vars.uniqueList )
            {
                return;
            }

            _vars.photoListOrder[left] += photoItem.height() + 16;
            _elements.content.height(Math.max.apply(0, _vars.photoListOrder));

            if ( _vars.data && _vars.data.photoList )
            {
                _methods.buildPhotoItem(_vars.data.photoList.shift());
            }
        });
    };

    window.browsePhoto = Object.defineProperties({},
    {
        init: {
            value: function()
            {
                $.extend(_elements, {
                    content: $(document.getElementById('browse-photo')),
                    preloader: $(document.getElementById('browse-photo-preloader')),
                    photoItemPrototype: $(document.getElementById('browse-photo-item-prototype'))
                });

                _vars.photoListOrder = Array.apply(0, new Array(_vars.level || 4)).map(Number.prototype.valueOf, 0);

                OW.bind('photo.onSetRate', _methods.updateRate);

                var updateCommentCount = function( data )
                {
                    var photo;

                    if ( !data || data.entityType != 'photo_comments' || (photo = document.getElementById('photo-item-' + data.entityId)) == null )
                    {
                        return;
                    }

                    $('.ow_photo_comment_count a', photo).html('<b>' + parseInt(data.commentCount) + '</b>');
                };

                OW.bind('base.comment_delete', updateCommentCount);
                OW.bind('base.comment_added', updateCommentCount);
                OW.bind('photo.onBeforeLoadFromCache', function()
                {
                    OW.bind('base.comment_delete', updateCommentCount);
                    OW.bind('base.comment_added', updateCommentCount);
                });

                _methods.getPhotos();
                return;
            }
        },
        reorder: {value: _methods.reorder},
        removePhotoItems: {value: _methods.removePhotoItems},
        updateSlot: {
            value: function( slotId, data )
            {
                OW.trigger('photo.onUpdateSlot', [slotId, data]);

                var item;

                if ( !_vars.isOwner || (item = document.getElementById(slotId)) === null || Object.keys(data).length === 0 )
                {
                    return false;
                }

                switch ( _vars.listType )
                {
                    case 'userPhotos':
                    case 'albumPhotos':
                        _methods.setAlbumInfo.call($(item), 0, data);
                        _methods.setDescription.call($(item), data);
                        break;
                }
            }
        },
        getListData: {
            value: function()
            {
                return {
                    searchVal: _vars.searchVal,
                    id: _vars.id
                };
            }
        },
        getMoreData: {value: _methods.getMoreData}
    });
})(jQuery);

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Lite Dating Software http://lite.skadate.com/
 * and is licensed under SkaDate Lite License by Skalfa LLC.
 * Full text of this license can be found at http://lite.skadate.com/sll.pdf
 *

/**
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.photo
 * @since 1.7.6
 */
(function( root, $ )
{
    var _params = {};
    var _contextList = [];
    var _methods = {
        sendRequest: function( ajaxFunc, entityId, success )
        {
            $.ajax(
            {
                url: _params.actionUrl,
                type: 'POST',
                cache: false,
                data:
                {
                    ajaxFunc: ajaxFunc,
                    entityId: entityId
                },
                dataType: 'json',
                success: success,
                error: function( jqXHR, textStatus, errorThrown )
                {
                    OW.error(textStatus);

                    throw textStatus;
                }
            });
        },
        deletePhoto: function( slot )
        {
            if ( !confirm(OW.getLanguageText('photo', 'confirm_delete')) )
            {
                return false;
            }

            var photoId = slot.id;

            _methods.sendRequest('ajaxDeletePhoto', photoId, function( data )
            {
                if ( window.hasOwnProperty('photoAlbum') )
                {
                    photoAlbum.setCoverUrl(data.coverUrl, data.isHasCover)
                }
                
                if ( data.result === true )
                {
                    OW.info(data.msg);

                    browsePhoto.removePhotoItems([photoId]);
                } 
                else if ( data.hasOwnProperty('error') )
                {
                    OW.error(data.error);
                }
            });
        },
        editPhoto: function( slot )
        {
            var photoId = slot.id;
            var editFB = OW.ajaxFloatBox('PHOTO_CMP_EditPhoto', {photoId: photoId}, {iconClass: 'ow_ic_edit', title: OW.getLanguageText('photo', 'tb_edit_photo'),
                onLoad: function()
                {
                    owForms['photo-edit-form'].bind("success", function( data )
                    {
                        editFB.close();
                        
                        if ( data && data.result )
                        {
                            if ( data.photo.status !== 'approved' )
                            {
                                OW.info(data.msgApproval);
                                browsePhoto.removePhotoItems([photoId]);
                            }
                            else
                            {
                                OW.info(data.msg);
                                browsePhoto.updateSlot(data.id, data);
                            }

                            browsePhoto.reorder();
                            
                            OW.trigger('photo.onAfterEditPhoto', [data.id]);
                        }
                        else if ( data.msg )
                        {
                            OW.error(data.msg);
                        }
                    });
                }}
            );
        },
        saveAsAvatar: function( slot )
        {
            document.avatarFloatBox = OW.ajaxFloatBox(
                "BASE_CMP_AvatarChange",
                { params : { step: 2, entityType : 'photo_album', entityId : '', id : slot.id } },
                { width : 749, title : OW.getLanguageText('base', 'avatar_change') }
            );
        },
        saveAsCover: function( slot )
        {
            var photoId = slot.id;
            var img, item = $('#photo-item-' + photoId), dim;
            
            if ( _params.isClassic )
            {
                img = $('img.ow_hidden', item)[0];
            }
            else
            {
                img = $('img', item)[0];
            }
            
            if ( slot.dimension && slot.dimension.length )
            {
                try
                {
                    var dimension = JSON.parse(slot.dimension);

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
    
            window.albumCoverMakerFB = OW.ajaxFloatBox('PHOTO_CMP_MakeAlbumCover', [_params.albumId, photoId], {
                title: OW.getLanguageText('photo', 'set_as_album_cover'),
                width: '700',
                onLoad: function()
                {
                    window.albumCoverMaker.init();
                }
            });
        },
        editAlbum: function( slot )
        {
            window.location = slot.albumUrl + '#edit';
        },
        deleteAlbum: function( album )
        {
            if ( !confirm(OW.getLanguageText('photo', 'are_you_sure')) )
            {
                return;
            }

            _methods.sendRequest('ajaxDeletePhotoAlbum', album.id, function( data )
            {
                if ( data.result )
                {
                    OW.info(data.msg);

                    browsePhoto.removePhotoItems([album.id]);
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
            });
        },
        call: function( action, slot )
        {
            if ( _methods.hasOwnProperty(action) )
            {
                _methods[action](slot);
            }
        },
        createElement: function( action, html, style )
        {
            return $('<li/>').addClass([style || '', action].join(' ')).append(
                $('<a/>', {href : 'javascript://'}).data('action', action).html(html)
            );
        },
        init: function()
        {
            $.extend(_params, (root.photoContextActionParams || {}));
            
            switch ( _params.listType )
            {
                case 'albums':
                    if ( _params.isOwner )
                    {
                        _contextList.push(_methods.createElement('editAlbum', OW.getLanguageText('photo', 'edit_album')));
                        _contextList.push(_methods.createElement('deleteAlbum', OW.getLanguageText('photo', 'delete_album'), 'delete_album'));
                    }
                    break;
                default:
                    if ( _params.downloadAccept === true )
                    {
                        var element  = _methods.createElement('downloadPhoto', OW.getLanguageText('photo', 'download_photo'));

                        element.find('a').attr('target', 'photo-downloader').addClass('download');
                        _contextList.push(element);
                    }

                    if ( _params.isOwner )
                    {
                        _contextList.push(_methods.createElement('deletePhoto', OW.getLanguageText('photo', 'delete_photo')));
                        _contextList.push(_methods.createElement('editPhoto', OW.getLanguageText('photo', 'tb_edit_photo')));

                        var divider = _methods.createElement('divider', '', 'ow_context_action_divider_wrap');

                        divider.find('a').addClass('ow_context_action_divider');
                        _contextList.push(divider);
                        _contextList.push(_methods.createElement('saveAsAvatar', OW.getLanguageText('photo', 'save_as_avatar')));

                        if ( _params.hasOwnProperty('albumId') && +_params.albumId > 0 )
                        {
                            _contextList.push(_methods.createElement('saveAsCover', OW.getLanguageText('photo', 'save_as_cover')));
                        }
                    }
                    else if ( _params.isModerator )
                    {
                        _contextList.push(_methods.createElement('deletePhoto', OW.getLanguageText('photo', 'delete_photo')));
                        _contextList.push(_methods.createElement('editPhoto', OW.getLanguageText('photo', 'tb_edit_photo')));
                    }
                    break;
            }
            
            var event = {buttons: [], actions: {}};
            OW.trigger('photo.collectMenuItems', [event, _params.listType]);
            $.extend(_methods, event.actions);
            
            if ( _contextList.length === 0 && event.buttons.length === 0 )
            {
                return;
            }

            var list = $('<ul>').addClass('ow_context_action_list');

            _contextList.concat(event.buttons).forEach(function(item)
            {
                item.appendTo(list);
            });

            _params.contextAction = $('<div>').addClass('ow_photo_context_action').on('click', function( event )
            {
                event.stopImmediatePropagation();
            });
            _params.contextActionPrototype = $(document.getElementById('context-action-prototype')).removeAttr('id');
            _params.contextActionPrototype.find('.ow_tooltip_body').append(list);
            
            OW.bind('photo.onRenderPhotoItem', function( slot )
            {
                var self = $(this);
                var prototype = _params.contextActionPrototype.clone(true);

                prototype.find('.download').attr('href', _params.downloadUrl.replace(':id', slot.id));

                if ( _params.listType == 'albums' && slot.name.trim() == OW.getLanguageText('photo', 'newsfeed_album').trim() )
                {
                    prototype.find('.delete_album').remove();
                }

                prototype.find('.ow_tooltip_body a').on('click', function()
                {
                    var action = $(this).data('action');

                    _methods.call(action, slot);
                });

                OW.trigger('photo.contextActionReady', [prototype, slot]);

                if ( prototype.find('li').length === 0 ) return;

                var contextAction = _params.contextAction.clone(true);
                contextAction.append(prototype);
                
                if ( _params.isClassic )
                {
                    self.find('.ow_photo_item').prepend(contextAction);
                }
                else
                {
                    self.find('.ow_photo_pint_album').append(contextAction);
                }
            });
        }
    };
    
    root.photoContextAction = Object.freeze({
        init: _methods.init,
        createElement: _methods.createElement
    });
    
})( window, jQuery );
