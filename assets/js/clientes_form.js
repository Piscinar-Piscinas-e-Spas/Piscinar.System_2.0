(function () {
    function toTitleCaseName(value) {
        return value
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim()
            .replace(/(^|\s)([\p{L}])/gu, function (match, sep, letter) {
                return sep + letter.toUpperCase();
            });
    }

    function maskCpfCnpj(value) {
        var digits = value.replace(/\D/g, '').slice(0, 14);

        if (digits.length <= 11) {
            digits = digits
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            return digits;
        }

        digits = digits
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');

        return digits;
    }

    function maskTelefoneBr(value) {
        var digits = value.replace(/\D/g, '').slice(0, 11);

        if (digits.length <= 10) {
            return digits
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
        }

        return digits
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
    }

    function sanitizeTelefoneIntl(value) {
        var cleaned = value.replace(/[^\d+()\-\s]/g, '');
        if (cleaned.indexOf('+') > 0) {
            cleaned = '+' + cleaned.replace(/\+/g, '');
        }
        return cleaned;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var nomeInput = document.querySelector('input[name="nome_cliente"]');
        var telefoneInput = document.querySelector('input[name="telefone_contato"]');
        var cpfCnpjInput = document.querySelector('input[name="cpf_cnpj"]');
        var form = document.querySelector('form[method="POST"]');

        if (nomeInput) {
            nomeInput.addEventListener('blur', function () {
                nomeInput.value = toTitleCaseName(nomeInput.value);
            });
        }

        if (cpfCnpjInput) {
            cpfCnpjInput.addEventListener('input', function () {
                cpfCnpjInput.value = maskCpfCnpj(cpfCnpjInput.value);
            });
        }

        if (telefoneInput) {
            var wrapper = document.createElement('div');
            wrapper.className = 'form-check mt-2';

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input';
            checkbox.id = 'telefone_internacional';

            var label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = 'telefone_internacional';
            label.textContent = 'Número internacional';

            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);

            telefoneInput.parentNode.appendChild(wrapper);

            var applyTelefoneMask = function () {
                if (checkbox.checked) {
                    telefoneInput.value = sanitizeTelefoneIntl(telefoneInput.value);
                    telefoneInput.placeholder = '+1 555 123 4567';
                } else {
                    telefoneInput.value = maskTelefoneBr(telefoneInput.value);
                    telefoneInput.placeholder = '(99) 99999-9999';
                }
            };

            telefoneInput.addEventListener('input', applyTelefoneMask);
            checkbox.addEventListener('change', applyTelefoneMask);
            applyTelefoneMask();
        }

        if (form) {
            form.addEventListener('submit', function () {
                if (nomeInput) {
                    nomeInput.value = toTitleCaseName(nomeInput.value);
                }
            });
        }
    });
})();
