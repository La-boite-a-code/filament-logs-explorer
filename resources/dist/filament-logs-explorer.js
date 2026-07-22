/**
 * Filament Logs Explorer - log viewer Alpine component.
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
        // Every matching line, in document order. Drives the match counter and
        // the previous / next navigation.
        matches: [],
        currentMatch: -1,

        // Cache of { el, text, marked } for every rendered line.
        lines: [],
        // Matching lines, which carry the row tint and must have it removed.
        matched: [],
        // Lines whose markup was replaced to wrap the query in <mark>, so we
        // know which ones need their original text restored.
        marked: [],
        // The line carrying the "current match" styling, kept around so we can
        // clear it without walking every match.
        currentLine: null,

        /**
         * Marking a line replaces its markup, which means parsing HTML for it.
         * That does not scale to the tens of thousands of lines a large file can
         * produce: a broad query would otherwise rewrite the whole viewer on
         * every keystroke. Past this many matches, lines are still tinted and
         * still navigable, and their marks are applied on demand as soon as they
         * become the current match.
         */
        maxMarkedLines: 500,

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
                marked: false,
            }));
        },

        runSearch() {
            this.resetHighlights();

            const query = this.query.trim();
            const needle = query.toLowerCase();

            if (needle === '') {
                return;
            }

            for (const line of this.lines) {
                if (! line.text.toLowerCase().includes(needle)) {
                    continue;
                }

                line.el.classList.add('lge-line--match');
                this.matched.push(line);
                this.matches.push(line);

                if (this.marked.length < this.maxMarkedLines) {
                    this.markLine(line, query);
                }
            }

            if (this.matches.length) {
                this.currentMatch = 0;
                this.focusCurrentMatch();
            }
        },

        markLine(line, query) {
            if (line.marked) {
                return;
            }

            line.el.innerHTML = this.highlight(line.text, query);
            line.marked = true;
            this.marked.push(line);
        },

        resetHighlights() {
            for (const line of this.marked) {
                line.el.textContent = line.text;
                line.marked = false;
            }

            for (const line of this.matched) {
                line.el.classList.remove('lge-line--match', 'lge-line--current');
            }

            this.marked = [];
            this.matched = [];
            this.matches = [];
            this.currentMatch = -1;
            this.currentLine = null;
        },

        /**
         * Wrap every occurrence of the query in a <mark>, escaping the text one
         * segment at a time.
         *
         * Escaping the whole line first and then searching the escaped string
         * would let a query such as "39", "quot" or "amp" match *inside* an HTML
         * entity and split it, so a line containing an apostrophe would render
         * as "Can&#39;t" instead of "Can't".
         */
        highlight(text, query) {
            const needle = query.toLowerCase();

            if (needle === '') {
                return this.escapeHtml(text);
            }

            const haystack = text.toLowerCase();
            let out = '';
            let index = 0;

            for (;;) {
                const found = haystack.indexOf(needle, index);

                if (found === -1) {
                    break;
                }

                out += this.escapeHtml(text.slice(index, found));
                out += `<mark class="lge-mark">${this.escapeHtml(text.slice(found, found + needle.length))}</mark>`;

                index = found + needle.length;
            }

            return out + this.escapeHtml(text.slice(index));
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
            this.currentLine?.el.classList.remove('lge-line--current');

            const line = this.matches[this.currentMatch];

            if (! line) {
                this.currentLine = null;

                return;
            }

            // Past maxMarkedLines the line was left untouched, so mark it now
            // that the user has actually navigated to it.
            this.markLine(line, this.query.trim());

            line.el.classList.add('lge-line--current');
            line.el.scrollIntoView({ block: 'center', behavior: 'smooth' });

            this.currentLine = line;
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
