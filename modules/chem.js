/* Chemical calculator UI. GPL-2.0. */
(function () {
    'use strict';
    var api = window.DokuCalculator, instances = 0;
    api.modules.chem = function (host, ui) {
        var fixed = host.getAttribute('data-formula'), interactive = !fixed;
        var id = 'calculator-chem-' + (++instances), result = null, formulaInput, partInput;
        function element(tag, text, cls) {
            var node = document.createElement(tag);
            if (text !== undefined) node.textContent = text;
            if (cls) node.className = cls;
            return node;
        }
        function formatter(digits, fixedDigits) {
            return new Intl.NumberFormat(ui.locale, { useGrouping: true, minimumFractionDigits: fixedDigits ? digits : 0, maximumFractionDigits: digits });
        }
        var fmtPercent = formatter(3, true), fmtPpm = formatter(0, true), fmtOther = formatter(6, false);
        host.textContent = '';
        host.appendChild(element('h3', ui.chem_title, 'calculator-title'));
        if (interactive) {
            var form = element('form', undefined, 'calculator-chem-form'); form.noValidate = true;
            var fields = element('div', undefined, 'calculator-inputs');
            function field(name, label, value) {
                var group = element('div', undefined, 'calculator-field'), input = element('input'), caption = element('label', label);
                input.id = id + '-' + name; caption.htmlFor = input.id; input.type = 'text';
                input.value = value; input.autocomplete = 'off'; input.spellcheck = false;
                group.appendChild(caption); group.appendChild(input); fields.appendChild(group);
                return input;
            }
            formulaInput = field('formula', ui.chem_formula, ''); formulaInput.maxLength = 2000; formulaInput.placeholder = 'TiO2';
            partInput = field('part', ui.chem_part, ''); partInput.inputMode = 'decimal'; partInput.placeholder = '100'; partInput.maxLength = 100;
            form.appendChild(fields); form.appendChild(element('p', ui.chem_hint, 'calculator-hint'));
            form.addEventListener('submit', function (e) { e.preventDefault(); update(); });
            formulaInput.addEventListener('input', update); partInput.addEventListener('input', update);
            host.appendChild(form);
        }
        var notation = element('p', '', 'calculator-chem-notation'); host.appendChild(notation);
        var summary = element('p', '', 'calculator-chem-summary'); host.appendChild(summary);
        var status = element('p', '', 'calculator-status'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite'); host.appendChild(status);
        var scroll = element('div', undefined, 'calculator-table-scroll');
        var table = element('table', undefined, 'calculator-chem-table');
        var headings = [ui.chem_element, ui.chem_count, ui.chem_weight, ui.chem_percent, ui.chem_ppm];
        var header = element('tr'); headings.forEach(function (label) { var th=element('th', label); th.scope='col'; header.appendChild(th); });
        var thead = element('thead'); thead.appendChild(header); table.appendChild(thead);
        var tbody = element('tbody'); table.appendChild(tbody); scroll.appendChild(table); host.appendChild(scroll);
        var copy = element('button', ui.chem_copy_table); copy.type = 'button'; copy.disabled = true; host.appendChild(copy);
        var fallback = element('textarea', '', 'calculator-copy-fallback'); fallback.readOnly = true; fallback.rows = 5; fallback.hidden = true; fallback.setAttribute('aria-label', ui.chem_copy_table); host.appendChild(fallback);
        function copyText(text) {
            function legacy() {
                fallback.hidden = false; fallback.value = text; fallback.focus(); fallback.select();
                var ok = false; try { ok = document.execCommand('copy'); } catch (ignore) { /* keep selected text available */ }
                if (ok) fallback.hidden = true;
                status.textContent = ok ? ui.copied : ui.copy_select;
            }
            if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(function () { status.textContent = ui.copied; }).catch(legacy);
            else legacy();
        }
        function raw(value) { return String(value).replace('.', ui.locale === 'en-US' ? '.' : ','); }
        function rowValues(e) { return [e.symbol, fmtPpm.format(e.count), fmtOther.format(e.atomicWeight), fmtPercent.format(e.percent), fmtPpm.format(e.ppm)]; }
        copy.addEventListener('click', function () {
            if (!result) return;
            var lines = [ui.chem_formula + '\t' + result.formula,
                ui.chem_molar_mass + '\t' + raw(result.molarMass) + '\tg/mol',
                ui.chem_part + '\t' + raw(result.part), headings.join('\t')];
            result.elements.forEach(function (e) { lines.push(rowValues(e).join('\t')); });
            copyText(lines.join('\n'));
        });
        var basis = element('section', undefined, 'calculator-basis');
        basis.appendChild(element('h4', ui.calculation_basis)); basis.appendChild(element('p', ui.chem_basis));
        var source = element('p', ui.chem_weights_source + ' ');
        var link = element('a', 'CIAAW 2024'); link.href = 'https://www.ciaaw.org/abridged-atomic-weights.htm'; source.appendChild(link); basis.appendChild(source); host.appendChild(basis);
        function drawNotation(formula) {
            notation.textContent = ''; notation.setAttribute('aria-label', formula);
            var re = /\d+/g, previous = 0, match;
            while ((match = re.exec(formula))) {
                notation.appendChild(document.createTextNode(formula.slice(previous, match.index)));
                var sub = match.index > 0 && /[A-Za-z)\]}]/.test(formula[match.index - 1]);
                notation.appendChild(sub ? element('sub', match[0]) : document.createTextNode(match[0]));
                previous = match.index + match[0].length;
            }
            notation.appendChild(document.createTextNode(formula.slice(previous)));
        }
        function update() {
            result = null; tbody.textContent = ''; notation.textContent = ''; summary.textContent = ''; status.textContent = '';
            status.classList.remove('calculator-error'); copy.disabled = true; fallback.hidden = true; scroll.hidden = true;
            if (formulaInput) formulaInput.removeAttribute('aria-invalid');
            if (partInput) partInput.removeAttribute('aria-invalid');
            var input = interactive ? formulaInput.value : fixed;
            if (!input.trim()) { status.textContent = ui.chem_enter_formula; return; }
            var part = 100;
            if (interactive && partInput.value.trim()) {
                try { part = api.engine.number(partInput.value); if (part < 0 || part > 100) throw new Error(); }
                catch (e) { status.textContent = ui.chem_part_error; status.classList.add('calculator-error'); partInput.setAttribute('aria-invalid','true'); return; }
            }
            try {
                result = api.chemistry.calculate(input, part);
                drawNotation(result.formula);
                summary.textContent = ui.chem_molar_mass + ': ' + fmtOther.format(result.molarMass) + ' g/mol. ' + ui.chem_total + ': ' + fmtOther.format(result.part) + ' %. ' + ui.chem_rounding;
                result.elements.forEach(function (entry) {
                    var row = element('tr'), values = [null, entry.count, entry.atomicWeight, entry.percent, entry.ppm];
                    rowValues(entry).forEach(function (text, i) {
                        var cell = element('td', text);
                        if (i > 0) {
                            cell.className = 'calculator-number'; cell.tabIndex = 0; cell.title = ui.chem_copy_cell;
                            cell.setAttribute('data-value', String(values[i]));
                            cell.addEventListener('dblclick', function () { copyText(raw(values[i])); });
                            cell.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); copyText(raw(values[i])); } });
                        }
                        row.appendChild(cell);
                    });
                    tbody.appendChild(row);
                });
                scroll.hidden = false; copy.disabled = false;
            } catch (e) {
                result = null; status.classList.add('calculator-error');
                status.textContent = (ui[e.code] || ui.chem_invalid_character) + (e.detail ? ' (' + e.detail + ')' : '') + (e.position ? ' — ' + ui.chem_position + ' ' + e.position : '');
                if (formulaInput) formulaInput.setAttribute('aria-invalid', 'true');
            }
        }
        update();
    };
}());
