let editor;
let isDarkTheme = false;
let isFullscreen = false;
let escButton; // ESC button element

// Language mode mappings
const languageModes = {
    html: 'htmlmixed',
    css: 'css',
    javascript: 'javascript',
    php: 'application/x-httpd-php'
};

// Example code snippets
const exampleCode = {
    html: `<h1>Code is Poetry.</h1>`,
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

// Create ESC button and inject CSS
function createEscButton() {
    // Inject CSS for fullscreen and ESC button
    const fullscreenCSS = `
    .CodeMirror.fullscreen {
        position: fixed !important;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100% !important;
        height: 100% !important;
        z-index: 9999;
        background: #fff;
    }

    .CodeMirror.fullscreen.cm-s-material {
        background: #263238;
    }

    .fullscreen-esc-button {
        position: fixed;
        top: 40px;
        right: 15px;
        z-index: 10000;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        border: 1px solid #555;
        border-radius: 6px;
        padding: 8px 16px;
        cursor: pointer;
        font-family: monospace;
        font-size: 12px;
        font-weight: bold;
        display: none;
        transition: all 0.2s ease;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .fullscreen-esc-button:hover {
        background: rgba(0, 0, 0, 0.9);
        transform: translateY(-1px);
    }

    .fullscreen-esc-button.show {
        display: block;
    }

    .fullscreen-esc-button::before {
        content: "⎋ ";
        margin-right: 4px;
    }
    `;

    // Inject CSS
    const styleSheet = document.createElement("style");
    styleSheet.textContent = fullscreenCSS;
    document.head.appendChild(styleSheet);

    // Create ESC button
    escButton = document.createElement('button');
    escButton.className = 'fullscreen-esc-button';
    escButton.innerHTML = 'ESC';
    escButton.title = 'Exit Fullscreen (ESC key)';
    document.body.appendChild(escButton);

    // Add click event to ESC button
    escButton.addEventListener('click', function() {
        if (isFullscreen) {
            exitFullscreen();
        }
    });
}

// Initialize editor
function initializeEditor() {
    const editorContainer = document.getElementById('wp-code-editor-container');
    const hiddenField = document.getElementById('code-content-hidden');

    if (!editorContainer) {
        console.warn('Editor container not found');
        return;
    }

    editor = CodeMirror(editorContainer, {
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
            "Esc": exitFullscreen,
            // Additional useful shortcuts
            "Ctrl-/": "toggleComment",
            "Ctrl-D": "deleteLine",
            "Ctrl-]": "indentMore",
            "Ctrl-[": "indentLess",
            "Ctrl-F": "find",
            "F3": "findNext",
            "Shift-F3": "findPrev",
            "Ctrl-H": "replace",
            "Ctrl-G": "jumpToLine",
            "Ctrl-A": "selectAll",
            "Ctrl-L": "selectLine",
            "Alt-Up": function(cm) {
                // Move line up
                var cursor = cm.getCursor();
                if (cursor.line > 0) {
                    var line = cm.getLine(cursor.line);
                    var prevLine = cm.getLine(cursor.line - 1);
                    cm.replaceRange(line + '\n' + prevLine,
                        {line: cursor.line - 1, ch: 0},
                        {line: cursor.line + 1, ch: 0});
                    cm.setCursor(cursor.line - 1, cursor.ch);
                }
            },
            "Alt-Down": function(cm) {
                // Move line down
                var cursor = cm.getCursor();
                if (cursor.line < cm.lineCount() - 1) {
                    var line = cm.getLine(cursor.line);
                    var nextLine = cm.getLine(cursor.line + 1);
                    cm.replaceRange(nextLine + '\n' + line,
                        {line: cursor.line, ch: 0},
                        {line: cursor.line + 2, ch: 0});
                    cm.setCursor(cursor.line + 1, cursor.ch);
                }
            },
            "Ctrl-Shift-D": function(cm) {
                // Duplicate line
                var cursor = cm.getCursor();
                var line = cm.getLine(cursor.line);
                cm.replaceRange('\n' + line, {line: cursor.line, ch: cm.getLine(cursor.line).length});
                cm.setCursor(cursor.line + 1, cursor.ch);
            }
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
        value: hiddenField ? hiddenField.value || '' : '',
    });

    // Update hidden field on change
    if (hiddenField) {
        editor.on('change', function() {
            hiddenField.value = editor.getValue();
            updateStats();
        });
    }

    // Initial stats update
    updateStats();
}

// Change language mode
function changeLanguageMode(language) {
    if (!editor) return;

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
    if (!editor) return;

    const content = editor.getValue();
    const lines = editor.lineCount();
    const chars = content.length;
    const words = content.trim() ? content.trim().split(/\s+/).length : 0;

    const statsElement = document.getElementById('editor-stats');
    if (statsElement) {
        statsElement.innerHTML = `Lines: ${lines} | Characters: ${chars} | Words: ${words}`;
    }
}

// Toggle theme
function toggleTheme() {
    if (!editor) return;

    isDarkTheme = !isDarkTheme;
    const theme = isDarkTheme ? 'material' : 'default';
    editor.setOption('theme', theme);
    showNotification(`Switched to ${isDarkTheme ? 'dark' : 'light'} theme`);
}

// Toggle fullscreen
function toggleFullscreen() {
    if (!editor) return;

    isFullscreen = !isFullscreen;
    const wrapper = editor.getWrapperElement();

    if (isFullscreen) {
        wrapper.classList.add('fullscreen');
        editor.setSize('100%', '100vh');
        if (escButton) {
            escButton.classList.add('show'); // Show ESC button
        }
        editor.focus(); // Focus the editor
    } else {
        exitFullscreen();
    }

    editor.refresh();
    showNotification(isFullscreen ? 'Entered fullscreen mode - Press ESC to exit' : 'Exited fullscreen');
}

// Exit fullscreen
function exitFullscreen() {
    if (!editor || !isFullscreen) return;

    isFullscreen = false;
    const wrapper = editor.getWrapperElement();
    wrapper.classList.remove('fullscreen');
    editor.setSize('100%', '300px');
    if (escButton) {
        escButton.classList.remove('show'); // Hide ESC button
    }
    editor.refresh();
    showNotification('Exited fullscreen mode');
}

// Copy code to clipboard
async function copyCode() {
    if (!editor) return;

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
    if (!editor) return;

    const content = editor.getValue();
    const codeTypeElement = document.getElementById('code-type');
    if (!codeTypeElement) return;

    const codeType = codeTypeElement.value;
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
    if (!editor) return;

    const codeTypeElement = document.getElementById('code-type');
    if (!codeTypeElement) return;

    const codeType = codeTypeElement.value;
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
    notification.style.cssText = `
        position: fixed;
        top: 40px;
        right: 24%;
        background: #333;
        color: white;
        padding: 10px 16px;
        border-radius: 4px;
        font-size: 14px;
        z-index: 10001;
        opacity: 0;
        transition: opacity 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    document.body.appendChild(notification);

    setTimeout(() => notification.style.opacity = '1', 10);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                document.body.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Create ESC button first
    createEscButton();

    // Initialize editor
    initializeEditor();

    // Code type change listener
    const codeTypeElement = document.getElementById('code-type');
    if (codeTypeElement) {
        codeTypeElement.addEventListener('change', function() {
            const confirmClear = confirm('Switching code type will clear the editor. Continue?');
            if (confirmClear) {
                changeLanguageMode(this.value);
                if (this.value === 'php') {
                    if (editor) {
                        editor.setValue('<?php\n\n');
                    }
                } else {
                    if (editor) {
                        editor.setValue('');
                    }
                }
                showNotification(`Editor cleared for ${this.value.toUpperCase()} mode`);
            } else {
                if (editor) {
                    this.value = editor.getOption('mode');
                }
            }
        });
    }

    // Toolbar button listeners
    const themeToggleBtn = document.getElementById('theme-toggle-btn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', toggleTheme);
    }

    const fullscreenBtn = document.getElementById('fullscreen-btn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', toggleFullscreen);
    }

    const copyCodeBtn = document.getElementById('copy-code-btn');
    if (copyCodeBtn) {
        copyCodeBtn.addEventListener('click', copyCode);
    }

    const downloadCodeBtn = document.getElementById('download-code-btn');
    if (downloadCodeBtn) {
        downloadCodeBtn.addEventListener('click', downloadCode);
    }

    const insertExampleBtn = document.getElementById('insert-example-btn');
    if (insertExampleBtn) {
        insertExampleBtn.addEventListener('click', insertExample);
    }

    // Enhanced keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isFullscreen) {
            exitFullscreen();
        }
        // Quick theme toggle with Ctrl+T
        if (e.ctrlKey && e.key === 't' && !e.shiftKey) {
            e.preventDefault();
            toggleTheme();
        }
    });
});

// Show Hide location fields.
document.addEventListener('DOMContentLoaded', function () {
    const codeTypeSelect = document.getElementById('code-type');
    const loadLocationElement = document.getElementById('load-location');
    const loadNotice = document.getElementById('php-version-notice');

    if (!codeTypeSelect || !loadLocationElement || !loadNotice) return;

    const loadLocationField = loadLocationElement.closest('.form-group');
    if (!loadLocationField) return;

    function toggleLoadLocation() {
        if (codeTypeSelect.value === 'php') {
            loadLocationField.style.display = 'none';
            loadNotice.style.display = '';
        } else {
            loadLocationField.style.display = '';
            loadNotice.style.display = 'none';
        }
    }

    // Initial check
    toggleLoadLocation();

    // On change
    codeTypeSelect.addEventListener('change', toggleLoadLocation);
});

document.addEventListener('DOMContentLoaded', function () {
    const codeTypeSelect = document.getElementById('visibility-page');
    const loadLocationElement = document.getElementById('visibility-page-list');

    if (!codeTypeSelect || !loadLocationElement) return;

    const loadLocationField = loadLocationElement.closest('.form-subgroup');
    if (!loadLocationField) return;

    function toggleLoadLocation() {
        if (codeTypeSelect.value !== 'specifics') {
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

function updatePriorityValue(value) {
    const priorityValueElement = document.getElementById('priority-value');
    if (priorityValueElement) {
        priorityValueElement.textContent = value;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const slider = document.getElementById('priority-slider');
    const valueDisplay = document.getElementById('priority-value');

    if (slider && valueDisplay) {
        valueDisplay.textContent = slider.value;
        slider.addEventListener('input', function () {
            valueDisplay.textContent = this.value;
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const codeTypeSelect = document.querySelector('[name="code_type"]');
    const codeTextarea = document.querySelector('[name="code_content"]');

    if (!codeTypeSelect || !codeTextarea) return;

    // Check if WCFCustomCodeVars exists and has the required property
    if (typeof WCFCustomCodeVars === 'undefined' ||
        !WCFCustomCodeVars.serverDetails ||
        !WCFCustomCodeVars.serverDetails.currentVersion) {
        console.warn('WCFCustomCodeVars not found or missing serverDetails');
        return;
    }

    const phpVersion = WCFCustomCodeVars.serverDetails.currentVersion;

    function checkPHPVersion() {
        if (codeTypeSelect && codeTypeSelect.value === 'php') {
            let notice = document.getElementById('php-version-notice');
            if (notice) {
                notice.innerHTML = `Server is running <strong>PHP ${phpVersion}</strong>. Please ensure your code is compatible.`;
            }
        }
    }

    const featureChecks = [
        // === PHP 7.1 ===
        { regex: /void\s+function/, min: 7.1, name: 'void return type' },
        { regex: /iterable\s+function/, min: 7.1, name: 'iterable type' },
        { regex: /public\s+const|private\s+const|protected\s+const/, min: 7.1, name: 'Visibility on class constants' },
        { regex: /\?\?=/, min: 7.4, name: 'Null coalescing assignment (??=)' },

        // === PHP 7.2 ===
        { regex: /object\s+\$[A-Za-z_]/, min: 7.2, name: 'object type hint' },
        { regex: /(?<!::)count\(/, min: 7.2, name: 'count() with Countable objects' }, // mild check
        { regex: /stream_isatty\s*\(/, min: 7.2, name: 'stream_isatty() function' },

        // === PHP 7.3 ===
        { regex: /array_key_first\s*\(/, min: 7.3, name: 'array_key_first()' },
        { regex: /array_key_last\s*\(/, min: 7.3, name: 'array_key_last()' },
        { regex: /hrtime\s*\(/, min: 7.3, name: 'hrtime() function' },

        // === PHP 7.4 ===
        { regex: /fn\s*\(.*\)\s*=>/, min: 7.4, name: 'Arrow functions' },
        { regex: /[A-Za-z0-9_]+\s*\??:\s*[A-Za-z0-9_]+/, min: 7.4, name: 'Nullable property types' },
        { regex: /[A-Za-z0-9_]+\s+:\s*[A-Za-z0-9_]+(\s*\|[A-Za-z0-9_]+)+/, min: 8.0, name: 'Union types (PHP 8.0, but check early)' },
        { regex: /Typed property/, min: 7.4, name: 'Typed properties' },

        // === PHP 8.0 ===
        { regex: /match\s*\(/, min: 8.0, name: 'match expression' },
        { regex: /#[A-Za-z0-9_]+/, min: 8.0, name: 'Attributes syntax (#[])"' },
        { regex: /str_contains\s*\(/, min: 8.0, name: 'str_contains()' },
        { regex: /str_starts_with\s*\(/, min: 8.0, name: 'str_starts_with()' },
        { regex: /str_ends_with\s*\(/, min: 8.0, name: 'str_ends_with()' },
        { regex: /get_debug_type\s*\(/, min: 8.0, name: 'get_debug_type()' },
        { regex: /fdiv\s*\(/, min: 8.0, name: 'fdiv()' },
        { regex: /throw\s+\S+/, min: 8.0, name: 'throw as expression' },
        { regex: /static\s+function\s*\(/, min: 8.0, name: 'Static anonymous functions' },

        // === PHP 8.1 ===
        { regex: /readonly\s+/, min: 8.1, name: 'Readonly properties' },
        { regex: /enum\s+[A-Za-z_]/, min: 8.1, name: 'Enums' },
        { regex: /fibers?/i, min: 8.1, name: 'Fibers API' },
        { regex: /never\s+function/, min: 8.1, name: 'never return type' },
        { regex: /array_is_list\s*\(/, min: 8.1, name: 'array_is_list()' },

        // === PHP 8.2 ===
        { regex: /readonly\s+class/, min: 8.2, name: 'Readonly classes' },
        { regex: /true\s*\|\s*false/, min: 8.2, name: 'true/false as standalone types' },
        { regex: /null\s*\|/, min: 8.2, name: 'null as standalone type' },
        { regex: /#[A-Za-z0-9_]+\(.+\)/, min: 8.2, name: 'Disjunctive Normal Form types (DNF)' },

        // === PHP 8.3 ===
        { regex: /json_validate\s*\(/, min: 8.3, name: 'json_validate()' },
        { regex: /#[A-Za-z0-9_]+\(.+\)/, min: 8.3, name: 'Explicitly negative keys in array destructuring' },

        // === PHP 8.4 ===
        { regex: /array_find\s*\(/, min: 8.4, name: 'array_find()' },
        { regex: /array_all\s*\(/, min: 8.4, name: 'array_all()' },
        { regex: /array_any\s*\(/, min: 8.4, name: 'array_any()' },
        { regex: /\{\s*get\s*=>/, min: 8.4, name: 'Property hooks' },
    ];

    function preventHighVersionUsage() {
        const code = codeTextarea.value;
        const currentVersion = parseFloat(phpVersion);
        let blocked = false;

        featureChecks.forEach(feature => {
            if (currentVersion < feature.min && feature.regex.test(code)) {
                alert(`Your code uses ${feature.name}, which requires PHP ${feature.min}+ but the server is running PHP ${phpVersion}.`);
                blocked = true;
            }
        });

        return !blocked;
    }

    // Show notice on load and change
    checkPHPVersion();
    preventHighVersionUsage();
    codeTypeSelect.addEventListener('change', checkPHPVersion);

    // Hook into form submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!preventHighVersionUsage()) {
                e.preventDefault();
            }
        });
    }
});

/// Ajax Call for page select
(function($){
    "use strict";
    const WCFCustomCode = {
        init: function() {
            $('#visibility-page-list').select2({
                ajax: {
                    url: ajaxurl,
                    dataType: 'json',
                    method: 'post',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            page: params.page || 1,
                            action: 'add_custom_page',
                            nonce: WCFCustomCodeVars.nonce,
                        };
                    },
                    processResults: function (data) {
                        let uniqueData = [];
                        let seen = new Set();
                        data.forEach(item => {
                            if (!seen.has(item.id)) {
                                seen.add(item.id);
                                uniqueData.push(item);
                            }
                        });
                        return {
                            results: uniqueData
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Search and select an option',
                allowClear: true
            });
        },
    };

    WCFCustomCode.init();
})(jQuery);


document.addEventListener('DOMContentLoaded', function() {
    const codeTypeSelect = document.getElementById('code-type');
    const visibilityPageSelect = document.getElementById('visibility-page');

    if (!codeTypeSelect || !visibilityPageSelect) return;

    // Store original options for restoration
    const originalOptions = visibilityPageSelect.innerHTML;

    function updateVisibilityOptions() {
        const selectedCodeType = codeTypeSelect.value;

        // Store currently selected value before updating options
        const currentlySelected = visibilityPageSelect.value;

        if (selectedCodeType === 'php') {
            // Clear all options and add only allowed ones for PHP
            visibilityPageSelect.innerHTML = `
                <optgroup label="Basic">
                    <option value="">None</option>
                    <option value="global">Entire Website</option>
                    <option value="frontend">Frontend</option>
                    <option value="admin">Backend</option>
                </optgroup>
            `;
        } else {
            // Restore original options for other code types
            visibilityPageSelect.innerHTML = originalOptions;
        }

        // Try to restore the previously selected value if it still exists
        const optionExists = Array.from(visibilityPageSelect.options).some(option => option.value === currentlySelected);
        if (optionExists) {
            visibilityPageSelect.value = currentlySelected;
        }
    }

    // Add event listener to code type select
    codeTypeSelect.addEventListener('change', updateVisibilityOptions);

    // Initialize on page load - check current value and update accordingly
    updateVisibilityOptions();

    // Also check on page reload/refresh to maintain state
    window.addEventListener('load', function() {
        updateVisibilityOptions();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const codeType = document.getElementById('code-type');
    const loadLocation = document.getElementById('load-location');

    if (!codeType || !loadLocation) return;
    function updateLoadLocations() {
        const value = codeType.value;

        Array.from(loadLocation.options).forEach(option => {
            if (value === 'javascript') {
                // Only enable head, footer, and empty option
                option.disabled = !(option.value === '' || option.value === 'head' || option.value === 'footer');
            } else {
                // HTML, CSS, PHP → enable all options
                option.disabled = false;
            }
        });

        // Auto-select first enabled option if current selection is disabled
        if (loadLocation.options[loadLocation.selectedIndex].disabled) {
            const firstEnabled = Array.from(loadLocation.options).find(opt => !opt.disabled);
            if (firstEnabled) {
                loadLocation.value = firstEnabled.value;
            }
        }
    }

    // Run once on page load
    updateLoadLocations();

    // Listen for code type changes
    codeType.addEventListener('change', updateLoadLocations);
});


