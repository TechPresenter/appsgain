<?php /* CKEditor 5 CDN assets — require once per page that needs an editor */ ?>
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css" crossorigin>
<script src="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.umd.js" crossorigin></script>
<style>
/* ── CKEditor shell overrides ── */
.ck-editor-host { border-radius: 10px; overflow: hidden; border: 1.5px solid var(--border,#e2e8f0); }
.ck-editor-host:focus-within { border-color: var(--violet,#7c3aed); box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.ck.ck-toolbar { background: #f8fafc !important; border: none !important; border-bottom: 1px solid #e2e8f0 !important; padding: 6px 8px !important; border-radius: 0 !important; flex-wrap: wrap !important; }
.ck.ck-toolbar .ck-toolbar__items { flex-wrap: wrap !important; }
.ck.ck-editor__main .ck-editor__editable { border: none !important; border-radius: 0 !important; min-height: var(--ck-min-h, 420px); font-family: 'Figtree', sans-serif !important; font-size: 15px !important; line-height: 1.8 !important; color: #1a2340 !important; padding: 20px 24px !important; }
.ck.ck-editor__main .ck-editor__editable.ck-focused { box-shadow: none !important; }
.ck-editor-sm .ck.ck-editor__main .ck-editor__editable { min-height: 200px !important; }
.ck-editor-host .ck-powered-by { display: none !important; }
</style>
<script>
window.CKEditorInstances = window.CKEditorInstances || {};

/**
 * initCKEditor(textareaId, options)
 * Replaces a <textarea> with a full CKEditor 5 instance.
 * options.height  — custom min-height (default 420)
 * options.small   — true for compact mode (200px)
 */
window.initCKEditor = function(textareaId, opts) {
    opts = opts || {};
    var textarea = document.getElementById(textareaId);
    if (!textarea || !window.CKEDITOR) { return; }

    var CK = window.CKEDITOR;

    /* Safely pull plugins — filter undefined so missing ones don't crash */
    var plugins = [
        CK.Essentials, CK.Paragraph, CK.Heading,
        CK.Bold, CK.Italic, CK.Underline, CK.Strikethrough,
        CK.Link, CK.AutoLink,
        CK.List, CK.ListProperties,
        CK.BlockQuote,
        CK.Image, CK.ImageToolbar, CK.ImageCaption,
        CK.ImageStyle, CK.ImageResize, CK.LinkImage,
        CK.Table, CK.TableToolbar,
        CK.TableCellProperties, CK.TableProperties, CK.TableColumnResize,
        CK.HorizontalLine,
        CK.Code, CK.CodeBlock,
        CK.SourceEditing,
        CK.Indent, CK.IndentBlock,
        CK.Alignment,
        CK.FontSize, CK.FontFamily,
        CK.FontColor, CK.FontBackgroundColor,
        CK.RemoveFormat,
        CK.GeneralHtmlSupport,
        CK.Autoformat, CK.TextTransformation,
        CK.MediaEmbed,
        CK.FindAndReplace,
        CK.SpecialCharacters, CK.SpecialCharactersEssentials,
        CK.WordCount,
        CK.SelectAll,
        CK.Highlight,
    ].filter(Boolean);

    /* Build wrapper div */
    var host = document.createElement('div');
    host.className = 'ck-editor-host' + (opts.small ? ' ck-editor-sm' : '');
    if (opts.height) { host.style.setProperty('--ck-min-h', opts.height + 'px'); }
    textarea.parentNode.insertBefore(host, textarea);
    textarea.style.display = 'none';

    CK.ClassicEditor.create(host, {
        plugins: plugins,
        initialData: textarea.value,
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', 'removeFormat', '|',
                'alignment', '|',
                'bulletedList', 'numberedList', 'outdent', 'indent', '|',
                'link', 'blockQuote', 'insertTable', 'mediaEmbed', '|',
                'imageInsert', '|',
                'code', 'codeBlock', 'horizontalLine', 'specialCharacters', '|',
                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                'findAndReplace', 'sourceEditing', '|',
                'undo', 'redo'
            ],
            shouldNotGroupWhenFull: false
        },
        heading: {
            options: [
                { model:'paragraph', title:'Paragraph', class:'ck-heading_paragraph' },
                { model:'heading1', view:'h1', title:'Heading 1', class:'ck-heading_heading1' },
                { model:'heading2', view:'h2', title:'Heading 2', class:'ck-heading_heading2' },
                { model:'heading3', view:'h3', title:'Heading 3', class:'ck-heading_heading3' },
                { model:'heading4', view:'h4', title:'Heading 4', class:'ck-heading_heading4' },
            ]
        },
        image: {
            toolbar: [
                'imageStyle:inline','imageStyle:block','imageStyle:side','|',
                'toggleImageCaption','imageTextAlternative','|','linkImage',
                'resizeImage:50','resizeImage:75','resizeImage:original'
            ]
        },
        table: {
            contentToolbar: ['tableColumn','tableRow','mergeTableCells','tableProperties','tableCellProperties']
        },
        list: { properties: { styles:true, startIndex:true, reversed:true } },
        codeBlock: {
            languages: [
                {language:'php',        label:'PHP'},
                {language:'javascript', label:'JavaScript'},
                {language:'html',       label:'HTML'},
                {language:'css',        label:'CSS'},
                {language:'python',     label:'Python'},
                {language:'sql',        label:'SQL'},
                {language:'bash',       label:'Bash'},
                {language:'json',       label:'JSON'},
            ]
        },
        fontSize: {
            options: [10,11,12,13,14,16,18,20,22,24,28,32,36,48]
        },
        htmlSupport: {
            allow: [{ name:/.*/,attributes:true,classes:true,styles:true }]
        },
        link: { defaultProtocol:'https://', addTargetToExternalLinks:true, decorators:{
            openInNewTab:{mode:'manual',label:'Open in new tab',attributes:{target:'_blank',rel:'noopener'}}
        }},
        placeholder: opts.placeholder || 'Start writing content here…',
    }).then(function(editor) {
        window.CKEditorInstances[textareaId] = editor;

        /* Live sync */
        editor.model.document.on('change:data', function() {
            textarea.value = editor.getData();
        });

        /* Sync on form submit */
        var form = textarea.closest('form');
        if (form && !form.__ckSyncAdded) {
            form.__ckSyncAdded = true;
            form.addEventListener('submit', function() {
                Object.values(window.CKEditorInstances).forEach(function(ed) {
                    var ta = document.getElementById(ed.sourceElement ? ed.sourceElement.id : '');
                    /* find textarea by checking all instances */
                });
                /* Force sync all editors in this form */
                for (var id in window.CKEditorInstances) {
                    var ta2 = document.getElementById(id);
                    if (ta2 && form.contains(ta2)) {
                        ta2.value = window.CKEditorInstances[id].getData();
                        ta2.style.display = '';
                    }
                }
            });
        }
    }).catch(function(err) {
        console.error('CKEditor [' + textareaId + '] error:', err);
        textarea.style.display = ''; /* fallback: show raw textarea */
    });
};
</script>
