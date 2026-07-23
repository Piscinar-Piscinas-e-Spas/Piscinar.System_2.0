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

    function parseDecimal(value) {
        var text = String(value == null ? '' : value)
            .replace(/\s/g, '')
            .replace(/^R\$/i, '')
            .trim();

        if (!text) {
            return 0;
        }

        if (text.includes(',')) {
            text = text.replace(/\./g, '').replace(',', '.');
        }

        var parsed = Number(text.replace(/[^\d.-]/g, ''));
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatMoneyInput(value) {
        var digits = onlyDigits(value);
        if (!digits) {
            digits = '0';
        }

        var amount = Number(digits) / 100;
        return amount.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function applyMoneyMask(input) {
        if (!input) {
            return '';
        }

        input.value = formatMoneyInput(input.value);
        input.setSelectionRange(input.value.length, input.value.length);
        return input.value;
    }

    function selectOnFocus(input) {
        if (!input || input.readOnly || input.disabled) {
            return;
        }

        window.setTimeout(function () {
            input.select();
        }, 0);
    }

    function flashElement(element, variant) {
        if (!element) {
            return;
        }

        var className = variant === 'success' ? 'value-flash-success' : 'value-flash-info';
        element.classList.remove('value-flash-info', 'value-flash-success');
        void element.offsetWidth;
        element.classList.add(className);
        window.setTimeout(function () {
            element.classList.remove(className);
        }, 900);
    }

    function bindMoneyInputs(root, selector) {
        var scope = root || document;
        var fieldSelector = selector || '[data-money-input]';

        scope.querySelectorAll(fieldSelector).forEach(function (input) {
            if (input.dataset.moneyMaskBound === '1') {
                return;
            }

            input.dataset.moneyMaskBound = '1';
            input.addEventListener('focus', function () {
                selectOnFocus(input);
            });
            input.addEventListener('input', function () {
                applyMoneyMask(input);
            }, true);
            input.addEventListener('blur', function () {
                if (String(input.value || '').trim() === '') {
                    input.value = '0,00';
                    return;
                }

                applyMoneyMask(input);
            });
        });
    }

    window.PiscinarMasks = Object.assign({}, window.PiscinarMasks, {
        onlyDigits: onlyDigits,
        formatCpfCnpj: formatCpfCnpj,
        applyCpfCnpjMask: applyCpfCnpjMask,
        parseDecimal: parseDecimal,
        formatMoneyInput: formatMoneyInput,
        applyMoneyMask: applyMoneyMask,
        selectOnFocus: selectOnFocus,
        flashElement: flashElement,
        bindMoneyInputs: bindMoneyInputs
    });
})(window);
