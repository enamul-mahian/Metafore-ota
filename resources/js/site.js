/*
 * Eagle Global Hub public website interactions.
 * Works with the markup in layouts/site.blade.php and the site page views.
 */
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

/* ---------- Conversion tracking ----------
 * One call sends an event to every connected platform (GA4 / Google Ads via gtag,
 * GTM dataLayer, Meta Pixel, TikTok). Nothing happens when none is connected.
 */
const META_EVENTS = {
    search: 'Search',
    view_item: 'ViewContent',
    begin_checkout: 'InitiateCheckout',
    add_payment_info: 'AddPaymentInfo',
    purchase: 'Purchase',
    lead: 'Lead',
    sign_up: 'CompleteRegistration',
    subscribe: 'Subscribe',
    contact: 'Contact',
};

export function track(name, params = {}) {
    try { if (typeof window.gtag === 'function') window.gtag('event', name, params); } catch (e) { /* ignore */ }
    try { if (Array.isArray(window.dataLayer)) window.dataLayer.push({ event: name, ...params }); } catch (e) { /* ignore */ }
    try {
        if (typeof window.fbq === 'function') {
            META_EVENTS[name] ? window.fbq('track', META_EVENTS[name], params) : window.fbq('trackCustom', name, params);
        }
    } catch (e) { /* ignore */ }
    try { if (window.ttq && typeof window.ttq.track === 'function') window.ttq.track(META_EVENTS[name] || name, params); } catch (e) { /* ignore */ }
}

let toastTimer;
export function toast(message) {
    const el = $('#toast');
    if (!el) return;
    el.textContent = message;
    el.classList.add('is-show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('is-show'), 3000);
}

window.EGH = Object.assign(window.EGH || {}, { track, toast });

function initHeader() {
    const header = $('#header');
    const totop = $('#totop');
    const onScroll = () => {
        const y = window.scrollY;
        header?.classList.toggle('is-scrolled', y > 10);
        totop?.classList.toggle('is-show', y > 600);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    totop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    const drawer = $('#drawer');
    const burger = $('#burger');
    const nav = $('#nav');
    if (!drawer || !burger) return;
    if (nav) $('#drawerNav').innerHTML = nav.innerHTML;

    const open = () => {
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        burger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        $('#drawerClose')?.focus();
    };
    const close = () => {
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        burger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };
    burger.addEventListener('click', open);
    $('#drawerClose')?.addEventListener('click', close);
    drawer.addEventListener('click', (e) => { if (e.target === drawer || e.target.closest('a')) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && drawer.classList.contains('is-open')) close(); });
}

/* [data-tabs] container with [data-tab-btn="x"] buttons and [data-tab-panel="x"] panels */
function initTabs() {
    $$('[data-tabs]').forEach((box) => {
        box.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-tab-btn]');
            if (!btn || !box.contains(btn)) return;
            const key = btn.getAttribute('data-tab-btn');
            $$('[data-tab-btn]', box).forEach((b) => {
                const on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            $$('[data-tab-panel]', box).forEach((p) => { p.hidden = p.getAttribute('data-tab-panel') !== key; });
        });
    });
}

/* [data-toggle="id"] toggles .is-open on #id (filters panel, admin menu...) */
function initToggles() {
    $$('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.getAttribute('data-toggle'));
            if (!target) return;
            const isOpen = target.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });
}

/* [data-scroll="id"][data-dir="1|-1"] scrolls a horizontal carousel */
function initCarousels() {
    $$('[data-scroll]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const el = document.getElementById(btn.dataset.scroll);
            if (!el) return;
            const card = el.firstElementChild;
            const step = card ? card.getBoundingClientRect().width + 14 : 220;
            const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
            if (Number(btn.dataset.dir) > 0 && atEnd) el.scrollTo({ left: 0, behavior: 'smooth' });
            else el.scrollBy({ left: step * Number(btn.dataset.dir), behavior: 'smooth' });
        });
    });
}

