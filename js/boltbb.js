/*
 * Insert CKEditor
 */
CKEDITOR.replace(ckfield);

/*
 * BoltBB Specific CKEditor extensions
 */
CKEDITOR.plugins.addExternal('codeTag',     boltbb_basepath + 'js/ckeditor/plugins/codeTag/', 'plugin.js');
CKEDITOR.plugins.addExternal('codesnippet', boltbb_basepath + 'js/ckeditor/plugins/codesnippet/', 'plugin.js');
CKEDITOR.plugins.addExternal('footnotes',   boltbb_basepath + 'js/ckeditor/plugins/footnotes/', 'plugin.js');

/*
 * CKEditor configuration
 */
CKEDITOR.editorConfig = function( config ) {
    config.language = ckeditor_lang || 'en';
    config.uiColor = '#DDDDDD';
    config.resize_enabled = true;
    config.extraPlugins = 'codemirror';

    /* Initial toolbars */
    config.toolbar = [{ name: 'styles',      items: [ 'Format' ] },
                      { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike' ] },
                      { name: 'paragraph',   items: [ 'NumberedList', 'BulletedList', 'Indent', 'Outdent', '-', 'Blockquote' ] } 
                      ];
    
    /* Text Clear Formatting */
    config.toolbar = config.toolbar.concat({ name: 'format', items: [ 'RemoveFormat' ] });
    
    /* Subscript / Superscript */
    if (wysiwyg.subsuper) {
        config.toolbar = config.toolbar.concat({ name: 'subsuper', items: [ 'Subscript', 'Superscript' ] });
    }

    /* Link generation */
    if (wysiwyg.anchor) {
        config.toolbar = config.toolbar.concat({ name: 'links', items: [ 'Link', 'Unlink', '-', 'Anchor' ] });
    } else {
        config.toolbar = config.toolbar.concat({ name: 'links', items: [ 'Link', 'Unlink' ] });
    }

    /* CodeTag */
    if (wysiwyg.codetag) {
        config.extraPlugins = config.extraPlugins + ',codeTag';
        config.toolbar = config.toolbar.concat({ name: 'codetag', items: [ 'Code' ] });
    }

    /* CodeSnippet */
    if (wysiwyg.codesnippet) {
        config.extraPlugins = config.extraPlugins + ',codesnippet';
        config.toolbar = config.toolbar.concat({ name: 'codesnippet', items: [ 'CodeSnippet' ] });
    }
    
    /* Footnotes */
    if (wysiwyg.footnotes) {
        config.extraPlugins = config.extraPlugins + ',footnotes';
        config.toolbar = config.toolbar.concat({ name: 'footnotes', items: [ 'Footnotes' ] });
    }

    /* Images & Embedded Media */
    if (wysiwyg.images || wysiwyg.embed ) {
        
        var media = { name: 'media', items: [] };

        if (wysiwyg.images) {
            media.items.push('Image');
        }

        if (wysiwyg.embed) {
            config.extraPlugins += ',oembed,widget';
            config.oembed_maxWidth = '853';
            config.oembed_maxHeight = '480';
            
            media.items.push('oembed');
        }
        
        config.toolbar.push(media);
    }

    /* Tables */
    if (wysiwyg.tables) {
        config.toolbar = config.toolbar.concat({ name: 'table',  items: [ 'Table' ] });
    }
    
    /* Text Alignment */
    if (wysiwyg.align) {
        config.toolbar = config.toolbar.concat({ name: 'align',  items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] });
    }
    
    /* Font Colour */
    if (wysiwyg.fontcolor) {
        config.toolbar = config.toolbar.concat({ name: 'colors', items: [ 'TextColor', 'BGColor' ] });
    }
    
    /* Tools */
    config.toolbar = config.toolbar.concat({ name: 'tools',  items: [ 'SpecialChar', '-', 'Maximize' ] });
    
    /* Display Source */
    config.toolbar = config.toolbar.concat({ name: 'source', items: [ 'Source' ] });

    /* Parse override settings from config */
    for (var key in wysiwyg.ck){
        if (wysiwyg.ck.hasOwnProperty(key)) {
             config[key] = wysiwyg.ck[key];
        }
    }

    /* Codemirror settings */
    config.codemirror = {
        theme: 'default',
        lineNumbers: true,
        lineWrapping: true,
        matchBrackets: true,
        autoCloseTags: true,
        autoCloseBrackets: true,
        enableSearchTools: true,
        enableCodeFolding: true,
        enableCodeFormatting: true,
        autoFormatOnStart: true,
        autoFormatOnUncomment: true,
        highlightActiveLine: true,
        highlightMatches: true,
        showFormatButton: false,
        showCommentButton: false,
        showUncommentButton: false
    };
};

/*
 * CSS Emoticons trigger elements
 * 
 * See: http://os.alfajango.com/css-emoticons/
 */
$( document ).ready(function() {
    $('.topic p').emoticonize();
    $('.reply p').emoticonize();
    });
