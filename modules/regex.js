/* Prepare regex-list inputs without blocking the UI. GPL-2.0. */
(function () {
    'use strict';
    var api = window.DokuCalculator;
    function failure(code, field, line) { var e = new Error(code); e.code = code; e.field = field.label; if (line) e.line = line; return e; }
    api.regexInput = {
        prepare: function (definition, raw) {
            var field = definition.inputs.filter(function (input) { return input.type === 'regex-list'; })[0];
            if (!field) return { promise: Promise.resolve({}), cancel: function () {} };
            var worker, timer, rejectJob, settled = false;
            function stop() { if (worker) worker.terminate(); window.clearTimeout(timer); }
            var promise = new Promise(function (resolve, reject) {
                rejectJob = reject;
                var text = String(raw[field.name] || '').replace(/\r\n?/g, '\n'), lines = text.split('\n');
                if (text.length > 200000 || lines.length > 10000) { settled = true; reject(failure('too_many_lines', field)); return; }
                if (!window.Worker) { settled = true; reject(failure('regex_unsupported', field)); return; }
                try { worker = new Worker(DOKU_BASE + 'lib/plugins/calculator/modules/regex-worker.js?v=' + encodeURIComponent(api.version || '0.4.0')); }
                catch (e) { settled = true; reject(failure('regex_unsupported', field)); return; }
                timer = window.setTimeout(function () { if (!settled) { settled = true; stop(); reject(failure('regex_timeout', field)); } }, 2000);
                worker.onerror = function () { if (!settled) { settled = true; stop(); reject(failure('regex_unsupported', field)); } };
                worker.onmessage = function (event) {
                    if (settled) return;
                    settled = true; stop();
                    if (!event.data.ok) { reject(failure(event.data.error, field)); return; }
                    var prepared = Object.create(null); prepared[field.name] = event.data.rows; resolve(prepared);
                };
                worker.postMessage({ pattern: field.pattern, flags: field.flags || '', lines: lines });
            });
            return { promise: promise, cancel: function () { if (!settled) { settled = true; stop(); rejectJob(failure('regex_cancelled', field)); } } };
        }
    };
}());
