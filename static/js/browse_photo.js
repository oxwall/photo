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
            actionStr = 'setURL,setDescription,bindEvents';
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
            this.node.on('click', this.self(function()
            {
                window.location = this.data.url;
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
                description = utils.truncate(data.name, 50) + '&nbsp;(' + (data.count || 0) + ')';
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

            $('.rate_item', this.node).on('click', function( event )
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
            if ( params.classicMode )
            {
                this.node.find('.ow_photo_by_user').show()
                    .find('a').attr('href', this.data.userUrl)
                    .find('b').html(this.data.userName);
            }
            else
            {
                this.node.find('.ow_photo_by_user').show()
                    .find('a').attr('href', this.data.userUrl).html(this.data.userName);
            }
        });

        Slot.prototype.bindEvents = utils.fluent(function()
        {
            this.node.on('click', this.self(function()
            {
                var data = this.data,
                    img = this.node.find('img')[0],
                    _data = {mainUrl: data.url};

                if ( data.dimension && data.dimension.length )
                {
                    try
                    {
                        _data.main = JSON.parse(data.dimension).main;
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

                photoView.setId(data.id, data.listType, {}, _data);
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
                    return $.extend({}, (this.modified ? {offset: 1, idList: this.idList} : {}), {
                        userId: params.userId
                    });
                case 'albumPhotos':
                    return $.extend({}, (this.modified ? {offset: 1, idList: this.idList} : {}), {
                        albumId: params.albumId
                    });
                default:
                    if ( ['user', 'hash', 'desc', 'all', 'tag'].indexOf(this.listType) !== -1 )
                    {
                        return {searchVal: ''};
                    }

                    return {};
            }
        };

        SlotManager.prototype.buildSlotList = utils.fluent(function( data )
        {
            if ( !data || !data.photoList || data.photoList.length === 0 )
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
        });

        SlotManager.prototype.getSlotData = function( slot )
        {
            if ( !slot )
            {
                throw new TypeError('"Slot" required');
            }

            this.idList.push(slot.id);

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
                    case 'setURL':
                        data.url = this.data.photoUrlList[slot.id];
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
            if ( list.length === 0 ) return;

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
            this.idList = []
        });

        return SlotManager;
    })(BaseObject);

    var List = (function( BaseObject )
    {
        var instance;

        utils.extend(List, BaseObject);

        function List( slotManager )
        {
            if ( instance )
            {
                return instance;
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
                    slot.appendTo(this.content);
                    slot.node.fadeIn(100, this.self(function()
                    {
                        if ( this.slotManager.data && this.slotManager.data.photoList )
                        {
                            this.slotManager.buildPhotoItem(this.slotManager.data.photoList.shift());
                        }
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

                this.photoListOrder[offset.left] += slot.node.height() + 16;
                this.content.height(Math.max.apply(Math, this.photoListOrder));
                this.slotManager.buildNewOne();
            }));
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

            if ( ['albums', 'userPhotos', 'albumPhotos'].indexOf(params.listType) )
            {
                var searchEngine = new SearchEngine();

                searchEngine.init();
            }
        }
    });
}));

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
 * @since 1.6.1
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
        deletePhoto: function( photoId )
        {
            if ( !confirm(OW.getLanguageText('photo', 'confirm_delete')) )
            {
                return false;
            }
            
            _methods.sendRequest('ajaxDeletePhoto', photoId, function( data )
            {
                if ( window.hasOwnProperty('photoAlbum') )
                {
                    photoAlbum.setCoverUrl(data.coverUrl, data.isHasCover)
                }
                
                if ( data.result === true )
                {
                    OW.info(data.msg);

                    browsePhoto.removePhotoItems(['photo-item-' + photoId]);
                } 
                else if ( data.hasOwnProperty('error') )
                {
                    OW.error(data.error);
                }
            });
        },
        editPhoto: function( photoId )
        {
            var editFB = OW.ajaxFloatBox('PHOTO_CMP_EditPhoto', {photoId: photoId}, {width: 580, iconClass: 'ow_ic_edit', title: OW.getLanguageText('photo', 'tb_edit_photo'),
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
                                browsePhoto.removePhotoItems(['photo-item-' + photoId]);
                            }
                            else
                            {
                                OW.info(data.msg);
                                browsePhoto.updateSlot('photo-item-' + data.id, data);
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
        saveAsAvatar: function( photoId )
        {
            document.avatarFloatBox = OW.ajaxFloatBox(
                "BASE_CMP_AvatarChange",
                { params : { step: 2, entityType : 'photo_album', entityId : '', id : photoId } },
                { width : 749, title : OW.getLanguageText('base', 'avatar_change') }
            );
        },
        saveAsCover: function( photoId )
        {
            var img, item = document.getElementById('photo-item-' + photoId), data = $(item).data(), dim;
            
            if ( _params.isClassic )
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
    
            window.albumCoverMakerFB = OW.ajaxFloatBox('PHOTO_CMP_MakeAlbumCover', [_params.albumId, photoId], {
                title: OW.getLanguageText('photo', 'set_as_album_cover'),
                width: '700',
                onLoad: function()
                {
                    window.albumCoverMaker.init();
                }
            });
        },
        editAlbum: function( event )
        {
            var url = $(this).closest('.ow_photo_item_wrap').data('url') + '#edit';
            
            window.location = url;
        },
        deleteAlbum: function( albumId )
        {
            if ( !confirm(OW.getLanguageText('photo', 'are_you_sure')) )
            {
                return;
            }

            _methods.sendRequest('ajaxDeletePhotoAlbum', albumId, function( data )
            {
                if ( data.result )
                {
                    OW.info(data.msg);

                    browsePhoto.removePhotoItems(['photo-item-' + albumId]);
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
        call: function( event )
        {
            var closest = $(this).closest('.ow_photo_item_wrap');
            
            _methods[event.data.action].apply(closest, [+closest.data('photoId')]);
            
            event.stopPropagation();
        },
        createElement: function( action, html, style )
        {
            return $('<li/>', {"class": style || ''}).html(
                $('<a/>', {href : 'javascript://'}).on('click', {action: action}, _methods.call).html(html)
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
                        _contextList.push($('<li/>').html($('<a/>').html(OW.getLanguageText('photo', 'edit_album')).on('mouseup', _methods.editAlbum)));
                        _contextList.push(_methods.createElement('deleteAlbum', OW.getLanguageText('photo', 'delete_album'), 'delete_album'));
                    }
                    break;
                default:
                    
                    if ( _params.downloadAccept === true )
                    {
                        _contextList.push($('<li/>').html($('<a/>', {"class": 'download', href : 'javascript://', 'target': 'photo-downloader'}).html(OW.getLanguageText('photo', 'download_photo'))));
                    }

                    if ( _params.isOwner )
                    {
                        _contextList.push(_methods.createElement('deletePhoto', OW.getLanguageText('photo', 'delete_photo')));
                        _contextList.push(_methods.createElement('editPhoto', OW.getLanguageText('photo', 'tb_edit_photo')));
                        _contextList.push($('<li/>', {"class": 'ow_context_action_divider_wrap'}).html($('<a/>', {"class": 'ow_context_action_divider'})));
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
            
            var event = {buttons:[]};
            OW.trigger('photo.collectMenuItems', [event]);
            
            if ( _contextList.length === 0 && event.buttons.length === 0 )
            {
                return;
            }

            var list = $('<ul>', {"class": 'ow_context_action_list'});

            _contextList.concat(event.buttons).forEach(function(item)
            {
                item.appendTo(list);
            });

            _params.contextAction = $('<div>', {"class": 'ow_photo_context_action'}).on('click', function( event )
            {
                event.stopImmediatePropagation();
            });
            _params.contextActionPrototype = $(document.getElementById('context-action-prototype')).removeAttr('id');
            _params.contextActionPrototype.find('.ow_tooltip_body').append(list);
            
            OW.bind('photo.onRenderPhotoItem', function( album )
            {
                var self = $(this);
                var prototype = _params.contextActionPrototype.clone(true);

                prototype.find('.download').attr('href', _params.downloadUrl.replace(':id', self.data('photoId')));

                if ( _params.listType == 'albums' && album.name.trim() == OW.getLanguageText('photo', 'newsfeed_album').trim() )
                {
                    prototype.find('.delete_album').remove();
                }
                
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
    
    root.photoContextAction = Object.defineProperties({},
    {
        init: {value: _methods.init}
    });
    
})( window, jQuery );
