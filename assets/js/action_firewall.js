(function (window, document) {
    'use strict';

    function buildUrlWithToken(targetUrl, token) {
        var url = new URL(targetUrl, window.location.origin);
        url.searchParams.set('fw_token', token);
        return url.toString();
    }

    window.ActionFirewall = {
        init: function initActionFirewall(options) {
            if (!options || !options.endpoint || !options.csrfToken) {
                return;
            }

            var modalElement = document.getElementById('actionFirewallModal');
            if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                return;
            }

            var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            var passwordInput = document.getElementById('actionFirewallPassword');
            var feedback = document.getElementById('actionFirewallFeedback');
            var description = document.getElementById('actionFirewallDescription');
            var confirmButton = document.getElementById('actionFirewallConfirmButton');
            var confirmLabel = confirmButton ? confirmButton.querySelector('.js-btn-label') : null;
            var confirmSpinner = confirmButton ? confirmButton.querySelector('.spinner-border') : null;
            var pendingAction = null;

            function setLoading(loading) {
                if (!confirmButton) {
                    return;
                }

                confirmButton.disabled = loading;
                if (confirmSpinner) {
                    confirmSpinner.classList.toggle('d-none', !loading);
                }
                if (confirmLabel) {
                    confirmLabel.textContent = loading ? 'Validando...' : 'Confirmar';
                }
            }

            function resetModal() {
                pendingAction = null;
                if (passwordInput) {
                    passwordInput.value = '';
                }
                if (feedback) {
                    feedback.classList.add('d-none');
                    feedback.textContent = '';
                }
                setLoading(false);
            }

            function openForAction(action) {
                resetModal();
                pendingAction = action;
                if (description) {
                    description.textContent = 'Digite sua senha para ' + (action.label || 'continuar') + '.';
                }
                modal.show();
                window.setTimeout(function () {
                    if (passwordInput) {
                        passwordInput.focus();
                    }
                }, 200);
            }

            document.querySelectorAll('.js-firewall-link').forEach(function (button) {
                button.addEventListener('click', function () {
                    openForAction({
                        mode: 'redirect',
                        entity: button.getAttribute('data-firewall-entity') || '',
                        intent: button.getAttribute('data-firewall-intent') || '',
                        recordId: Number.parseInt(button.getAttribute('data-firewall-record-id') || '0', 10) || 0,
                        label: button.getAttribute('data-firewall-label') || 'continuar',
                        targetUrl: button.getAttribute('data-firewall-target-url') || ''
                    });
                });
            });

            document.querySelectorAll('form.js-firewall-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.dataset.firewallApproved === '1') {
                        return;
                    }

                    event.preventDefault();
                    openForAction({
                        mode: 'submit',
                        entity: form.getAttribute('data-firewall-entity') || '',
                        intent: form.getAttribute('data-firewall-intent') || '',
                        recordId: Number.parseInt(form.getAttribute('data-firewall-record-id') || '0', 10) || 0,
                        label: form.getAttribute('data-firewall-label') || 'continuar',
                        form: form
                    });
                });
            });

            modalElement.addEventListener('hidden.bs.modal', resetModal);

            if (!confirmButton) {
                return;
            }

            confirmButton.addEventListener('click', function () {
                var senha = passwordInput ? passwordInput.value : '';
                if (!pendingAction) {
                    return;
                }

                if (!senha) {
                    if (feedback) {
                        feedback.classList.remove('d-none');
                        feedback.textContent = 'Informe sua senha para continuar.';
                    }
                    if (passwordInput) {
                        passwordInput.focus();
                    }
                    return;
                }

                setLoading(true);
                fetch(options.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        senha: senha,
                        csrf_token: options.csrfToken,
                        entity: pendingAction.entity,
                        intent: pendingAction.intent,
                        record_id: pendingAction.recordId
                    })
                })
                    .then(function (response) {
                        return response.json().catch(function () { return {}; });
                    })
                    .then(function (payload) {
                        if (!payload || payload.status !== true || !payload.fw_token) {
                            throw new Error(payload && payload.mensagem ? payload.mensagem : 'Nao foi possivel validar sua senha.');
                        }

                        if (pendingAction.mode === 'redirect' && pendingAction.targetUrl) {
                            window.location.href = buildUrlWithToken(pendingAction.targetUrl, payload.fw_token);
                            return;
                        }

                        if (pendingAction.mode === 'submit' && pendingAction.form) {
                            var tokenInput = pendingAction.form.querySelector('input[name="fw_token"]');
                            if (!tokenInput) {
                                tokenInput = document.createElement('input');
                                tokenInput.type = 'hidden';
                                tokenInput.name = 'fw_token';
                                pendingAction.form.appendChild(tokenInput);
                            }

                            tokenInput.value = payload.fw_token;
                            pendingAction.form.dataset.firewallApproved = '1';
                            pendingAction.form.submit();
                        }
                    })
                    .catch(function (error) {
                        if (feedback) {
                            feedback.classList.remove('d-none');
                            feedback.textContent = error.message || 'Falha ao validar senha.';
                        }
                    })
                    .finally(function () {
                        setLoading(false);
                    });
            });
        }
    };
})(window, document);
