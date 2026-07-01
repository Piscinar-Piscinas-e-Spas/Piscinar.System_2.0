(function (window) {
    'use strict';

    function onlyDigits(value, maxLength) {
        var digits = String(value || '').replace(/\D/g, '');

        if (typeof maxLength === 'number' && maxLength > 0) {
            return digits.slice(0, maxLength);
        }

        return digits;
    }

    function formatCpfCnpj(value) {
        var digits = onlyDigits(value, 14);

        if (digits.length <= 11) {
            return digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }

        return digits
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    function applyCpfCnpjMask(input) {
        if (!input) {
            return '';
        }

        input.value = formatCpfCnpj(input.value);
        return input.value;
    }

    window.PiscinarMasks = Object.assign({}, window.PiscinarMasks, {
        onlyDigits: onlyDigits,
        formatCpfCnpj: formatCpfCnpj,
        applyCpfCnpjMask: applyCpfCnpjMask
    });
})(window);
