//
//
// // Initialize CodeMirror editor
// let editor;
// let isDarkTheme = false;
// let isFullscreen = false;
//
// // Language mode mappings
// const languageModes = {
//     html: 'htmlmixed',
//     css: 'css',
//     javascript: 'javascript',
//     php: 'application/x-httpd-php'
// };
//
// // Example code snippets
// const exampleCode = {
//     html: `<!DOCTYPE html>
// <html lang="en">
// <head>
//     <meta charset="UTF-8">
//     <meta name="viewport" content="width=device-width, initial-scale=1.0">
//     <title>Example Page</title>
// </head>
// <body>
//     <h1>Hello World!</h1>
//     <p>This is an example HTML document.</p>
// </body>
// </html>`,
//     css: `/* CSS Example */
// .container {
//     max-width: 1200px;
//     margin: 0 auto;
//     padding: 20px;
// }
//
// .button {
//     background: #007cba;
//     color: white;
//     padding: 10px 20px;
//     border: none;
//     border-radius: 4px;
//     cursor: pointer;
//     transition: background 0.3s ease;
// }
//
// .button:hover {
//     background: #005a87;
// }`,
//     javascript: `// JavaScript Example
// document.addEventListener('DOMContentLoaded', function() {
//     const button = document.querySelector('.my-button');
//
//     button.addEventListener('click', function() {
//         console.log('Button clicked!');
//         alert('Hello from JavaScript!');
//     });
//
//     // Example function
//     function calculateSum(a, b) {
//         return a + b;
//     }
//
//     const result = calculateSum(5, 3);
//     console.log('Result:', result);
// });`,
//     php: `<?php
// // PHP Example
// class ExampleClass {
//     private $data;
//
//     public function __construct($data) {
//         $this->data = $data;
//     }
//
//     public function processData() {
//         return strtoupper($this->data);
//     }
// }
//
// $example = new ExampleClass('hello world');
// echo $example->processData();
//
// // Database connection example
// $pdo = new PDO('mysql:host=localhost;dbname=example', $username, $password);
// $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
// $stmt->execute([$userId]);
// $user = $stmt->fetch();
// ?>`
// };
//
// // Initialize editor
// function initializeEditor() {
//     editor = CodeMirror(document.getElementById('wp-code-editor-container'), {
//         lineNumbers: true,
//         mode: languageModes.html,
//         theme: 'default',
//         indentUnit: 4,
//         lineWrapping: true,
//         autoCloseBrackets: true,
//         autoCloseTags: true,
//         foldGutter: true,
//         gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
//         hintOptions: {
//             completeSingle: false
//         },
//         extraKeys: {
//             "Ctrl-Space": "autocomplete",
//             "F11": toggleFullscreen,
//             "Esc": exitFullscreen
//         }
//     });
//
//     // Update hidden field on change
//     editor.on('change', function() {
//         const content = editor.getValue();
//         document.getElementById('code-content-hidden').value = content;
//         updateStats();
//     });
//
//     // Initial stats update
//     updateStats();
// }
//
// // Change language mode
// function changeLanguageMode(language) {
//     const mode = languageModes[language];
//     editor.setOption('mode', mode);
//     document.getElementById('language-indicator').textContent = language.toUpperCase();
//     showNotification(`Switched to ${language.toUpperCase()} mode`);
// }
//
// // Update editor stats
// function updateStats() {
//     const content = editor.getValue();
//     const lines = editor.lineCount();
//     const chars = content.length;
//     const words = content.trim() ? content.trim().split(/\s+/).length : 0;
//
//     document.getElementById('editor-stats').innerHTML =
//         `Lines: ${lines} | Characters: ${chars} | Words: ${words}`;
// }
//
// // Toggle theme
// function toggleTheme() {
//     isDarkTheme = !isDarkTheme;
//     const theme = isDarkTheme ? 'material' : 'default';
//     editor.setOption('theme', theme);
//     showNotification(`Switched to ${isDarkTheme ? 'dark' : 'light'} theme`);
// }
//
// // Toggle fullscreen
// function toggleFullscreen() {
//     isFullscreen = !isFullscreen;
//     const wrapper = editor.getWrapperElement();
//
//     if (isFullscreen) {
//         wrapper.classList.add('fullscreen');
//         editor.setSize('100%', '100vh');
//     } else {
//         exitFullscreen();
//     }
//
//     editor.refresh();
//     showNotification(isFullscreen ? 'Entered fullscreen' : 'Exited fullscreen');
// }
//
// // Exit fullscreen
// function exitFullscreen() {
//     if (isFullscreen) {
//         isFullscreen = false;
//         const wrapper = editor.getWrapperElement();
//         wrapper.classList.remove('fullscreen');
//         editor.setSize('100%', '300px');
//         editor.refresh();
//     }
// }
//
// // Copy code to clipboard
// async function copyCode() {
//     const content = editor.getValue();
//     try {
//         await navigator.clipboard.writeText(content);
//         showNotification('Code copied to clipboard!');
//     } catch (err) {
//         // Fallback for older browsers
//         const textarea = document.createElement('textarea');
//         textarea.value = content;
//         document.body.appendChild(textarea);
//         textarea.select();
//         document.execCommand('copy');
//         document.body.removeChild(textarea);
//         showNotification('Code copied to clipboard!');
//     }
// }
//
// // Download code as file
// function downloadCode() {
//     const content = editor.getValue();
//     const codeType = document.getElementById('code-type').value;
//     const extensions = {
//         html: 'html',
//         css: 'css',
//         javascript: 'js',
//         php: 'php'
//     };
//
//     const blob = new Blob([content], { type: 'text/plain' });
//     const url = URL.createObjectURL(blob);
//     const a = document.createElement('a');
//     a.href = url;
//     a.download = `code-snippet.${extensions[codeType]}`;
//     document.body.appendChild(a);
//     a.click();
//     document.body.removeChild(a);
//     URL.revokeObjectURL(url);
//
//     showNotification('Code downloaded successfully!');
// }
//
// // Insert example code
// function insertExample() {
//     const codeType = document.getElementById('code-type').value;
//     const example = exampleCode[codeType];
//     editor.setValue(example);
//     showNotification(`${codeType.toUpperCase()} example inserted!`);
// }
//
// // Show notification
// function showNotification(message) {
//     const notification = document.createElement('div');
//     notification.className = 'notification';
//     notification.textContent = message;
//     document.body.appendChild(notification);
//
//     setTimeout(() => notification.classList.add('show'), 10);
//
//     setTimeout(() => {
//         notification.classList.remove('show');
//         setTimeout(() => document.body.removeChild(notification), 300);
//     }, 3000);
// }
//
// // Initialize when DOM is loaded
// document.addEventListener('DOMContentLoaded', function() {
//     // Initialize editor
//     initializeEditor();
//
//     // Code type change listener
//     document.getElementById('code-type').addEventListener('change', function() {
//         changeLanguageMode(this.value);
//     });
//
//     // Toolbar button listeners
//     document.getElementById('theme-toggle-btn').addEventListener('click', toggleTheme);
//     document.getElementById('fullscreen-btn').addEventListener('click', toggleFullscreen);
//     document.getElementById('copy-code-btn').addEventListener('click', copyCode);
//     document.getElementById('download-code-btn').addEventListener('click', downloadCode);
//     document.getElementById('insert-example-btn').addEventListener('click', insertExample);
//
//     // Keyboard shortcuts
//     document.addEventListener('keydown', function(e) {
//         if (e.key === 'Escape' && isFullscreen) {
//             exitFullscreen();
//         }
//     });
// });
//
// // Make functions globally available for event handlers
// window.toggleFullscreen = toggleFullscreen;
// window.exitFullscreen = exitFullscreen;