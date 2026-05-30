// --- Per-page bindings -------------------------------------------------
// Anything that wires up freshly-rendered content goes in here so it can
// be re-run after a sidebar SPA swap (which replaces .content in place).
function initContentArea(root) {
    if (!root) return;

    // Phone input mask
    root.querySelectorAll('[data-phone-mask]').forEach((input) => {
        if (input.dataset.phoneMaskInited === '1') return;
        input.dataset.phoneMaskInited = '1';
        input.addEventListener('input', () => {
            const digits = input.value.replace(/\D/g, '').slice(0, 10);
            const area = digits.slice(0, 3);
            const prefix = digits.slice(3, 6);
            const line = digits.slice(6, 10);

            if (digits.length > 6) {
                input.value = `(${area}) ${prefix}-${line}`;
            } else if (digits.length > 3) {
                input.value = `(${area}) ${prefix}`;
            } else if (digits.length > 0) {
                input.value = `(${area}`;
            } else {
                input.value = '';
            }
        });
    });

    // Status badge auto-color: stamp a normalized data-status attribute
    // so the stylesheet can color-code raw status text.
    root.querySelectorAll('.status-badge').forEach((badge) => {
        if (badge.hasAttribute('data-status')) return;
        const raw = (badge.textContent || '').trim().toLowerCase();
        if (!raw) return;
        const slug = raw
            .replace(/[_\s]+/g, '-')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/^-+|-+$/g, '');
        if (slug) badge.setAttribute('data-status', slug);
    });

    // Disabled link guards
    root.querySelectorAll('a[aria-disabled="true"]').forEach((link) => {
        if (link.dataset.disabledGuard === '1') return;
        link.dataset.disabledGuard = '1';
        link.addEventListener('click', (event) => event.preventDefault());
    });
}

initContentArea(document);

// --- Topbar search keyboard shortcut -----------------------------------
// Ctrl/Cmd+K focuses the global search input in the topbar.
document.addEventListener('keydown', (event) => {
    const isShortcut = (event.ctrlKey || event.metaKey) && event.key && event.key.toLowerCase() === 'k';
    if (!isShortcut) return;
    const search = document.querySelector('.topbar-search input[type="search"]');
    if (!search) return;
    event.preventDefault();
    search.focus();
    search.select();
});

// --- Mobile sidebar toggle ---------------------------------------------
// On small screens the sidebar is off-canvas. The hamburger button in the
// topbar toggles `.is-sidebar-open` on the app shell. Tapping the backdrop
// or any nav link closes it again.
(function setupSidebarToggle() {
    const shell = document.querySelector('.app-shell');
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    if (!shell || !toggle || !sidebar) return;

    const setOpen = (open) => {
        shell.classList.toggle('is-sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(!shell.classList.contains('is-sidebar-open'));
    });

    // Close when a nav link is followed.
    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    // Close on Escape.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && shell.classList.contains('is-sidebar-open')) {
            setOpen(false);
            toggle.focus();
        }
    });

    // Close when the backdrop (the ::after on the shell) is tapped.
    shell.addEventListener('click', (event) => {
        if (!shell.classList.contains('is-sidebar-open')) return;
        if (sidebar.contains(event.target) || toggle.contains(event.target)) return;
        setOpen(false);
    });
})();

