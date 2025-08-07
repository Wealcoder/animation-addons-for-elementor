let editor;
let isDarkTheme = false;
let isFullscreen = false;

// Language mode mappings
const languageModes = {
    html: 'htmlmixed',
    css: 'css',
    javascript: 'javascript',
    php: 'application/x-httpd-php'
};

// Example code snippets
const exampleCode = {
    html: `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Example Page</title>
</head>
<body>
    <h1>Code is Poetry.</h1>
</body>
</html>`,
    css: `/* CSS Example */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.button {
    background: #007cba;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.button:hover {
    background: #005a87;
}`,
    javascript: `// JavaScript Example
document.addEventListener('DOMContentLoaded', function() {
    console.log('Code is Poetry.');
});`,
    php: `<?php
// PHP Example
echo 'Code is Poetry.';`
};

// Initialize editor
function initializeEditor() {
    editor = CodeMirror(document.getElementById('wp-code-editor-container'), {
        lineNumbers: true,
        mode: languageModes.html,
        theme: 'default',
        indentUnit: 4,
        lineWrapping: true,
        autoCloseBrackets: true,
        autoCloseTags: true,
        foldGutter: true,
        gutters: [
            "CodeMirror-linenumbers",
            "CodeMirror-lint-markers",
            "CodeMirror-foldgutter",
        ],
        extraKeys: {
            "Ctrl-Space": "autocomplete",
            "F11": toggleFullscreen,
            "Esc": exitFullscreen
        },
        tabSize: 4,
        readOnly: false,
        matchBrackets: true,
        styleActiveLine: true,
        showCursorWhenSelecting: true,
        scrollbarStyle: 'native',
        viewportMargin: Infinity,
        cursorBlinkRate: 530,
        dragDrop: true,
        hintOptions: {
            completeSingle: false,
            alignWithWord: true,
        },
        value: document.getElementById('code-content-hidden').value || '',
    });

    // Update hidden field on change
    editor.on('change', function() {
        document.getElementById('code-content-hidden').value = editor.getValue();
        updateStats();
    });

    // Initial stats update
    updateStats();
}

// Change language mode
function changeLanguageMode(language) {
    const mode = languageModes[language];
    editor.setOption('mode', mode);

    const indicator = document.getElementById('language-indicator');
    if (indicator) {
        indicator.textContent = language.toUpperCase();
    }

    showNotification(`Switched to ${language.toUpperCase()} mode`);
}

// Update editor stats
function updateStats() {
    const content = editor.getValue();
    const lines = editor.lineCount();
    const chars = content.length;
    const words = content.trim() ? content.trim().split(/\s+/).length : 0;

    document.getElementById('editor-stats').innerHTML =
        `Lines: ${lines} | Characters: ${chars} | Words: ${words}`;
}

// Toggle theme
function toggleTheme() {
    isDarkTheme = !isDarkTheme;
    const theme = isDarkTheme ? 'material' : 'default';
    editor.setOption('theme', theme);
    showNotification(`Switched to ${isDarkTheme ? 'dark' : 'light'} theme`);
}

// Toggle fullscreen
function toggleFullscreen() {
    isFullscreen = !isFullscreen;
    const wrapper = editor.getWrapperElement();

    if (isFullscreen) {
        wrapper.classList.add('fullscreen');
        editor.setSize('100%', '100vh');
    } else {
        exitFullscreen();
    }

    editor.refresh();
    showNotification(isFullscreen ? 'Entered fullscreen' : 'Exited fullscreen');
}

// Exit fullscreen
function exitFullscreen() {
    if (isFullscreen) {
        isFullscreen = false;
        const wrapper = editor.getWrapperElement();
        wrapper.classList.remove('fullscreen');
        editor.setSize('100%', '300px');
        editor.refresh();
    }
}

// Copy code to clipboard
async function copyCode() {
    const content = editor.getValue();
    try {
        await navigator.clipboard.writeText(content);
        showNotification('Code copied to clipboard!');
    } catch (err) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = content;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('Code copied to clipboard!');
    }
}

// Download code as file
function downloadCode() {
    const content = editor.getValue();
    const codeType = document.getElementById('code-type').value;
    const extensions = {
        html: 'html',
        css: 'css',
        javascript: 'js',
        php: 'php'
    };

    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `code-snippet.${extensions[codeType]}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showNotification('Code downloaded successfully!');
}

// Insert example code
function insertExample() {
    const codeType = document.getElementById('code-type').value;
    let example = exampleCode[codeType];
    if (codeType === 'php' && !example.trim().startsWith('<?php')) {
        example = `<?php\n` + example;
    }
    editor.setValue(example);
    showNotification(`${codeType.toUpperCase()} example inserted!`);
}

// Show notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => notification.classList.add('show'), 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize editor
    initializeEditor();

    // Code type change listener
    document.getElementById('code-type').addEventListener('change', function() {
        const confirmClear = confirm('Switching code type will clear the editor. Continue?');
        if (confirmClear) {
            changeLanguageMode(this.value);
            if ( this.value === 'php' ) {
                editor.setValue('<?php\n\n');
            } else {
                editor.setValue('');
            }
            showNotification(`Editor cleared for ${this.value.toUpperCase()} mode`);
        } else {
            this.value = editor.getOption('mode');
        }
    });

    // Toolbar button listeners
    document.getElementById('theme-toggle-btn').addEventListener('click', toggleTheme);
    document.getElementById('fullscreen-btn').addEventListener('click', toggleFullscreen);
    document.getElementById('copy-code-btn').addEventListener('click', copyCode);
    document.getElementById('download-code-btn').addEventListener('click', downloadCode);
    document.getElementById('insert-example-btn').addEventListener('click', insertExample);

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isFullscreen) {
            exitFullscreen();
        }
    });
});

// Show Hide location fields.
document.addEventListener('DOMContentLoaded', function () {
    const codeTypeSelect = document.getElementById('code-type');
    const loadLocationField = document.getElementById('load-location').closest('.form-group');

    function toggleLoadLocation() {
        if (codeTypeSelect.value === 'php') {
            loadLocationField.style.display = 'none';
        } else {
            loadLocationField.style.display = '';
        }
    }

    // Initial check
    toggleLoadLocation();

    // On change
    codeTypeSelect.addEventListener('change', toggleLoadLocation);
});