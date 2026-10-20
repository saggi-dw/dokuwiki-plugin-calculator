/* User calculator interface. GPL-2.0. */
(function () {
    'use strict';
    var api = window.DokuCalculator, instance = 0;
    api.modules.user = function (host, def, ui) {
        var id = 'calculator-' + (++instance) + '-', controls = Object.create(null), outputs = [];
        var generation = 0, activeJob = null;
        host.textContent = '';
        function element(tag, text, cls) {
            var node = document.createElement(tag);
            if (text !== undefined) node.textContent = text;
            if (cls) node.className = cls;
            return node;
        }
        host.appendChild(element('h3', def.title, 'calculator-title'));
        var form = element('form', undefined, 'calculator-form');
        form.noValidate = true;
        host.appendChild(form);
        var inputArea = element('div', undefined, 'calculator-inputs');
        form.appendChild(inputArea);
        def.inputs.forEach(function (field) {
            if (field.type === 'const') return;
            var group = element('div', undefined, 'calculator-field' + (field.break ? ' calculator-field-break' : ''));
            var label = element('label', field.label + (field.unit ? ' (' + field.unit + ')' : ''));
            label.htmlFor = id + field.name;
            group.appendChild(label);
            var isList = field.type === 'decimal-list' || field.type === 'regex-list';
            var control = element(isList ? 'textarea' : 'input');
            control.id = id + field.name;
            control.name = field.name;
            if (isList) { control.rows = 8; control.maxLength = 200000; }
            else { control.type = 'text'; control.inputMode = 'decimal'; control.maxLength = 100; }
            control.autocomplete = 'off';
            control.spellcheck = false;
            if (field.default !== undefined) control.value = isList ? String(field.default) : String(field.default).replace('.', ui.locale === 'en-US' ? '.' : ',');
            var hint = element('small', field.type === 'regex-list' ? ui.regex_hint : isList ? ui.list_hint : ui.number_hint, 'calculator-hint');
            hint.id = id + field.name + '-hint';
            control.setAttribute('aria-describedby', hint.id);
            group.appendChild(control); group.appendChild(hint); inputArea.appendChild(group);
            controls[field.name] = control;
        });
        var calculateButton = element('button', ui.calculate); calculateButton.type = 'submit';
        form.appendChild(calculateButton);
        var status = element('p', '', 'calculator-status');
        status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
        form.appendChild(status);
        var outputArea = element('div', undefined, 'calculator-outputs');
        form.appendChild(outputArea);
        def.outputs.forEach(function (output) {
            var group = element('div', undefined, 'calculator-field calculator-output' + (output.break ? ' calculator-field-break' : ''));
            var label = element('label', output.label + (output.unit ? ' (' + output.unit + ')' : ''));
            label.htmlFor = id + output.name;
            var isList = output.type === 'decimal-list' || output.type === 'text-list' || (!output.type && !!output.source);
            var control = element(isList ? 'textarea' : 'input');
            control.id = label.htmlFor; control.readOnly = true; control.spellcheck = false;
            if (isList) control.rows = 8;
            else control.type = 'text';
            var copy = element('button', ui.copy); copy.type = 'button'; copy.disabled = true;
            copy.addEventListener('click', function () {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(control.value).then(function () { status.textContent = ui.copied; }).catch(fallback);
                } else fallback();
                function fallback() {
                    control.focus(); control.select();
                    var ok = false;
                    try { ok = document.execCommand('copy'); } catch (ignore) { /* selection remains available */ }
                    status.textContent = ok ? ui.copied : ui.copy_select;
                }
            });
            group.appendChild(label); group.appendChild(control); group.appendChild(copy); outputArea.appendChild(group);
            outputs.push({ control: control, copy: copy });
        });
        if (def.showwork === true) {
            var basis = element('section', undefined, 'calculator-basis');
            basis.appendChild(element('h4', ui.calculation_basis));
            def.outputs.forEach(function (output) {
                var formula = element('p');
                formula.appendChild(element('strong', output.label + ': '));
                formula.appendChild(element('code', output.formula));
                basis.appendChild(formula);
            });
            def.inputs.forEach(function (field) {
                if (field.type !== 'regex-list') return;
                var rule = element('p');
                rule.appendChild(element('strong', ui.regex_pattern + ' (' + field.label + '): '));
                rule.appendChild(element('code', field.pattern));
                rule.appendChild(document.createTextNode(' [' + (field.flags || '') + ']'));
                basis.appendChild(rule);
            });
            if ((def.constants || []).length) {
                var table = element('table', undefined, 'calculator-constants');
                var head = element('tr');
                [ui.constant_name, ui.constant_value, ui.constant_scope].forEach(function (label) {
                    var th = element('th', label); th.scope = 'col'; head.appendChild(th);
                });
                var thead = element('thead'); thead.appendChild(head); table.appendChild(thead);
                var tbody = element('tbody');
                def.constants.forEach(function (constant) {
                    var row = element('tr');
                    row.appendChild(element('td', constant.name + (constant.label !== constant.name && constant.scope === 'local' ? ' (' + constant.label + ')' : '')));
                    row.appendChild(element('td', String(constant.value).replace('.', ui.locale === 'en-US' ? '.' : ',') + (constant.unit ? ' ' + constant.unit : '')));
                    row.appendChild(element('td', constant.scope === 'global' ? ui.scope_global : ui.scope_local));
                    tbody.appendChild(row);
                });
                table.appendChild(tbody); basis.appendChild(table);
            }
            host.appendChild(basis);
        }
        function clear() {
            generation++;
            if (activeJob) { activeJob.cancel(); activeJob = null; }
            outputs.forEach(function (out) { out.control.value = ''; out.copy.disabled = true; });
            status.textContent = ''; status.classList.remove('calculator-error');
            Object.keys(controls).forEach(function (key) { controls[key].removeAttribute('aria-invalid'); });
        }
        function calculate() {
            clear();
            var request = generation, raw = Object.create(null);
            Object.keys(controls).forEach(function (key) { raw[key] = controls[key].value; });
            if (def.inputs.some(function (field) { return field.type === 'regex-list'; })) status.textContent = ui.regex_processing;
            activeJob = api.regexInput.prepare(def, raw);
            activeJob.promise.then(function (prepared) {
                if (request !== generation) return;
                activeJob = null;
                var results = api.engine.calculate(def, raw, prepared);
                results.forEach(function (result, i) {
                    outputs[i].control.value = api.engine.formatResult(result, ui.locale);
                    outputs[i].copy.disabled = false;
                });
                status.textContent = '';
            }).catch(function (e) {
                if (request !== generation) return;
                activeJob = null; clear();
                status.classList.add('calculator-error');
                status.textContent = (e.field ? e.field + ': ' : '') + (e.line ? ui.line + ' ' + e.line + ': ' : '') +
                    (ui[e.code] || ui.invalid_expression) + (e.detail ? ' (' + e.detail + ')' : '');
                def.inputs.forEach(function (field) {
                    if (field.type !== 'const' && field.label === e.field) controls[field.name].setAttribute('aria-invalid', 'true');
                });
            });
        }
        form.addEventListener('submit', function (event) { event.preventDefault(); calculate(); });
        var timer;
        form.addEventListener('input', function () {
            clear(); window.clearTimeout(timer); timer = window.setTimeout(calculate, 200);
        });
    };
}());
