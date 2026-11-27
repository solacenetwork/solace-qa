'use strict';
const form = document.getElementById('observation-form');
const keys = ["project","app_version","device","device_model","os","browser","orientation","context","viewport","screen","pixel_ratio","interface_mode"];
const storageKey = 'solace-qa.environment.v1';
const remember = document.getElementById('remember-environment');
const message = document.getElementById('environment-message');
const details = document.getElementById('environment-details');
const summary = document.getElementById('environment-summary');
function updateSummary() {
    summary.textContent = [form.elements.project.value, form.elements.browser.value, form.elements.device.value, form.elements.interface_mode.value].filter(Boolean).join(' · ');
}
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
    if (saved) {
        applyEnvironment(JSON.parse(saved));
        if (form.elements.project.value.trim() && form.elements.device.value) details.open = false;
    }
} catch { message.textContent = 'Saved environment could not be loaded.'; }
updateSummary();
for (const key of keys) form.elements[key].addEventListener('input', updateSummary);
form.addEventListener('invalid', event => {
    if (details.contains(event.target)) details.open = true;
}, true);
document.getElementById('detect-environment').addEventListener('click', () => { detectEnvironment(); updateSummary(); });
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
        updateSummary();
        remember.checked = preference;
        setDate();
        form.elements.reproducibility.value = 'Not retested';
        updateSeverity();
    }, 0);
});
function updateSeverity() {
    const input = form.elements.severitylevel;
    const fraction = (Number(input.value) - Number(input.min)) / (Number(input.max) - Number(input.min));
    input.closest('.form-range').style.setProperty('--bs-range-fill', String(fraction));
    document.getElementById('severity-value').value = input.value;
}
form.elements.severitylevel.addEventListener('input', updateSeverity);
form.elements.severitylevel.addEventListener('change', updateSeverity);
updateSeverity();
