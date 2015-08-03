window.photoUtils = Object.freeze({
    extend: function( ctor, superCtor )
    {
        ctor.prototype = Object.create(superCtor.prototype, {
            constructor: {
                value: ctor,
                enumerable: false,
                writable: true,
                configurable: true
            }
        });
    },

    bind: function( context )
    {
        return function( f )
        {
            return f.bind(context);
        };
    },

    fluent: function( f )
    {
        return function()
        {
            f.apply(this, arguments);

            return this;
        };
    },

    truncate: function( value, limit, ended )
    {
        if ( !value ) return '';

        value = String(value);
        limit = +limit || 50;
        ended = ended || '...';

        var parts;

        if ( (parts = value.split('\n')).length >= 3 )
        {
            value = parts.slice(0, 3).join('\n') + ended;
        }
        else if ( value.length > limit )
        {
            value = value.substring(0, limit) + ended;
        }

        return value;
    },

    hashtagPattern: /#(?:\w|[^\u0000-\u007F])+/g,

    getHashtags: function( text )
    {
        var result = {};

        text.replace(this.hashtagPattern, function( str, offest )
        {
            result[offest] = str;
        });

        return result;
    },

    descToHashtag: function( description, hashtags, url )
    {
        var url = '<a href="' + url + '">{$tagLabel}</a>';

        return description.replace(this.hashtagPattern, function( str, offest )
        {
            return (url.replace('-tag-', encodeURIComponent(hashtags[offest]))).replace('{$tagLabel}', str);
        }).replace(/\n/g, '<br>');
    },

    includeScriptAndStyle: function( markup )
    {
        if ( !markup ) return;

        if (markup.styleSheets)
        {
            $.each(markup.styleSheets, function(i, o)
            {
                OW.addCssFile(o);
            });
        }

        if (markup.styleDeclarations)
        {
            OW.addCss(markup.styleDeclarations);
        }

        if (markup.beforeIncludes)
        {
            OW.addScript(markup.beforeIncludes);
        }

        if (markup.scriptFiles)
        {
            OW.addScriptFiles(markup.scriptFiles, function()
            {
                if (markup.onloadScript)
                {
                    OW.addScript(markup.onloadScript);
                    options.onLoad();
                }
            });
        }
        else
        {
            if (markup.onloadScript)
            {
                OW.addScript(markup.onloadScript);
            }

            options.onLoad();
        }
    }
});