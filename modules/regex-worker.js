/* Isolated regex execution; terminated by caller on timeout. GPL-2.0. */
'use strict';
self.onmessage = function (event) {
    try {
        var data = event.data;
        var flags = data.flags || '';
        var regex = new RegExp(data.pattern, flags + 'd');
        if (!regex.hasIndices) throw new Error('regex_unsupported');
        var result = data.lines.map(function (line) {
            if (!line.trim()) return { text: line };
            var match = regex.exec(line);
            if (!match) return { text: line };
            if (!match.groups || !Object.keys(match.groups).length) return { text: line, error: 'regex_invalid_capture' };
            var row = { text: line, rawGroups: match.groups };
            if (Object.prototype.hasOwnProperty.call(match.groups, 'value')) {
                if (typeof match.groups.value !== 'string' || !match.indices.groups || !match.indices.groups.value) return { text: line, error: 'regex_invalid_capture' };
                row.start = match.indices.groups.value[0]; row.end = match.indices.groups.value[1];
                row.raw = match.groups.value;
            }
            return row;
        });
        self.postMessage({ ok: true, rows: result });
    } catch (e) {
        self.postMessage({ ok: false, error: e.message === 'regex_unsupported' ? e.message : 'regex_invalid_pattern' });
    }
};
