function updatePriorityValue(value) {
	document.getElementById('priority-value').textContent = value;
}

// Auto-resize textarea
const textarea = document.getElementById('code-content');
textarea.addEventListener('input', function() {
	this.style.height = 'auto';
	this.style.height = this.scrollHeight + 'px';
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
	e.preventDefault();

	const title = document.getElementById('snippet-title').value;
	const code = document.getElementById('code-content').value;

	if (!title || !code) {
		alert('Please fill in all required fields');
		return;
	}

	// Here you would normally submit the form data to your WordPress backend
	alert('Code snippet saved successfully!');
});

// Real-time code type detection
document.getElementById('code-content').addEventListener('input', function() {
	const code = this.value.toLowerCase();
	const typeSelect = document.getElementById('code-type');

	if (code.includes('<style>') || code.includes('css')) {
		typeSelect.value = 'css';
	} else if (code.includes('<script>') || code.includes('javascript')) {
		typeSelect.value = 'javascript';
	}
});
//# sourceMappingURL=code-snippet.js.map
