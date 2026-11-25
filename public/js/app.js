document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const mobileQuery = window.matchMedia('(max-width: 900px)');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const closeButtons = document.querySelectorAll('[data-sidebar-close]');

    if (!mobileQuery.matches && localStorage.getItem('pcms-sidebar-collapsed') === '1') {
        body.classList.add('sidebar-collapsed');
    }

    toggles.forEach(function (button) {
        button.addEventListener('click', function () {
            if (mobileQuery.matches) {
                const open = body.classList.toggle('sidebar-open');
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
            } else {
                const collapsed = body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('pcms-sidebar-collapsed', collapsed ? '1' : '0');
                button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            }
        });
    });

    function closeDrawer() {
        body.classList.remove('sidebar-open');
        toggles.forEach(function (button) {
            button.setAttribute('aria-expanded', 'false');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeDrawer);
    }

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeDrawer);
    });

    function createConfirmModal() {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.setAttribute('aria-hidden', 'true');
        backdrop.innerHTML =
            '<div class="modal" role="dialog" aria-modal="true" aria-labelledby="confirm-modal-title" aria-describedby="confirm-modal-message">' +
            '<div class="modal-header">' +
            '<h2 class="modal-title" id="confirm-modal-title">Confirm Action</h2>' +
            '</div>' +
            '<div class="modal-body"><p id="confirm-modal-message"></p></div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn--outline" data-confirm-cancel>Cancel</button>' +
            '<button type="button" class="btn btn--primary" data-confirm-accept>Confirm</button>' +
            '</div>' +
            '</div>';
        document.body.appendChild(backdrop);
        return backdrop;
    }

    let confirmModal = null;
    let activeConfirmResolve = null;
    let previousFocus = null;

    function closeConfirmModal(accepted) {
        if (!confirmModal) {
            return;
        }
        confirmModal.classList.remove('is-open');
        confirmModal.setAttribute('aria-hidden', 'true');
        const resolve = activeConfirmResolve;
        activeConfirmResolve = null;
        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
        previousFocus = null;
        if (resolve) {
            resolve(accepted);
        }
    }

    function showConfirmModal(options) {
        if (!confirmModal) {
            confirmModal = createConfirmModal();
            confirmModal.querySelector('[data-confirm-cancel]').addEventListener('click', function () {
                closeConfirmModal(false);
            });
            confirmModal.querySelector('[data-confirm-accept]').addEventListener('click', function () {
                closeConfirmModal(true);
            });
            confirmModal.addEventListener('click', function (event) {
                if (event.target === confirmModal) {
                    closeConfirmModal(false);
                }
            });
        }

        previousFocus = document.activeElement;
        confirmModal.querySelector('#confirm-modal-title').textContent = options.title || 'Confirm Action';
        confirmModal.querySelector('#confirm-modal-message').textContent = options.message || 'Are you sure you want to continue?';

        const acceptBtn = confirmModal.querySelector('[data-confirm-accept]');
        acceptBtn.textContent = options.okLabel || 'Confirm';
        acceptBtn.className = 'btn ' + (options.danger ? 'btn--danger' : 'btn--primary');

        confirmModal.classList.add('is-open');
        confirmModal.setAttribute('aria-hidden', 'false');
        acceptBtn.focus();

        return new Promise(function (resolve) {
            activeConfirmResolve = resolve;
        });
    }

    function resolveConfirmOptions(element) {
        let message = element.getAttribute('data-confirm');
        let okLabel = element.getAttribute('data-confirm-ok') || 'Confirm';
        let danger = element.getAttribute('data-confirm-variant') === 'danger';
        const selectSelector = element.getAttribute('data-confirm-select');

        if (selectSelector) {
            const select = element.querySelector(selectSelector);
            if (select && select.value) {
                const dynamicMessage = element.getAttribute('data-confirm-' + select.value);
                if (dynamicMessage) {
                    message = dynamicMessage;
                }
                if (select.value === 'approved') {
                    okLabel = 'Approve';
                } else if (select.value === 'rejected') {
                    okLabel = 'Reject';
                    danger = true;
                } else if (select.value === 'accepted') {
                    okLabel = 'Accept';
                } else if (select.value === 'returned') {
                    okLabel = 'Return';
                    danger = true;
                }
            }
        }

        if (!danger && /delete|disable/i.test(message || '')) {
            danger = true;
        }

        return {
            title: element.getAttribute('data-confirm-title') || 'Confirm Action',
            message: message,
            okLabel: okLabel,
            danger: danger,
        };
    }

    function markWorking(form) {
        const button = form.querySelector('[type="submit"]');
        if (button && !button.disabled) {
            button.disabled = true;
            button.textContent = 'Working…';
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && confirmModal && confirmModal.classList.contains('is-open')) {
            closeConfirmModal(false);
            return;
        }
        if (event.key === 'Escape') {
            closeDrawer();
            document.querySelectorAll('.user-menu.open').forEach(function (menu) {
                menu.classList.remove('open');
                const trigger = menu.querySelector('[data-user-menu-toggle]');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });

    document.querySelectorAll('[data-user-menu-toggle]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            const menu = trigger.closest('.user-menu');
            const open = menu.classList.toggle('open');
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.user-menu.open').forEach(function (menu) {
            if (!menu.contains(event.target)) {
                menu.classList.remove('open');
                const trigger = menu.querySelector('[data-user-menu-toggle]');
                if (trigger) {
                    trigger.setAttribute('aria-expanded', 'false');
                }
            }
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        const method = (form.getAttribute('method') || 'get').toLowerCase();

        form.addEventListener('submit', function (event) {
            if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== '1') {
                event.preventDefault();
                showConfirmModal(resolveConfirmOptions(form)).then(function (accepted) {
                    if (!accepted) {
                        return;
                    }
                    form.dataset.confirmed = '1';
                    if (method !== 'get') {
                        markWorking(form);
                    }
                    HTMLFormElement.prototype.submit.call(form);
                });
                return;
            }

            if (method === 'get' || event.defaultPrevented) {
                return;
            }

            markWorking(form);
        });
    });

    document.querySelectorAll('button[data-confirm], a[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (element.dataset.confirmed === '1') {
                return;
            }
            event.preventDefault();
            showConfirmModal(resolveConfirmOptions(element)).then(function (accepted) {
                if (!accepted) {
                    return;
                }
                element.dataset.confirmed = '1';
                element.click();
            });
        });
    });

    const assignUserSelect = document.querySelector('[data-assign-user]');
    const assignRoleDisplay = document.querySelector('[data-assign-role-display]');
    const assignRoleInput = document.querySelector('[data-assign-role-input]');

    if (assignUserSelect && assignRoleDisplay && assignRoleInput) {
        const syncAssignmentRole = function () {
            const option = assignUserSelect.options[assignUserSelect.selectedIndex];
            const role = option ? (option.getAttribute('data-assignment-role') || '') : '';
            const label = option ? (option.getAttribute('data-assignment-role-label') || '') : '';
            assignRoleInput.value = role;
            assignRoleDisplay.value = label;
        };

        assignUserSelect.addEventListener('change', syncAssignmentRole);
        syncAssignmentRole();
    }

    const reviewDecisionSelect = document.querySelector('[data-review-decision]');
    const reviewReasonInput = document.querySelector('[data-review-reason]');
    const reasonHint = document.querySelector('[data-reason-hint]');

    if (reviewDecisionSelect && reviewReasonInput) {
        const syncReviewReasonRequirement = function () {
            const isRejected = reviewDecisionSelect.value === 'rejected';
            reviewReasonInput.required = isRejected;
            if (reasonHint) {
                reasonHint.textContent = isRejected
                    ? '(required for rejection)'
                    : '(optional for approval, required for rejection)';
            }
        };

        reviewDecisionSelect.addEventListener('change', syncReviewReasonRequirement);
        syncReviewReasonRequirement();
    }

    document.querySelectorAll('[data-file-dropzone]').forEach(function (zone) {
        const input = zone.querySelector('input[type="file"]');
        const meta = zone.querySelector('[data-file-meta]');
        const maxBytes = parseInt(zone.getAttribute('data-max-bytes') || '0', 10);

        if (!input || !meta) {
            return;
        }

        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) {
                meta.textContent = meta.getAttribute('data-default-text') || '';
                meta.classList.remove('is-error');
                return;
            }

            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            if (maxBytes > 0 && file.size > maxBytes) {
                const maxMb = (maxBytes / (1024 * 1024)).toFixed(1);
                meta.textContent = file.name + ' (' + sizeMb + ' MB) exceeds the server upload limit (' + maxMb + ' MB). Please choose a smaller file.';
                meta.classList.add('is-error');
            } else {
                meta.textContent = 'Selected: ' + file.name + ' (' + sizeMb + ' MB)';
                meta.classList.remove('is-error');
            }
        });
    });
});


document.querySelectorAll('[data-phone-digits]').forEach(function (input) {
    input.addEventListener('input', function () {
        const digits = input.value.replace(/\D/g, '').slice(0, 9);

        if (input.value !== digits) {
            input.value = digits;
        }
    });
});


(function () {
    const toggle = document.querySelector('[data-theme-toggle]');

    function labelFor(theme) {
        return theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme';
    }

    const current = document.documentElement.dataset.theme || 'light';

    if (toggle) {
        toggle.setAttribute('aria-label', labelFor(current));

        toggle.addEventListener('click', function () {
            const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            localStorage.setItem('pcms-theme', next);
            toggle.setAttribute('aria-label', labelFor(next));
        });
    }

})();