/* Tracking hooks: data-track on links/buttons fires on click, on forms fires on submit */
function initTrackingHooks() {
    document.addEventListener('click', (e) => {
        const el = e.target.closest('a[data-track], button[data-track]:not([type="submit"])');
        if (el) track(el.dataset.track, { label: (el.textContent || '').trim().slice(0, 80) });
    });
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form instanceof HTMLFormElement && form.dataset.track) {
            track(form.dataset.track, { form: form.getAttribute('aria-label') || form.id || 'form' });
        }
    });
    // Server-side confirmation pages can ask for an event: <div data-track-on-load="lead" data-track-params='{"value":1}'>
    $$('[data-track-on-load]').forEach((el) => {
        let params = {};
        try { params = JSON.parse(el.dataset.trackParams || '{}'); } catch (e) { params = {}; }
        track(el.dataset.trackOnLoad, params);
    });
}

/* Copy buttons: <button data-copy="TEXT"> */
function initCopy() {
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-copy]');
        if (!btn) return;
        try {
            await navigator.clipboard.writeText(btn.dataset.copy);
            toast(`Copied ${btn.dataset.copy}`);
        } catch (err) {
            toast(btn.dataset.copy);
        }
    });
}

/* File inputs inside .upload show the chosen file name */
function initUploads() {
    document.addEventListener('change', (e) => {
        const input = e.target;
        if (!(input instanceof HTMLInputElement) || input.type !== 'file') return;
        const label = input.closest('.upload')?.querySelector('[data-file-name]');
        if (label) label.textContent = input.files && input.files.length ? Array.from(input.files).map((f) => f.name).join(', ') : label.dataset.empty || '';
    });
}

/* Homepage search widget: tabs switch which search form is shown */
function initSearchWidget() {
    const widget = $('[data-search-widget]');
    if (!widget) return;
    const select = (key) => {
        $$('[data-search-tab]', widget).forEach((b) => {
            const on = b.dataset.searchTab === key;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        $$('[data-search-panel]', widget).forEach((p) => { p.hidden = p.dataset.searchPanel !== key; });
    };
    widget.addEventListener('click', (e) => {
        const tab = e.target.closest('[data-search-tab]');
        if (tab) select(tab.dataset.searchTab);
        const swap = e.target.closest('[data-swap]');
        if (swap) {
            const form = swap.closest('form');
            const [a, b] = swap.dataset.swap.split(',').map((n) => form?.elements.namedItem(n));
            if (a && b) [a.value, b.value] = [b.value, a.value];
        }
    });
    const wanted = new URLSearchParams(window.location.search).get('tab');
    if (wanted && $(`[data-search-tab="${CSS.escape(wanted)}"]`, widget)) select(wanted);

    // Return date only matters for round trips
    $$('[data-trip-toggle]', widget).forEach((form) => {
        const sync = () => {
            const trip = form.querySelector('input[name="trip_type"]:checked')?.value;
            const ret = form.querySelector('[data-return-field]');
            if (!ret) return;
            const input = ret.querySelector('input');
            const oneWay = trip === 'one_way';
            ret.classList.toggle('is-disabled', oneWay);
            if (input) input.disabled = oneWay;
        };
        form.addEventListener('change', sync);
        sync();
    });
}

function initCounters() {
    const counters = $$('[data-count-to]');
    if (!counters.length || !('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const run = (el) => {
        const end = Number(el.dataset.countTo);
        const suffix = el.dataset.countSuffix || '';
        const start = performance.now();
        const step = (t) => {
            const p = Math.min(1, (t - start) / 1200);
            el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))).toLocaleString('en-US') + suffix;
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };
    const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => { if (en.isIntersecting) { run(en.target); io.unobserve(en.target); } });
    }, { threshold: 0.3 });
    counters.forEach((el) => io.observe(el));
}

function init() {
    initHeader();
    initTabs();
    initToggles();
    initCarousels();
    initTrackingHooks();
    initCopy();
    initUploads();
    initSearchWidget();
    initCounters();
}

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
