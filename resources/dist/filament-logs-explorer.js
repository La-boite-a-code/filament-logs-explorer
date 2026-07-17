/**
 * Filament Logs Explorer — log viewer Alpine component.
 *
 * Handles client-side searching (with safe highlighting), match navigation and
 * scrolling within a single, already-rendered log file. Moving between files is
 * handled server-side by Livewire; this component is re-created every time the
 * file changes (its root element is keyed by the file id), so its state always
 * matches the file on screen.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('logsExplorerViewer', () => ({
        query: '',
        matches: [],
        currentMatch: -1,

        // Cache of { el, text } for every rendered line.
        lines: [],
        // Currently highlighted line objects, so we only ever reset those.
        highlighted: [],

        init() {
            this.cacheLines();
            this.$watch('query', () => this.runSearch());
        },

        cacheLines() {
            const viewer = this.$refs.viewer;

            if (! viewer) {
                this.lines = [];

                return;
            }

            this.lines = Array.from(viewer.querySelectorAll('[data-log-line]')).map((el) => ({
                el,
                text: el.textContent,
            }));
        },

        runSearch() {
            this.resetHighlights();

            this.matches = [];
            this.currentMatch = -1;

            const needle = this.query.trim().toLowerCase();

            if (needle === '') {
                return;
            }

            for (const line of this.lines) {
                if (line.text.toLowerCase().includes(needle)) {
                    line.el.innerHTML = this.highlight(line.text, this.query.trim());
                    line.el.classList.add('lge-line--match');
                    this.highlighted.push(line);
                    this.matches.push(line.el);
                }
            }

            if (this.matches.length) {
                this.currentMatch = 0;
                this.focusCurrentMatch();
            }
        },

        resetHighlights() {
            for (const line of this.highlighted) {
                line.el.textContent = line.text;
                line.el.classList.remove('lge-line--match', 'lge-line--current');
            }

            this.highlighted = [];
        },

        highlight(text, query) {
            const escaped = this.escapeHtml(text);
            const pattern = new RegExp(this.escapeRegExp(this.escapeHtml(query)), 'gi');

            return escaped.replace(pattern, (match) => `<mark class="lge-mark">${match}</mark>`);
        },

        escapeHtml(value) {
            return value.replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char]);
        },

        escapeRegExp(value) {
            return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },

        nextMatch() {
            if (! this.matches.length) {
                return;
            }

            this.currentMatch = (this.currentMatch + 1) % this.matches.length;
            this.focusCurrentMatch();
        },

        previousMatch() {
            if (! this.matches.length) {
                return;
            }

            this.currentMatch = (this.currentMatch - 1 + this.matches.length) % this.matches.length;
            this.focusCurrentMatch();
        },

        focusCurrentMatch() {
            this.matches.forEach((el) => el.classList.remove('lge-line--current'));

            const el = this.matches[this.currentMatch];

            if (! el) {
                return;
            }

            el.classList.add('lge-line--current');
            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        },

        matchLabel() {
            if (! this.matches.length) {
                return '';
            }

            return `${this.currentMatch + 1} / ${this.matches.length}`;
        },

        scrollToTop() {
            this.$refs.viewer?.scrollTo({ top: 0, behavior: 'smooth' });
        },

        scrollToBottom() {
            const viewer = this.$refs.viewer;

            viewer?.scrollTo({ top: viewer.scrollHeight, behavior: 'smooth' });
        },

        clear() {
            this.query = '';
            this.$refs.search?.blur();
        },

        focusSearch() {
            this.$refs.search?.focus();
        },

        onKeydown(event) {
            const inSearch = event.target === this.$refs.search;

            if (event.key === '/' && ! inSearch) {
                event.preventDefault();
                this.focusSearch();

                return;
            }

            if (inSearch) {
                return;
            }

            switch (event.key) {
                case 'n':
                    event.preventDefault();
                    this.nextMatch();
                    break;
                case 'N':
                    event.preventDefault();
                    this.previousMatch();
                    break;
                case 'g':
                    event.preventDefault();
                    this.scrollToTop();
                    break;
                case 'G':
                    event.preventDefault();
                    this.scrollToBottom();
                    break;
            }
        },
    }));
});
