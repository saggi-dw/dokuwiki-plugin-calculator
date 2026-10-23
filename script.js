/* Calculator 0.6.5: modules are loaded by JS_SCRIPT_LIST with cache dependencies. */
(function () {
    'use strict';
    window.DokuCalculator.version = '0.6.5';
    function init() {
        document.querySelectorAll('[data-calculator="chem"]').forEach(function (host) {
            if (host.getAttribute('data-calculator-initialized')) return;
            host.setAttribute('data-calculator-initialized', '1');
            window.DokuCalculator.modules.chem(host, JSON.parse(host.getAttribute('data-ui')));
        });
        document.querySelectorAll('[data-calculator="user"]').forEach(function (host) {
            if (host.getAttribute('data-calculator-initialized')) return;
            host.setAttribute('data-calculator-initialized', '1');
            var ui = JSON.parse(host.getAttribute('data-ui'));
            var body = new URLSearchParams();
            body.set('call', 'plugin_calculator_definition');
            body.set('name', host.getAttribute('data-name'));
            body.set('page', host.getAttribute('data-page'));
            fetch(DOKU_BASE + 'lib/exe/ajax.php', {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body: body.toString()
            }).then(function (response) { return response.json(); }).then(function (payload) {
                if (!payload.ok) throw new Error(payload.error || ui.load_error);
                window.DokuCalculator.modules.user(host, payload.definition, ui);
            }).catch(function (e) { host.textContent = ui.load_error + ' ' + e.message; host.classList.add('calculator-error'); });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
}());
