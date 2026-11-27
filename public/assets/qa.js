'use strict';
const form = document.getElementById('observation-form');
const keys = ["project","app_version","device","device_model","os","browser","orientation","context","viewport","screen","pixel_ratio","interface_preference","interface_mode"];
const storageKey = 'solace-qa.environment.v1';
const remember = document.getElementById('remember-environment');
const message = document.getElementById('environment-message');
const readEnvironment = () => Object.fromEntries(keys.map(key => [key, form.elements[key].value]));
function applyEnvironment(values) {
    for (const key of keys) {
        if (typeof values[key] === 'string') form.elements[key].value = values[key];
    }
}
function saveEnvironment() {
    try {
        if (remember.checked) localStorage.setItem(storageKey, JSON.stringify(readEnvironment()));
        else localStorage.removeItem(storageKey);
    } catch { message.textContent = 'Environment preferences could not be stored.'; }
}
function detectEnvironment() {
    const ua = navigator.userAgent;
    const match = ua.match(/(?:Vivaldi|EdgA?|EdgiOS|Firefox|FxiOS|Chrome|CriOS|Version)\/[\d.]+/);
    const browser = match ? match[0].replace('/', ' ') : '';
    const os = /Android/.test(ua) ? 'Android' : /iPhone|iPad/.test(ua) ? 'iOS / iPadOS' : /Windows/.test(ua) ? 'Windows' : /Macintosh/.test(ua) ? 'macOS' : /Linux/.test(ua) ? 'Linux' : '';
    applyEnvironment({
        browser, os,
        orientation: innerWidth >= innerHeight ? 'Landscape' : 'Portrait',
        context: matchMedia('(display-mode: standalone)').matches || navigator.standalone === true ? 'Installed PWA' : 'Browser tab',
        viewport: innerWidth + ' × ' + innerHeight,
        screen: screen.width + ' × ' + screen.height,
        pixel_ratio: String(devicePixelRatio)
    });
    message.textContent = 'Reporting browser values filled in. Verify these describe the application under test; browser identity may be inaccurate.';
}
function setDate() {
    const now = new Date();
    form.elements.date.value = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
}
setDate();
form.elements.reproducibility.value = 'Not retested';
try {
    const saved = localStorage.getItem(storageKey);
    if (saved) applyEnvironment(JSON.parse(saved));
} catch { message.textContent = 'Saved environment could not be loaded.'; }
document.getElementById('detect-environment').addEventListener('click', detectEnvironment);
document.getElementById('forget-environment').addEventListener('click', () => {
    remember.checked = false;
    saveEnvironment();
    message.textContent = 'Saved environment removed; current field values retained.';
});
remember.addEventListener('change', saveEnvironment);
form.addEventListener('submit', saveEnvironment);
form.addEventListener('reset', () => {
    const current = readEnvironment();
    const preference = remember.checked;
    setTimeout(() => {
        applyEnvironment(current);
        remember.checked = preference;
        setDate();
        form.elements.reproducibility.value = 'Not retested';
        document.getElementById('severity-value').value = '0';
    }, 0);
});
form.elements.severitylevel.addEventListener('input', () => {
    document.getElementById('severity-value').value = form.elements.severitylevel.value;
});
