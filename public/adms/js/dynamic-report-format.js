/**
 * Infere o tipo de cada coluna pelo valor retornado (não pelo alias)
 * e formata para exibição em pt-BR.
 */
(function (global) {
    'use strict';

    var ISO_RE = /^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2}):(\d{2})(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?$/;

    function parseIso(value) {
        if (value == null || value === '') return null;
        if (typeof value === 'object') return null;
        var m = String(value).trim().match(ISO_RE);
        if (!m) return null;
        var hour = m[4] || '00';
        var minute = m[5] || '00';
        var second = m[6] || '00';
        return {
            year: m[1],
            month: m[2],
            day: m[3],
            hour: hour,
            minute: minute,
            midnight: hour === '00' && minute === '00' && second === '00'
        };
    }

    function parseNumber(value) {
        if (typeof value === 'number' && Number.isFinite(value)) {
            return value;
        }
        if (typeof value !== 'string') return null;
        var s = value.trim().replace(/\s/g, '');
        if (!s || ISO_RE.test(s)) return null;
        if (!/^-?\d+(\.\d+)?$/.test(s)) return null;
        var n = parseFloat(s);
        return Number.isFinite(n) ? n : null;
    }

    function decimalPlaces(raw, number) {
        if (typeof raw === 'string') {
            var m = raw.trim().match(/^-?\d+\.(\d+)$/);
            if (m) return m[1].replace(/0+$/, '').length;
        }
        for (var d = 0; d <= 6; d++) {
            var factor = Math.pow(10, d);
            if (Math.abs(number - (Math.round(number * factor) / factor)) < 1e-8) {
                return d;
            }
        }
        return 6;
    }

    function inferType(values) {
        var samples = [];
        for (var i = 0; i < values.length; i++) {
            var value = values[i];
            if (value == null || value === '') continue;
            if (typeof value === 'object') return 'text';
            samples.push(value);
        }
        if (!samples.length) return 'text';

        var dates = 0;
        var months = 0;
        var datetimes = 0;
        var nums = 0;
        var maxDecimals = 0;
        var hasFraction = false;

        for (var j = 0; j < samples.length; j++) {
            var sample = samples[j];
            var iso = parseIso(sample);
            if (iso) {
                dates++;
                if (iso.day === '01' && iso.midnight) months++;
                if (!iso.midnight) datetimes++;
                continue;
            }
            var number = parseNumber(sample);
            if (number === null) return 'text';
            nums++;
            var decimals = decimalPlaces(sample, number);
            if (decimals > maxDecimals) maxDecimals = decimals;
            if (decimals > 0) hasFraction = true;
        }

        var n = samples.length;
        if (dates === n) {
            if (months === n) return 'month';
            return datetimes > 0 ? 'datetime' : 'date';
        }
        if (nums === n) {
            if (!hasFraction) return 'integer';
            return maxDecimals <= 2 ? 'money' : 'number';
        }
        return 'text';
    }

    function inferColumnTypes(rows) {
        var types = {};
        if (!Array.isArray(rows) || !rows.length || typeof rows[0] !== 'object' || rows[0] === null) {
            return types;
        }
        var headers = Object.keys(rows[0]);
        headers.forEach(function (header) {
            types[header] = inferType(rows.map(function (row) { return row[header]; }));
        });
        return types;
    }

    function formatCell(value, type) {
        if (value == null || value === '') return '';
        if (typeof value === 'object') {
            try { return JSON.stringify(value); } catch (e) { return String(value); }
        }
        var iso = parseIso(value);
        if (type === 'month' && iso) return iso.month + '/' + iso.year;
        if (type === 'datetime' && iso) {
            return iso.day + '/' + iso.month + '/' + iso.year + ' ' + iso.hour + ':' + iso.minute;
        }
        if (type === 'date' && iso) return iso.day + '/' + iso.month + '/' + iso.year;

        if (type === 'money' || type === 'number') {
            var number = parseNumber(value);
            if (number !== null) {
                if (type === 'money') {
                    return number.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                }
                var d = Math.min(6, Math.max(1, decimalPlaces(value, number)));
                return number.toLocaleString('pt-BR', { minimumFractionDigits: d, maximumFractionDigits: d });
            }
        }
        return String(value).trim();
    }

    function formatLabels(labels) {
        if (!Array.isArray(labels) || !labels.length) return labels || [];
        var type = inferType(labels);
        return labels.map(function (label) { return formatCell(label, type); });
    }

    global.DynamicReportFormat = {
        inferColumnTypes: inferColumnTypes,
        inferType: inferType,
        formatCell: formatCell,
        formatLabels: formatLabels
    };
})(window);
