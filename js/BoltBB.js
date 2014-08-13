CKEDITOR.replace( "form[editor]" );

CKEDITOR.editorConfig = function( config ) {
	config.language = 'en';
    config.uiColor = '#DDDDDD';
    config.resize_enabled = false;
    config.toolbar = [{ name: 'styles',      items: [ 'Format' ] },
                      { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike' ] },
                      { name: 'paragraph',   items: [ 'NumberedList', 'BulletedList', 'Indent', 'Outdent', '-', 'Blockquote' ] },
                      { name: 'links',       items: [ 'Link', 'Unlink' ] },
                      { name: 'source',      items: [ 'Source' ] }];
};