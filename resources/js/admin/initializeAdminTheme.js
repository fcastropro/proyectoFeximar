/**
 * Loads Eres admin vendor scripts once and exposes initializeAdminTheme()
 * for Vue/Inertia remounts without duplicating listeners.
 */

const SCRIPT_FLAG = 'data-feximar-eres-script';
const STYLE_FLAG = 'data-feximar-eres-style';

const ERES_STYLES = [
    '/admin-assets/vendor/metismenu/css/metisMenu.min.css',
];

const ERES_SCRIPTS = [
    '/admin-assets/vendor/jquery/jquery.min.js',
    '/admin-assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
    '/admin-assets/vendor/metismenu/js/metisMenu.min.js',
    '/admin-assets/js/settings.js',
    '/admin-assets/js/deznav-init.js',
    // custom.js intentionally omitted: its window.load runs $("select").selectpicker()
    // without bootstrap-select JS and would break native Vue selects. Toggle/MetisMenu
    // behavior from custom.js is recreated below using the same Eres class contract.
];

let assetsPromise = null;
let navBound = false;
let resizeBound = false;

function loadStyle(href) {
    if (document.querySelector(`link[${STYLE_FLAG}="1"][href="${href}"]`)) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.setAttribute(STYLE_FLAG, '1');
        link.onload = () => resolve();
        link.onerror = () => reject(new Error(`Failed to load style: ${href}`));
        document.head.appendChild(link);
    });
}

function loadScript(src) {
    if (document.querySelector(`script[${SCRIPT_FLAG}="1"][src="${src}"]`)) {
        return Promise.resolve();
    }

    // Also skip if the same src was included by other means
    if (document.querySelector(`script[src="${src}"]`)) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = false;
        script.setAttribute(SCRIPT_FLAG, '1');
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.body.appendChild(script);
    });
}

export function loadEresAdminAssets() {
    if (!assetsPromise) {
        assetsPromise = (async () => {
            for (const href of ERES_STYLES) {
                await loadStyle(href);
            }

            for (const src of ERES_SCRIPTS) {
                await loadScript(src);
            }
        })().catch((error) => {
            assetsPromise = null;
            throw error;
        });
    }

    return assetsPromise;
}

function applyDezSettings() {
    const $ = window.jQuery;
    if (!$ || typeof window.dezSettings !== 'function') {
        return;
    }

    if (!window.dezSettingsOptions) {
        window.dezSettingsOptions = {
            typography: 'poppins',
            version: 'light',
            layout: 'vertical',
            primary: 'color_1',
            headerBg: 'color_1',
            navheaderBg: 'color_1',
            sidebarBg: 'color_1',
            sidebarStyle: 'full',
            sidebarPosition: 'fixed',
            headerPosition: 'fixed',
            containerLayout: 'full',
            direction: 'ltr',
            navTextColor: 'color_1',
            navigationBarImg: '',
        };
    }

    // Refresh data-* attributes and responsive sidebar style (full / mini / overlay)
    // eslint-disable-next-line no-new
    new window.dezSettings(window.dezSettingsOptions);
}

function bindNavToggle() {
    const $ = window.jQuery;
    if (!$ || navBound) {
        return;
    }

    // Document-delegated so it survives AdminLayout remounts under Inertia
    $(document).on('click.feximarAdmin', '.nav-control', function onNavControlClick() {
        const $body = $('body');
        const $wrapper = $('#main-wrapper');

        $wrapper.toggleClass('menu-toggle');
        $('.hamburger').toggleClass('is-active');

        if ($body.attr('data-sidebar-style') === 'full' && $body.attr('data-layout') === 'vertical') {
            if ($wrapper.hasClass('menu-toggle')) {
                $body.attr('data-sidebar-position', 'static');
            } else {
                $body.attr('data-sidebar-position', 'fixed');
            }
        }
    });

    // Close overlay sidebar after navigating via a leaf link on mobile
    $(document).on('click.feximarAdmin', '#menu a', function onMenuLinkClick(event) {
        const $link = $(event.currentTarget);
        if ($link.hasClass('has-arrow')) {
            return;
        }

        if ($(window).width() < 768 && $('#main-wrapper').hasClass('menu-toggle')) {
            $('#main-wrapper').removeClass('menu-toggle');
            $('.hamburger').removeClass('is-active');
        }
    });

    navBound = true;
}

function bindResponsiveResize() {
    const $ = window.jQuery;
    if (!$ || resizeBound) {
        return;
    }

    $(window).on('resize.feximarAdmin', function onAdminResize() {
        applyDezSettings();
    });

    resizeBound = true;
}

function initMetisMenu() {
    const $ = window.jQuery;
    if (!$ || !$.fn || typeof $.fn.metisMenu !== 'function') {
        return;
    }

    const $menu = $('#menu');
    if (!$menu.length) {
        return;
    }

    if ($menu.data('metisMenu')) {
        try {
            $menu.metisMenu('dispose');
        } catch (error) {
            // Fallback if dispose is unavailable in an unexpected build.
        }
    }

    $menu.metisMenu();
}

function markActiveMenuItems() {
    const $ = window.jQuery;
    if (!$ || !$('#menu').length) {
        return;
    }

    $('#menu a').removeClass('mm-active');
    $('#menu li').removeClass('mm-active mm-show active-no-child');

    const locationHref = window.location.href;

    let $active = $('#menu a')
        .filter(function filterActiveLink() {
            return this.href === locationHref;
        })
        .addClass('mm-active')
        .parent()
        .addClass('mm-active');

    // Mirror custom.js handlePageActive / metisMenuSidebar walk
    while ($active.length && $active.is('li')) {
        $active = $active
            .parent()
            .addClass('mm-show')
            .parent()
            .addClass('mm-active');
    }

    $('#menu > .mm-active').each(function markNoChild() {
        if (!$(this).children('ul').length) {
            $(this).addClass('active-no-child');
        }
    });
}

/**
 * Safe admin chrome init for first paint and every Inertia visit.
 */
export async function initializeAdminTheme() {
    if (!document.getElementById('main-wrapper')) {
        return;
    }

    await loadEresAdminAssets();

    const $ = window.jQuery;
    if (!$) {
        console.error('[FEXIMAR] jQuery failed to load for admin theme');
        return;
    }

    applyDezSettings();
    bindNavToggle();
    bindResponsiveResize();
    initMetisMenu();
    markActiveMenuItems();

    $('#main-wrapper').addClass('show');
}

export function teardownAdminThemeListeners() {
    const $ = window.jQuery;
    if (!$) {
        return;
    }

    $(document).off('.feximarAdmin');
    $(window).off('.feximarAdmin');
    navBound = false;
    resizeBound = false;
}
