/*
 * CKEditor configuration
 */
CKEDITOR.replace( "form[editor]" );

CKEDITOR.editorConfig = function( config ) {
    config.language = 'en';
    config.uiColor = '#DDDDDD';
    config.resize_enabled = true;
    config.extraPlugins = 'codemirror';
    config.toolbar = [{ name: 'styles',      items: [ 'Format' ] },
                      { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike' ] },
                      { name: 'paragraph',   items: [ 'NumberedList', 'BulletedList', 'Indent', 'Outdent', '-', 'Blockquote' ] },
                      { name: 'links',       items: [ 'Link', 'Unlink' ] },
                      { name: 'source',      items: [ 'Source' ] }];

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
