require('../css/form-type-code-editor.css');

import CodeMirror from 'codemirror';

import 'codemirror/mode/css/css';
import 'codemirror/mode/dockerfile/dockerfile';
import 'codemirror/mode/javascript/javascript';
import 'codemirror/mode/markdown/markdown';
import 'codemirror/mode/nginx/nginx';
import 'codemirror/mode/php/php';
import 'codemirror/mode/shell/shell';
import 'codemirror/mode/sql/sql';
import 'codemirror/mode/twig/twig';
import 'codemirror/mode/xml/xml';
import 'codemirror/mode/yaml-frontmatter/yaml-frontmatter';
import 'codemirror/mode/yaml/yaml';
import 'codemirror/addon/display/autorefresh';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ea-code-editor-field]').forEach((codeBlock) => {
        const editor = CodeMirror.fromTextArea(codeBlock, {
            autocapitalize: false,
            autocorrect: false,
            indentWithTabs: codeBlock.dataset.indentWithTabs === 'true',
            lineNumbers: codeBlock.dataset.showLineNumbers === 'true',
            lineWrapping: true,
            mode: codeBlock.dataset.language,
            scrollbarStyle: 'native',
            spellcheck: false,
            tabSize: codeBlock.dataset.tabSize,
            theme: 'default',
            autoRefresh: true,
            readOnly: codeBlock.readOnly,
        });

        if (codeBlock.required) {
            editor.on('change', editor.save);
        }

        if (codeBlock.dataset.numberOfRows !== '') {
            const wrapper = editor.getWrapperElement();
            // the height must include the vertical padding of the lines and the border of
            // the editor, or the last row is partially hidden
            const linesStyle = getComputedStyle(wrapper.querySelector('.CodeMirror-lines'));
            const verticalPadding =
                Number.parseFloat(linesStyle.paddingTop) + Number.parseFloat(linesStyle.paddingBottom);
            const verticalBorder = wrapper.offsetHeight - wrapper.clientHeight;
            const height =
                editor.defaultTextHeight() * codeBlock.dataset.numberOfRows + verticalPadding + verticalBorder;
            wrapper.style.setProperty('height', `${height}px`, 'important');
            wrapper.style.setProperty('max-block-size', 'none');
        }
    });
});
