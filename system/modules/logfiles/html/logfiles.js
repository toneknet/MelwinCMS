(function () {
    function initialize() {
        document.querySelectorAll('.logfiles').forEach(function (viewer) {
            var search = viewer.querySelector('.logfiles-search');
            if (!search) return;
            viewer.querySelector('.logfiles-refresh').addEventListener('click', function () { window.location.reload(); });
            if (!viewer.querySelector('.logfiles-count')) return;
            var entries = Array.from(viewer.querySelectorAll('.logfiles-entry'));
            var texts = entries.map(function (entry) { return entry.textContent.toLocaleLowerCase('sv'); });
            var selectedLevel = 'all';
            var filters = Array.from(viewer.querySelectorAll('[data-level-filter]'));
            var totals = { all: entries.length, critical: 0, error: 0, warning: 0, other: 0 };
            entries.forEach(function (entry) { totals[entry.dataset.level || 'other']++; });
            var levels = viewer.querySelector('.logfiles-levels');
            if (levels) levels.hidden = !(totals.critical + totals.error + totals.warning);
            filters.forEach(function (button) {
                var level = button.dataset.levelFilter;
                button.querySelector('.logfiles-level-count').textContent = '(' + totals[level] + ')';
                button.addEventListener('click', function () {
                    selectedLevel = level;
                    filters.forEach(function (other) {
                        other.setAttribute('aria-pressed', String(other === button));
                    });
                    applyFilters();
                });
            });
            function applyFilters() {
                var query = search.value.trim().toLocaleLowerCase('sv');
                var visible = 0;
                entries.forEach(function (entry, index) {
                    entry.hidden = texts[index].indexOf(query) === -1 || (selectedLevel !== 'all' && entry.dataset.level !== selectedLevel);
                    if (!entry.hidden) visible++;
                });
                viewer.querySelector('.logfiles-count').textContent = visible + ' av ' + entries.length + ' inlägg';
                viewer.querySelector('.logfiles-empty').hidden = visible !== 0 || entries.length === 0;
            }
            search.addEventListener('input', applyFilters);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
}());
