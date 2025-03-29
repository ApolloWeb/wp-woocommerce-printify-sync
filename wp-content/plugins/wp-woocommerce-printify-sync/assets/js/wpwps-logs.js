document.addEventListener('DOMContentLoaded', function() {
    const logContent = document.getElementById('logContent');
    if (!logContent) return;

    // Search functionality
    let currentMatchIndex = -1;
    let matches = [];

    const logSearch = document.getElementById('logSearch');
    const prevMatch = document.getElementById('prevMatch');
    const nextMatch = document.getElementById('nextMatch');

    function performSearch() {
        clearHighlights();
        const searchText = logSearch.value.trim();
        if (!searchText) return;

        const content = logContent.textContent;
        const regex = new RegExp(escapeRegExp(searchText), 'gi');
        matches = [];
        let match;

        while ((match = regex.exec(content)) !== null) {
            matches.push(match.index);
        }

        if (matches.length > 0) {
            currentMatchIndex = 0;
            highlightMatch();
        }
    }

    function highlightMatch() {
        if (matches.length === 0 || currentMatchIndex === -1) return;

        const content = logContent.textContent;
        const matchIndex = matches[currentMatchIndex];
        const searchText = logSearch.value.trim();
        
        let html = content.substring(0, matchIndex);
        html += '<mark class="current-match">' + content.substring(matchIndex, matchIndex + searchText.length) + '</mark>';
        html += content.substring(matchIndex + searchText.length);

        logContent.innerHTML = html;
        const mark = logContent.querySelector('.current-match');
        mark.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearHighlights() {
        logContent.innerHTML = logContent.textContent;
        currentMatchIndex = -1;
        matches = [];
    }

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    if (logSearch) {
        logSearch.addEventListener('input', performSearch);
        prevMatch?.addEventListener('click', () => {
            if (matches.length === 0) return;
            currentMatchIndex = (currentMatchIndex - 1 + matches.length) % matches.length;
            highlightMatch();
        });
        nextMatch?.addEventListener('click', () => {
            if (matches.length === 0) return;
            currentMatchIndex = (currentMatchIndex + 1) % matches.length;
            highlightMatch();
        });
    }

    // Toggle controls
    const toggleWrap = document.getElementById('toggleWrap');
    if (toggleWrap) {
        toggleWrap.addEventListener('click', () => {
            logContent.classList.toggle('wrap-text');
        });
    }

    const toggleTimestamps = document.getElementById('toggleTimestamps');
    if (toggleTimestamps) {
        toggleTimestamps.addEventListener('click', () => {
            logContent.classList.toggle('hide-timestamps');
        });
    }

    const clearHighlightsBtn = document.getElementById('clearHighlights');
    if (clearHighlightsBtn) {
        clearHighlightsBtn.addEventListener('click', clearHighlights);
    }

    // Log entry colorization
    const content = logContent.innerHTML;
    const colorizedContent = content
        .replace(/\[(.*?)\]/g, '<span class="log-timestamp">[$1]</span>')
        .replace(/ERROR:/g, '<span class="log-error">ERROR:</span>')
        .replace(/WARNING:/g, '<span class="log-warning">WARNING:</span>')
        .replace(/INFO:/g, '<span class="log-info">INFO:</span>')
        .replace(/DEBUG:/g, '<span class="log-debug">DEBUG:</span>');
    
    logContent.innerHTML = colorizedContent;
});