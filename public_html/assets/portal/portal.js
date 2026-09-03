/*
 * codesaur.net portal - Turbo C IDE theme behaviours
 * ---------------------------------------------------------------
 *  - mobile menu toggle for the top menu bar
 *  - highlight.js initialisation (language alias mapping)
 *  - copy button on code blocks
 *  - F1 keyboard shortcut -> documentation (like Turbo C help)
 *  - active menu item detection by URL prefix
 */
(function () {
    'use strict';

    var COPY_LABEL = document.documentElement.getAttribute('data-copy-label') || 'Copy';
    var COPIED_LABEL = document.documentElement.getAttribute('data-copied-label') || 'Copied';

    function initMenuToggle() {
        var bar = document.querySelector('.tc-menubar');
        var toggle = document.querySelector('.tc-menu-toggle');
        if (!bar || !toggle) {
            return;
        }
        toggle.addEventListener('click', function () {
            bar.classList.toggle('open');
        });
    }

    function initActiveMenu() {
        var path = window.location.pathname.replace(/\/+$/, '');
        var links = document.querySelectorAll('.tc-menu a[data-prefix]');
        var best = null;
        var bestLen = 0;
        links.forEach(function (a) {
            var prefix = a.getAttribute('data-prefix').replace(/\/+$/, '');
            if (prefix === '') {
                return;
            }
            if ((path === prefix || path.indexOf(prefix + '/') === 0) && prefix.length > bestLen) {
                best = a;
                bestLen = prefix.length;
            }
        });
        if (best) {
            best.classList.add('active');
        } else {
            var home = document.querySelector('.tc-menu a[data-home]');
            if (home && path === home.getAttribute('data-home').replace(/\/+$/, '')) {
                home.classList.add('active');
            }
        }
    }

    function initHighlight() {
        if (typeof window.hljs === 'undefined') {
            return;
        }
        var alias = { env: 'ini', dotenv: 'ini', http: 'plaintext', text: 'plaintext', txt: 'plaintext', htaccess: 'apache', apacheconf: 'apache', sh: 'bash', shell: 'bash', twig: 'xml', html: 'xml', nginx: 'nginx' };
        document.querySelectorAll('pre code').forEach(function (block) {
            var cls = block.className.match(/language-([\w.+#-]+)/);
            if (cls) {
                var lang = cls[1].toLowerCase();
                if (alias[lang]) {
                    lang = alias[lang];
                }
                if (!window.hljs.getLanguage(lang)) {
                    lang = 'plaintext';
                }
                block.className = 'language-' + lang;
            } else {
                block.className = 'language-plaintext';
            }
            window.hljs.highlightElement(block);
        });
    }

    function initCopyButtons() {
        if (!navigator.clipboard) {
            return;
        }
        document.querySelectorAll('.tc-dialog pre, .tc-window pre.tc-copyable').forEach(function (pre) {
            var wrap = document.createElement('div');
            wrap.className = 'tc-pre-wrap';
            pre.parentNode.insertBefore(wrap, pre);
            wrap.appendChild(pre);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tc-copy';
            btn.textContent = COPY_LABEL;
            btn.addEventListener('click', function () {
                navigator.clipboard.writeText(pre.innerText).then(function () {
                    btn.textContent = COPIED_LABEL;
                    setTimeout(function () {
                        btn.textContent = COPY_LABEL;
                    }, 1500);
                });
            });
            wrap.appendChild(btn);
        });
    }

    function initHotkeys() {
        var docs = document.querySelector('a[data-hotkey="F1"]');
        document.addEventListener('keydown', function (e) {
            if (e.key === 'F1' && docs) {
                e.preventDefault();
                window.location.href = docs.getAttribute('href');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMenuToggle();
        initActiveMenu();
        initHighlight();
        initCopyButtons();
        initHotkeys();
    });
})();
