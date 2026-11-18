/**
 * Metamint Helpdesk — Setup Wizard JavaScript
 * Self-contained • Zero inline dependencies • Toast feedback
 */

(function () {
    'use strict';

    // Retrieve localized configuration
    var config = window.mmhdWizardData || {};
    var ajaxUrl = config.ajaxUrl || '';
    var nonce = config.nonce || '';
    var steps = config.steps || {};
    var dashboardUrl = config.dashboardUrl || '';

    // ============================================================
    // Toast Notification System
    // ============================================================
    function showToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('mmhd-wizard-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'mmhd-wizard-toast-container';
            container.className = 'mmhd-wizard-toast-container';
            document.body.appendChild(container);
        }

        var toast = document.createElement('div');
        toast.className = 'mmhd-wizard-toast is-' + type;

        var iconSvg = type === 'error'
            ? '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            : '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

        toast.innerHTML = iconSvg + '<span>' + escapeHtml(message) + '</span>';
        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('is-removing');
            setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3500);
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ============================================================
    // Copy Shortcodes
    // ============================================================
    document.addEventListener('click', function (e) {
        var copyBtn = e.target.closest('.mmhd-wizard-copy-btn, .mmhd-wizard-copy-chip');
        if (copyBtn) {
            e.preventDefault();
            var code = copyBtn.getAttribute('data-code') || '';
            if (!code) return;

            navigator.clipboard.writeText(code).then(function () {
                var isChip = copyBtn.classList.contains('mmhd-wizard-copy-chip');
                if (!isChip) {
                    var originalText = copyBtn.textContent;
                    copyBtn.textContent = 'Copied!';
                    copyBtn.style.background = '#10b981';
                    copyBtn.style.color = '#ffffff';
                    copyBtn.style.borderColor = '#10b981';
                    setTimeout(function () {
                        copyBtn.textContent = originalText;
                        copyBtn.style.background = '';
                        copyBtn.style.color = '';
                        copyBtn.style.borderColor = '';
                    }, 1800);
                }
                showToast('Shortcode copied: ' + code);
            }).catch(function () {
                showToast('Could not copy to clipboard', 'error');
            });
        }
    });

    // ============================================================
    // Setup Page Interactions
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {
        initSetupPage();
        initAddonsPage();
        initRoleSelection();
        initLivePreview();
        initPresets();
        initFinishCelebration();
    });

    // ============================================================
    // Live Chat Widget Interactive Preview (WYSIWYG)
    // ============================================================
    function initLivePreview() {
        var themeColorInput = document.getElementById('wz-theme-color');
        var chatHeadingInput = document.getElementById('wz-chat-heading');
        var welcomeMsgInput = document.getElementById('wz-welcome-message');
        var enableChatToggle = document.getElementById('wz-enable-chat');

        var previewHeader = document.getElementById('wz-preview-header');
        var previewTitle = document.getElementById('wz-preview-title');
        var previewBubble = document.getElementById('wz-preview-bubble');
        var previewTrigger = document.getElementById('wz-preview-trigger');
        var previewWidget = document.getElementById('wz-mockup-widget');

        if (!themeColorInput) return;

        function updatePreviewColor(color) {
            if (previewHeader) previewHeader.style.backgroundColor = color;
            if (previewTrigger) previewTrigger.style.backgroundColor = color;
            var previewClose = document.getElementById('wz-preview-close');
            if (previewClose) previewClose.style.color = color;
            var previewInputBox = document.getElementById('wz-preview-input-box');
            if (previewInputBox) previewInputBox.style.borderColor = color;
            var previewPlaceholder = document.getElementById('wz-preview-placeholder');
            if (previewPlaceholder) previewPlaceholder.style.color = color;
            var previewSend = document.getElementById('wz-preview-send');
            if (previewSend) previewSend.style.color = color;
            var previewTabHome = document.getElementById('wz-preview-tab-home');
            if (previewTabHome) previewTabHome.style.color = color;
            var hexDisplay = document.getElementById('wz-theme-color-hex');
            if (hexDisplay) hexDisplay.textContent = color;
        }

        function updatePreviewHeading(heading) {
            if (previewTitle) {
                previewTitle.textContent = heading.trim() || 'Have Questions?';
            }
        }

        function updatePreviewBubble(msg) {
            if (previewBubble) {
                previewBubble.textContent = msg.trim() || 'Leave us a message and we will get back to you shortly.';
            }
        }

        function updateChatVisibility(enabled) {
            if (previewWidget) {
                previewWidget.style.opacity = enabled ? '1' : '0.4';
                previewWidget.style.filter = enabled ? 'none' : 'grayscale(1)';
            }
            if (previewTrigger) {
                previewTrigger.style.opacity = enabled ? '1' : '0.3';
            }
            var statusTag = document.getElementById('wz-preview-status-tag');
            if (statusTag) {
                statusTag.textContent = enabled ? 'Live Preview' : 'Widget Inactive';
            }
        }

        themeColorInput.addEventListener('input', function (e) {
            updatePreviewColor(e.target.value);
        });

        if (chatHeadingInput) {
            chatHeadingInput.addEventListener('input', function (e) {
                updatePreviewHeading(e.target.value);
            });
        }

        if (welcomeMsgInput) {
            welcomeMsgInput.addEventListener('input', function (e) {
                updatePreviewBubble(e.target.value);
            });
        }

        if (enableChatToggle) {
            enableChatToggle.addEventListener('change', function (e) {
                updateChatVisibility(e.target.checked);
            });
        }
    }

    // ============================================================
    // 1-Click Starter Presets
    // ============================================================
    function initPresets() {
        document.addEventListener('click', function (e) {
            var presetBtn = e.target.closest('.mmhd-wizard-preset-pill');
            if (!presetBtn) return;
            e.preventDefault();

            var type = presetBtn.getAttribute('data-preset-type');
            var value = presetBtn.getAttribute('data-preset-value');
            var color = presetBtn.getAttribute('data-preset-color');

            if (type === 'department') {
                var deptInput = document.getElementById('wz-new-dept');
                var addDeptBtn = document.getElementById('wz-add-dept');
                if (deptInput && addDeptBtn) {
                    deptInput.value = value;
                    addDeptBtn.click();
                    presetBtn.style.opacity = '0.4';
                    presetBtn.style.pointerEvents = 'none';
                }
            } else if (type === 'priority') {
                var priorityInput = document.getElementById('wz-new-priority');
                var colorInput = document.getElementById('wz-new-priority-color');
                var addPriorityBtn = document.getElementById('wz-add-priority');
                if (priorityInput && addPriorityBtn) {
                    priorityInput.value = value;
                    if (color && colorInput) colorInput.value = color;
                    addPriorityBtn.click();
                    presetBtn.style.opacity = '0.4';
                    presetBtn.style.pointerEvents = 'none';
                }
            }
        });
    }

    // ============================================================
    // Lightweight Confetti Particles (Finish Celebration)
    // ============================================================
    function initFinishCelebration() {
        var canvas = document.getElementById('mmhd-confetti-canvas');
        if (!canvas) return;

        var ctx = canvas.getContext('2d');
        var container = canvas.parentElement;
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;

        var particles = [];
        var colors = ['#10b981', '#059669', '#34d399', '#6ee7b7', '#3b82f6', '#f59e0b'];

        for (var i = 0; i < 60; i++) {
            particles.push({
                x: canvas.width / 2,
                y: 120,
                r: Math.random() * 5 + 3,
                d: Math.random() * 60,
                color: colors[Math.floor(Math.random() * colors.length)],
                tilt: Math.floor(Math.random() * 10) - 10,
                tiltAngleIncremental: (Math.random() * 0.07) + 0.05,
                tiltAngle: 0,
                vx: (Math.random() - 0.5) * 8,
                vy: (Math.random() - 0.5) * 8 - 3,
                opacity: 1
            });
        }

        var animationFrame;
        var start = Date.now();

        function render() {
            var elapsed = Date.now() - start;
            if (elapsed > 4000) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                cancelAnimationFrame(animationFrame);
                return;
            }

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.15; // gravity
                p.vx *= 0.98; // friction
                p.tiltAngle += p.tiltAngleIncremental;
                p.tilt = Math.sin(p.tiltAngle) * 12;

                ctx.beginPath();
                ctx.lineWidth = p.r;
                ctx.strokeStyle = p.color;
                ctx.moveTo(p.x + p.tilt + p.r, p.y);
                ctx.lineTo(p.x + p.tilt, p.y + p.tilt + p.r);
                ctx.stroke();
            }

            animationFrame = requestAnimationFrame(render);
        }

        render();
    }

    function initRoleSelection() {
        var roleOptions = document.querySelectorAll('.mmhd-wizard-role-option');
        if (!roleOptions.length) return;

        roleOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                roleOptions.forEach(function (opt) {
                    opt.classList.remove('is-selected');
                });
                option.classList.add('is-selected');
                var radio = option.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });
    }

    function initSetupPage() {
        var deptList = document.getElementById('wz-dept-list');
        var priorityList = document.getElementById('wz-priority-list');

        // Only run if on setup page
        if (!deptList && !priorityList) return;

        // Empty state checker
        function updateEmptyState(listElement, placeholderText) {
            if (!listElement) return;
            var chips = listElement.querySelectorAll('.mmhd-wizard-chip');
            var existingEmpty = listElement.querySelector('.mmhd-wizard-empty-state');
            if (chips.length === 0) {
                if (!existingEmpty) {
                    var emptyDiv = document.createElement('div');
                    emptyDiv.className = 'mmhd-wizard-empty-state';
                    emptyDiv.textContent = placeholderText;
                    listElement.appendChild(emptyDiv);
                }
            } else if (existingEmpty) {
                existingEmpty.remove();
            }
        }

        // Add Department
        var newDeptInput = document.getElementById('wz-new-dept');
        var addDeptBtn = document.getElementById('wz-add-dept');

        if (addDeptBtn && newDeptInput) {
            function handleAddDept() {
                var name = newDeptInput.value.trim();
                if (!name) {
                    newDeptInput.focus();
                    return;
                }

                newDeptInput.disabled = true;
                addDeptBtn.disabled = true;
                var origText = addDeptBtn.textContent;
                addDeptBtn.textContent = 'Adding...';

                var params = new URLSearchParams();
                params.append('action', 'mmhd_wizard_add_item');
                params.append('nonce', nonce);
                params.append('type', 'department');
                params.append('name', name);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    newDeptInput.disabled = false;
                    addDeptBtn.disabled = false;
                    addDeptBtn.textContent = origText;

                    if (data.success && data.data) {
                        var item = (data.data.data) ? data.data.data : data.data;
                        newDeptInput.value = '';
                        var emptyState = deptList.querySelector('.mmhd-wizard-empty-state');
                        if (emptyState) emptyState.remove();

                        var chip = document.createElement('div');
                        chip.className = 'mmhd-wizard-chip';
                        chip.innerHTML = '<span>' + escapeHtml(item.name) + '</span>' +
                            '<span class="mmhd-wizard-chip-count">0</span>' +
                            '<button type="button" class="mmhd-wizard-chip-remove" data-type="department" data-id="' + escapeHtml(String(item.id)) + '" title="Remove">' +
                            '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
                            '</button>';

                        deptList.prepend(chip);
                        newDeptInput.focus();
                        showToast('Department "' + item.name + '" added');
                    } else {
                        var errMsg = (data.data && data.data.message) ? data.data.message : 'Could not add department';
                        showToast(errMsg, 'error');
                    }
                })
                .catch(function () {
                    newDeptInput.disabled = false;
                    addDeptBtn.disabled = false;
                    addDeptBtn.textContent = origText;
                    showToast('Network error while adding department', 'error');
                });
            }

            addDeptBtn.addEventListener('click', handleAddDept);
            newDeptInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleAddDept();
                }
            });
        }

        // Add Priority
        var newPriorityInput = document.getElementById('wz-new-priority');
        var newPriorityColor = document.getElementById('wz-new-priority-color');
        var addPriorityBtn = document.getElementById('wz-add-priority');

        if (addPriorityBtn && newPriorityInput && newPriorityColor) {
            function handleAddPriority() {
                var name = newPriorityInput.value.trim();
                var color = newPriorityColor.value;

                if (!name) {
                    newPriorityInput.focus();
                    return;
                }

                newPriorityInput.disabled = true;
                addPriorityBtn.disabled = true;
                var origText = addPriorityBtn.textContent;
                addPriorityBtn.textContent = 'Adding...';

                var params = new URLSearchParams();
                params.append('action', 'mmhd_wizard_add_item');
                params.append('nonce', nonce);
                params.append('type', 'priority');
                params.append('name', name);
                params.append('color', color);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    newPriorityInput.disabled = false;
                    addPriorityBtn.disabled = false;
                    addPriorityBtn.textContent = origText;

                    if (data.success && data.data) {
                        var item = (data.data.data) ? data.data.data : data.data;
                        newPriorityInput.value = '';
                        var emptyState = priorityList.querySelector('.mmhd-wizard-empty-state');
                        if (emptyState) emptyState.remove();

                        var chip = document.createElement('div');
                        chip.className = 'mmhd-wizard-chip';
                        chip.innerHTML = '<span class="mmhd-wizard-chip-color" style="background-color: ' + escapeHtml(color) + '"></span>' +
                            '<span>' + escapeHtml(item.name) + '</span>' +
                            '<span class="mmhd-wizard-chip-count">0</span>' +
                            '<button type="button" class="mmhd-wizard-chip-remove" data-type="priority" data-id="' + escapeHtml(String(item.id)) + '" title="Remove">' +
                            '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
                            '</button>';

                        priorityList.prepend(chip);
                        newPriorityInput.focus();
                        showToast('Priority "' + item.name + '" added');
                        showToast('Priority "' + data.data.name + '" added');
                    } else {
                        var errMsg = (data.data && data.data.message) ? data.data.message : 'Could not add priority';
                        showToast(errMsg, 'error');
                    }
                })
                .catch(function () {
                    newPriorityInput.disabled = false;
                    addPriorityBtn.disabled = false;
                    addPriorityBtn.textContent = origText;
                    showToast('Network error while adding priority', 'error');
                });
            }

            addPriorityBtn.addEventListener('click', handleAddPriority);
            newPriorityInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleAddPriority();
                }
            });
        }

        // Remove item (Department or Priority)
        document.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('.mmhd-wizard-chip-remove');
            if (removeBtn) {
                e.preventDefault();
                var type = removeBtn.getAttribute('data-type');
                var id = removeBtn.getAttribute('data-id');
                var chip = removeBtn.closest('.mmhd-wizard-chip');

                if (chip) {
                    chip.style.opacity = '0.5';
                    chip.style.pointerEvents = 'none';
                }

                var params = new URLSearchParams();
                params.append('action', 'mmhd_wizard_remove_item');
                params.append('nonce', nonce);
                params.append('type', type);
                params.append('id', id);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        if (chip) chip.remove();
                        if (type === 'department') {
                            updateEmptyState(deptList, 'No departments yet. Add your first department below.');
                        } else if (type === 'priority') {
                            updateEmptyState(priorityList, 'No priorities yet. Add custom urgency levels below.');
                        }
                        showToast('Removed successfully');
                    } else {
                        if (chip) {
                            chip.style.opacity = '1';
                            chip.style.pointerEvents = 'auto';
                        }
                        showToast(data.data && data.data.message ? data.data.message : 'Remove failed', 'error');
                    }
                })
                .catch(function () {
                    if (chip) {
                        chip.style.opacity = '1';
                        chip.style.pointerEvents = 'auto';
                    }
                    showToast('Network error while removing item', 'error');
                });
            }
        });

        // Setup "Next: Addons" Save Handler
        var setupNextBtn = document.getElementById('wz-setup-next');
        if (setupNextBtn) {
            setupNextBtn.addEventListener('click', function () {
                var btn = setupNextBtn;
                var origContent = btn.innerHTML;
                btn.classList.add('is-loading');
                btn.textContent = 'Saving Preferences...';

                // Collect settings from setup screen
                var params = new URLSearchParams();
                params.append('action', 'mmhd_wizard_save_step');
                params.append('step', 'setup');
                params.append('nonce', nonce);

                var siteName = document.getElementById('wz-site-name');
                if (siteName) params.append('site_name', siteName.value);

                var adminEmail = document.getElementById('wz-admin-email');
                if (adminEmail) params.append('admin_email', adminEmail.value);

                var themeColor = document.getElementById('wz-theme-color');
                if (themeColor) params.append('theme_color', themeColor.value);

                var enableChat = document.getElementById('wz-enable-chat');
                if (enableChat) params.append('enable_chat', enableChat.checked ? '1' : '0');

                var chatHeading = document.getElementById('wz-chat-heading');
                if (chatHeading) params.append('chat_heading', chatHeading.value);

                var welcomeMsg = document.getElementById('wz-welcome-message');
                if (welcomeMsg) params.append('welcome_message', welcomeMsg.value);

                var defaultRole = document.querySelector('input[name="wz-default-role"]:checked');
                if (defaultRole) params.append('default_role', defaultRole.value);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.href = steps.addons || (config.adminUrl + 'admin.php?page=mmhd-wizard&step=addons');
                    } else {
                        btn.classList.remove('is-loading');
                        btn.innerHTML = origContent;
                        showToast(data.data && data.data.message ? data.data.message : 'Save failed', 'error');
                    }
                })
                .catch(function () {
                    btn.classList.remove('is-loading');
                    btn.innerHTML = origContent;
                    showToast('Connection error during save', 'error');
                });
            });
        }
    }

    // ============================================================
    // Addons Page Interactions
    // ============================================================
    function initAddonsPage() {
        var addonsNextBtn = document.getElementById('wz-addons-next');
        if (addonsNextBtn) {
            addonsNextBtn.addEventListener('click', function () {
                var btn = addonsNextBtn;
                var origContent = btn.innerHTML;
                btn.classList.add('is-loading');
                btn.textContent = 'Saving...';

                var params = new URLSearchParams();
                params.append('action', 'mmhd_wizard_save_step');
                params.append('step', 'addons');
                params.append('nonce', nonce);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        window.location.href = steps.finish || (config.adminUrl + 'admin.php?page=mmhd-wizard&step=finish');
                    } else {
                        btn.classList.remove('is-loading');
                        btn.innerHTML = origContent;
                        showToast(data.data && data.data.message ? data.data.message : 'Save failed', 'error');
                    }
                })
                .catch(function () {
                    btn.classList.remove('is-loading');
                    btn.innerHTML = origContent;
                    showToast('Connection error', 'error');
                });
            });
        }

        // Addons Category Filter
        var filterBtns = document.querySelectorAll('.mmhd-wizard-filter-btn');
        var addonCards = document.querySelectorAll('.mmhd-wizard-addon-card');

        if (filterBtns.length && addonCards.length) {
            filterBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');

                    var category = btn.getAttribute('data-category') || 'all';

                    addonCards.forEach(function (card) {
                        var cardCats = (card.getAttribute('data-categories') || '').split(' ');
                        if (category === 'all' || cardCats.indexOf(category) !== -1) {
                            card.style.display = 'flex';
                            card.style.animation = 'mmhd-pop-in 0.25s ease-out';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        }

        // Addons Live Search
        var searchInput = document.getElementById('wz-addons-search');
        if (searchInput && addonCards.length) {
            searchInput.addEventListener('input', function (e) {
                var query = e.target.value.toLowerCase().trim();

                // If searching, reset category filter to 'All'
                if (query && filterBtns.length) {
                    filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
                    var allBtn = document.querySelector('.mmhd-wizard-filter-btn[data-category="all"]');
                    if (allBtn) allBtn.classList.add('is-active');
                }

                addonCards.forEach(function (card) {
                    var text = card.textContent.toLowerCase();
                    if (!query || text.indexOf(query) !== -1) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    }

})();