// --- Chrome SPA navigation ---------------------------------------------
// Intercept clicks on links inside the persistent app chrome (sidebar and
// topbar) so those regions are never re-rendered. We fetch the target page,
// swap only .content, and update the topbar title and active-nav state. The
// sidebar's scroll position and any DOM state on it are preserved. Falls
// back to full navigation on errors, redirects to a different URL, or
// responses without the expected app-shell markup.
(function setupChromeSpa() {
    const shell = document.querySelector('.app-shell');
    const sidebar = document.getElementById('sidebar');
    const topbar = document.querySelector('.topbar');
    const main = document.querySelector('.main');
    if (!shell || !sidebar || !topbar || !main || !main.querySelector('.content')) return;
    if (!window.history || !window.history.pushState) return;

    // Apply a fetched response to the page: swap .content, update title /
    // topbar / active-nav, and push or replace history. `history` is one of
    // 'push' | 'replace' | 'none'. Returns true on success, false if the
    // caller should fall back to a hard navigation.
    const applyResponse = async (res, requestedPathname, { history: historyMode = 'push', resetScroll = true } = {}) => {
        const finalUrl = new URL(res.url, location.href);
        // If the server redirected us somewhere materially different
        // (e.g. session expired -> /login), let the browser handle it
        // so the proper layout loads.
        if (res.redirected && finalUrl.pathname !== requestedPathname) {
            window.location.href = finalUrl.href;
            return true; // navigation handed off; no fallback needed
        }
        if (!res.ok && res.status >= 500) {
            return false;
        }
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newContent = doc.querySelector('.main .content');
        if (!newContent) {
            return false;
        }

        const currentContent = main.querySelector('.content');
        currentContent.replaceWith(newContent);

        const newTitle = doc.querySelector('title');
        if (newTitle) document.title = newTitle.textContent || document.title;

        const newH1 = doc.querySelector('.topbar-title h1');
        const currentH1 = document.querySelector('.topbar-title h1');
        if (newH1 && currentH1) currentH1.textContent = newH1.textContent;

        const newActiveHrefs = new Set();
        doc.querySelectorAll('.sidebar .nav-list a.active').forEach((a) => {
            newActiveHrefs.add(a.getAttribute('href'));
        });
        sidebar.querySelectorAll('.nav-list a').forEach((a) => {
            a.classList.toggle('active', newActiveHrefs.has(a.getAttribute('href')));
        });

        // The sandbox banner lives in the chrome, not in .content, so a content
        // swap won't update it on its own. Flipping the Square environment from
        // the settings page must add/remove it, so reconcile it from the fetched
        // document here.
        const newShell = doc.querySelector('.app-shell');
        if (newShell) {
            const wantBanner = newShell.classList.contains('has-env-banner');
            shell.classList.toggle('has-env-banner', wantBanner);
            const currentBanner = shell.querySelector(':scope > .env-banner');
            if (wantBanner && !currentBanner) {
                const newBanner = doc.querySelector('.app-shell > .env-banner');
                if (newBanner) shell.insertBefore(newBanner, shell.firstChild);
            } else if (!wantBanner && currentBanner) {
                currentBanner.remove();
            }
        }

        initContentArea(main.querySelector('.content'));

        if (historyMode === 'push') {
            history.pushState({ spa: true }, '', finalUrl.href);
        } else if (historyMode === 'replace') {
            history.replaceState({ spa: true }, '', finalUrl.href);
        }
        if (resetScroll) {
            // Reset main scroll only — sidebar's scroll is left alone.
            window.scrollTo(0, 0);
            main.scrollTop = 0;
        }
        return true;
    };

    const swapTo = async (urlString, { push = true } = {}) => {
        const targetUrl = new URL(urlString, location.href);
        try {
            const res = await fetch(targetUrl.href, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch', 'Accept': 'text/html' },
            });
            const ok = await applyResponse(res, targetUrl.pathname, { history: push ? 'push' : 'replace' });
            if (!ok) window.location.href = targetUrl.href;
        } catch (err) {
            window.location.href = targetUrl.href;
        }
    };

    // Submit a content-area form (e.g. an individual setting's Save button)
    // without re-rendering the chrome. The POST handler uses Post/Redirect/Get,
    // so fetch follows the 302 back to the GET page and we swap that .content
    // in place. On a validation error the handler returns the page directly
    // (200, same URL) and we swap that instead. We keep scroll position so the
    // saved field stays put, and use replaceState so the back button doesn't
    // step through every save.
    const submitForm = async (form, submitter) => {
        const action = new URL(form.getAttribute('action') || location.href, location.href);
        const method = (form.getAttribute('method') || 'post').toUpperCase();
        const formData = new FormData(form);
        if (submitter && submitter.name) {
            formData.append(submitter.name, submitter.value);
        }
        try {
            const res = await fetch(action.href, {
                method,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch', 'Accept': 'text/html' },
                body: formData,
            });
            const ok = await applyResponse(res, action.pathname, { history: 'replace', resetScroll: false });
            if (!ok) form.submit();
        } catch (err) {
            form.submit();
        }
    };

    shell.addEventListener('click', (event) => {
        if (event.defaultPrevented) return;
        if (event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a');
        if (!link) return;
        // Only intercept links inside the persistent chrome (sidebar / topbar).
        // Content-area links keep their default full-page behavior.
        if (!sidebar.contains(link) && !topbar.contains(link)) return;
        if (link.hasAttribute('download')) return;
        if (link.getAttribute('aria-disabled') === 'true') return;
        const target = link.getAttribute('target');
        if (target && target !== '' && target !== '_self') return;

        const rawHref = link.getAttribute('href');
        if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) return;

        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin) return;

        event.preventDefault();
        swapTo(url.href);
    });

    // Intercept submits of content-area SPA forms (per-field setting saves).
    shell.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const form = event.target.closest('form.spa-form');
        if (!form) return;
        // Only forms living in the swappable content area, never the chrome.
        if (!main.contains(form)) return;

        const action = new URL(form.getAttribute('action') || location.href, location.href);
        if (action.origin !== location.origin) return;

        event.preventDefault();
        submitForm(form, event.submitter);
    });

    window.addEventListener('popstate', (event) => {
        if (!event.state || !event.state.spa) return;
        swapTo(location.href, { push: false });
    });

    // Mark the initial entry so back/forward into it triggers a swap.
    history.replaceState({ spa: true }, '', location.href);
})();
