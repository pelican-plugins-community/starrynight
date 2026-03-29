<?php

namespace JoanFo\StarryNight;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;

class StarryNightPlugin implements Plugin
{
    public function getId(): string
    {
        return 'starrynight';
    }

    public function register(Panel $panel): void
    {
        $pairs = [
            [__DIR__.'/../../css/starry-night.css', public_path('plugins/starrynight/css/starry-night.css')],
            [__DIR__.'/../../css/starry-night-light.css', public_path('plugins/starrynight/css/starry-night-light.css')],
        ];

        foreach ($pairs as [$source, $destination]) {
            try {
                if (!File::exists($destination) && File::exists($source)) {
                    $dir = dirname($destination);
                    if (!File::isDirectory($dir)) {
                        File::makeDirectory($dir, 0755, true);
                    }
                    File::copy($source, $destination);
                }
            } catch (\Throwable $e) {

            }
        }

        $panel->colors([
            'danger' => Color::Rose,
            'gray' => Color::Slate,
            'info' => Color::Pink,
            'primary' => Color::Purple,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
        ]);

        $panel->renderHook('panels::head.end', function () {
            $dark = asset('plugins/starrynight/css/starry-night.css');
            $light = asset('plugins/starrynight/css/starry-night-light.css');

            return <<<HTML
<link id="starrynight-css" rel="stylesheet">
<script>
(function () {
    var dark = "{$dark}";
    var light = "{$light}";

    function detectThemeDetail() {
        if (document.documentElement && document.documentElement.classList.contains && document.documentElement.classList.contains('dark')) {
            return { useDark: true, source: 'html.class' };
        }

        var htmlTheme = document.documentElement && document.documentElement.getAttribute && document.documentElement.getAttribute('data-theme');
        if (htmlTheme) {
            var v = ('' + htmlTheme).toLowerCase();
            return { useDark: v === 'dark', source: 'html.data-theme', details: htmlTheme };
        }

        try {
            var keys = ['filament_theme', 'theme', 'filamentTheme'];
            for (var i = 0; i < keys.length; i++) {
                var k = keys[i];
                var val = localStorage.getItem(k);
                if (val) {
                    var lv = ('' + val).toLowerCase();
                    if (lv === 'dark' || lv === 'light') return { useDark: lv === 'dark', source: 'localStorage.' + k, details: val };
                }
            }

            var cap = Math.min(localStorage.length || 0, 50);
            for (var j = 0; j < cap; j++) {
                var key = localStorage.key(j);
                if (!key) continue;
                if (!/filament|theme/i.test(key)) continue;
                var v2 = localStorage.getItem(key);
                if (v2) {
                    var vl = ('' + v2).toLowerCase();
                    if (vl === 'dark' || vl === 'light') return { useDark: vl === 'dark', source: 'localStorage.' + key, details: v2 };
                }
            }
        } catch (e) {
        }

        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return { useDark: true, source: 'prefers-color-scheme' };

        return { useDark: false, source: 'default' };
    }

    function apply() {
        var info = detectThemeDetail();

        if (!info) {
            return info;
        }

        var useDark = !!info.useDark;
        var el = document.getElementById('starrynight-css');
        if (!el) {
            el = document.createElement('link');
            el.rel = 'stylesheet';
            el.id = 'starrynight-css';
            document.head.appendChild(el);
        }
        var target = useDark ? dark : light;
        if (el.getAttribute('href') !== target) el.setAttribute('href', target);
        try {
            var starColor = useDark ? '#ffffff' : '#b86b1a';
            var starTrail = useDark ? 'rgba(255,255,255,0.9)' : 'rgba(184,107,26,0.95)';
            var starGlow = useDark ? 'rgba(255,255,255,0.1)' : 'rgba(184,107,26,0.12)';
            var meteorColor = useDark ? '#ffffff' : '#b86b1a';
            document.documentElement.style.setProperty('--sn-star-color', starColor);
            document.documentElement.style.setProperty('--sn-star-trail-color', starTrail);
            document.documentElement.style.setProperty('--sn-star-glow', starGlow);
            document.documentElement.style.setProperty('--sn-meteor-color', meteorColor);
        } catch (e) {}
        return info;
    }

    try {
        var obs = new MutationObserver(apply);
        if (document.documentElement) obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
        if (document.body) obs.observe(document.body, { attributes: true, attributeFilter: ['class', 'data-theme'] });
    } catch (e) {}

    window.addEventListener('storage', function (e) { if (e && e.key === 'filament_theme') apply(); });
    window.addEventListener('theme-changed', apply);
    if (window.matchMedia) {
        try { window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', apply); } catch (e) { try { window.matchMedia('(prefers-color-scheme: dark)').addListener(apply); } catch (e) {} }
    }

    try { apply(); } catch (e) {}
})();
</script>
HTML;
        });

        $panel->renderHook('panels::body.start', function () {
            $meteorCss = <<<'CSS'
            <style>
            .starrynight-meteors { position: absolute; top: 0; left: 0; width: 100%; height: 100vh; pointer-events: none; z-index: 2; overflow: hidden; }
            .starrynight-meteors span { position: absolute; top: 50%; left: 50%; width: 4px; height: 4px; background: var(--sn-meteor-color, #fff); border-radius: 50%; box-shadow: 0 0 0 4px var(--sn-star-glow, rgba(255,255,255,0.1)),0 0 0 8px var(--sn-star-glow, rgba(255,255,255,0.1)),0 0 20px var(--sn-star-glow, rgba(255,255,255,0.1)); animation: animate 3s linear infinite; }
            .starrynight-meteors span::before { content: ""; position: absolute; top: 50%; transform: translateY(-50%); width: 300px; height: 1px; background: linear-gradient(90deg,var(--sn-star-trail-color, #fff),transparent); }
            @keyframes animate { 0% { transform: rotate(315deg) translateX(0); opacity: 1; } 70% { opacity: 1; } 100% { transform: rotate(315deg) translateX(-1000px); opacity: 0; } }
            .starrynight-meteors span:nth-child(1) { top: 0; right: 0; left: initial; animation-delay: 0s; animation-duration: 1s; }
            .starrynight-meteors span:nth-child(2) { top: 0; right: 80px; left: initial; animation-delay: 0.2s; animation-duration: 3s; }
            .starrynight-meteors span:nth-child(3) { top: 80px; right: 0px; left: initial; animation-delay: 0.4s; animation-duration: 2s; }
            .starrynight-meteors span:nth-child(4) { top: 0; right: 180px; left: initial; animation-delay: 0.6s; animation-duration: 1.5s; }
            .starrynight-meteors span:nth-child(5) { top: 0; right: 400px; left: initial; animation-delay: 0.8s; animation-duration: 2.5s; }
            .starrynight-meteors span:nth-child(6) { top: 0; right: 600px; left: initial; animation-delay: 1s; animation-duration: 3s; }
            .starrynight-meteors span:nth-child(7) { top: 300px; right: 0px; left: initial; animation-delay: 1.2s; animation-duration: 1.75s; }
            .starrynight-meteors span:nth-child(8) { top: 0px; right: 700px; left: initial; animation-delay: 1.4s; animation-duration: 1.25s; }
            .starrynight-meteors span:nth-child(9) { top: 0px; right: 1000px; left: initial; animation-delay: 0.75s; animation-duration: 2.25s; }
            .starrynight-meteors span:nth-child(10) { top: 0px; right: 450px; left: initial; animation-delay: 2.75s; animation-duration: 2.75s; }
            </style>
            CSS;
            $starJs = <<<'JS'
<script>
(function () {
    var STAR_COUNT = 400;
    var FLICKER_COUNT = 80;
    var FLICKER_INTERVAL = 3000;

    function getStarColor() {
        try {
            var c = getComputedStyle(document.documentElement).getPropertyValue('--sn-star-color').trim();
            return c || '#ffffff';
        } catch (e) { return '#ffffff'; }
    }

    function createMeteors() {
        if (document.getElementById('starrynight-meteors')) return;
        var section = document.createElement('section');
        section.className = 'starrynight-meteors';
        section.id = 'starrynight-meteors';
        for (var i = 0; i < 10; i++) {
            section.appendChild(document.createElement('span'));
        }
        if (document.body.firstChild) {
            document.body.insertBefore(section, document.body.firstChild);
        } else {
            document.body.appendChild(section);
        }
    }

    function initStars() {
        if (!document.body) return;

        createMeteors();

        var canvas = document.getElementById('starrynight-stars');
        if (canvas && canvas.dataset.ctInitialized === '1') return;

        if (canvas && canvas.tagName !== 'CANVAS') {
            if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
            canvas = null;
        }

        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'starrynight-stars';
            canvas.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:3;';
            if (document.body.firstChild) {
                document.body.insertBefore(canvas, document.body.firstChild);
            } else {
                document.body.appendChild(canvas);
            }
        }

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        var ctx = canvas.getContext('2d');

        var stars = [];
        for (var i = 0; i < STAR_COUNT; i++) {
            stars.push({
                px: 0.05 + Math.random() * 0.9,
                py: 0.05 + Math.random() * 0.9,
                size: 1 + Math.random() * 2,
                flickering: false,
                opacity: 0.5 + Math.random() * 0.4,
                flickerPhase: Math.random() * Math.PI * 2,
                flickerSpeed: 0.5 + Math.random() * 1.5
            });
        }

        function updateFlickeringStars() {
            for (var i = 0; i < stars.length; i++) stars[i].flickering = false;
            var picked = {};
            var count = 0;
            while (count < FLICKER_COUNT) {
                var idx = Math.floor(Math.random() * STAR_COUNT);
                if (!picked[idx]) { picked[idx] = true; count++; }
            }
            for (var k in picked) { if (stars[k]) stars[k].flickering = true; }
        }

        updateFlickeringStars();

        var lastFlickerUpdate = 0;

        function draw(timestamp) {
            if (!canvas.parentNode) {
                cancelAnimationFrame(window.__starrynight_raf);
                window.__starrynight_raf = null;
                return;
            }
            if (timestamp - lastFlickerUpdate > FLICKER_INTERVAL) {
                updateFlickeringStars();
                lastFlickerUpdate = timestamp;
            }
            var w = window.innerWidth;
            var h = window.innerHeight;
            if (canvas.width !== w) canvas.width = w;
            if (canvas.height !== h) canvas.height = h;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            var color = getStarColor();

            for (var i = 0; i < stars.length; i++) {
                var star = stars[i];
                var opacity = star.opacity;
                var r = star.size / 2;
                if (star.flickering) {
                    var phase = (timestamp / 1000 * star.flickerSpeed + star.flickerPhase);
                    var flicker = (Math.sin(phase) + 1) / 2;
                    opacity = 0.3 + flicker * 0.7;
                    r = (star.size * (0.8 + flicker * 0.4)) / 2;
                }
                ctx.globalAlpha = opacity;
                ctx.fillStyle = color;
                ctx.beginPath();
                ctx.arc(star.px * canvas.width, star.py * canvas.height, Math.max(0.5, r), 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.globalAlpha = 1;
            window.__starrynight_raf = requestAnimationFrame(draw);
        }

        if (window.__starrynight_raf) cancelAnimationFrame(window.__starrynight_raf);
        window.__starrynight_raf = requestAnimationFrame(draw);
        canvas.dataset.ctInitialized = '1';
    }

    window.StarryNight = window.StarryNight || {};
    window.StarryNight.initStars = initStars;

    function reset() {
        if (window.__starrynight_raf) { cancelAnimationFrame(window.__starrynight_raf); window.__starrynight_raf = null; }
        if (window.__starrynight_observer) { window.__starrynight_observer.disconnect(); window.__starrynight_observer = null; }
        var canvas = document.getElementById('starrynight-stars');
        if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
        var meteors = document.getElementById('starrynight-meteors');
        if (meteors && meteors.parentNode) meteors.parentNode.removeChild(meteors);
    }

    function runInit() {
        try { initStars(); } catch (e) { console.error('StarryNight init error', e); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runInit);
    } else {
        runInit();
    }

    window.addEventListener('turbo:load', function () { reset(); runInit(); });
    window.addEventListener('turbo:render', runInit);
    window.addEventListener('pjax:end', function () { reset(); runInit(); });
    window.addEventListener('popstate', runInit);
    window.addEventListener('spa:navigate', function () { reset(); runInit(); });

    try {
        window.__starrynight_observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var removed = mutations[i].removedNodes;
                for (var j = 0; j < removed.length; j++) {
                    if (removed[j] && (removed[j].id === 'starrynight-stars' || removed[j].id === 'starrynight-meteors')) {
                        runInit();
                        return;
                    }
                }
            }
        });
        if (document.body) window.__starrynight_observer.observe(document.body, { childList: true });
    } catch (e) {}

})();
</script>
JS;

            return $meteorCss . $starJs;
        });
    }

    public function boot(Panel $panel): void {}
}
