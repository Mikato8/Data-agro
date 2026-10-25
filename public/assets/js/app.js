(function () {
    'use strict';

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    // ---------------------------------------------------------------------
    // Menú móvil
    // ---------------------------------------------------------------------
    const menuToggle = $('[data-menu-toggle]');
    if (menuToggle) {
        menuToggle.addEventListener('click', () => {
            const open = document.body.classList.toggle('nav-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ---------------------------------------------------------------------
    // Menú del panel editorial
    // ---------------------------------------------------------------------
    const adminToggle = $('[data-admin-toggle]');
    const adminOverlay = $('[data-admin-overlay]');
    if (adminToggle) {
        const closeAdmin = () => {
            document.body.classList.remove('admin-nav-open');
            adminToggle.setAttribute('aria-expanded', 'false');
            if (adminOverlay) adminOverlay.hidden = true;
        };
        adminToggle.addEventListener('click', () => {
            const open = document.body.classList.toggle('admin-nav-open');
            adminToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (adminOverlay) adminOverlay.hidden = !open;
        });
        if (adminOverlay) adminOverlay.addEventListener('click', closeAdmin);
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAdmin();
        });
    }

    // ---------------------------------------------------------------------
    // Cerrar menús de usuario al hacer clic fuera
    // ---------------------------------------------------------------------
    document.addEventListener('click', (event) => {
        $$('details.user-menu[open]').forEach((details) => {
            if (!details.contains(event.target)) details.removeAttribute('open');
        });
    });

    // ---------------------------------------------------------------------
    // Mensajes flash
    // ---------------------------------------------------------------------
    $$('[data-dismiss]').forEach((button) => {
        button.addEventListener('click', () => {
            const flash = button.closest('.flash');
            if (flash) flash.remove();
        });
    });

    // ---------------------------------------------------------------------
    // Copiar al portapapeles
    // ---------------------------------------------------------------------
    $$('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const text = button.getAttribute('data-copy') || '';
            const original = button.innerHTML;
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.style.position = 'fixed';
                    area.style.opacity = '0';
                    document.body.appendChild(area);
                    area.select();
                    document.execCommand('copy');
                    area.remove();
                }
                button.innerHTML = 'Copiado';
                window.setTimeout(() => { button.innerHTML = original; }, 1800);
            } catch (error) {
                window.prompt('Copia el texto:', text);
            }
        });
    });

    // ---------------------------------------------------------------------
    // Contadores de caracteres
    // ---------------------------------------------------------------------
    const updateCounter = (field) => {
        const target = document.querySelector('[data-counter-for="' + field.id + '"]');
        if (!target) return;
        const max = field.getAttribute('maxlength');
        const current = field.value.length;
        target.textContent = max
            ? current.toLocaleString('es-MX') + ' / ' + Number(max).toLocaleString('es-MX') + ' caracteres'
            : current.toLocaleString('es-MX') + ' caracteres';
    };
    $$('[data-counter]').forEach((field) => {
        updateCounter(field);
        field.addEventListener('input', () => updateCounter(field));
    });

    // ---------------------------------------------------------------------
    // Cultivo → variedades
    // ---------------------------------------------------------------------
    const cropsSelect = $('[data-crops-select]');
    const varietySelect = $('[data-variety-select]');
    if (cropsSelect && varietySelect) {
        const filterVarietys = () => {
            const cropsId = cropsSelect.value;
            let hasSelected = false;
            $$('option', varietySelect).forEach((option) => {
                const optionCrops = option.getAttribute('data-crops-id');
                if (!optionCrops) return;
                const visible = !cropsId || optionCrops === cropsId;
                option.hidden = !visible;
                option.disabled = !visible;
                if (visible && option.selected) hasSelected = true;
            });
            if (!hasSelected) {
                const first = $('option[value=""]', varietySelect);
                if (first) first.selected = true;
            }
        };
        cropsSelect.addEventListener('change', filterVarietys);
        filterVarietys();
    }

    // ---------------------------------------------------------------------
    // Previsualización de imágenes
    // ---------------------------------------------------------------------
    const imageInput = $('[data-image-input]');
    const previews = $('[data-image-previews]');
    const maxImages = 6;

    const renderPreviews = (files) => {
        if (!previews) return;
        previews.innerHTML = '';
        Array.from(files).slice(0, maxImages).forEach((file) => {
            if (!file.type.startsWith('image/')) return;
            const figure = document.createElement('figure');
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = '';
            const caption = document.createElement('figcaption');
            caption.textContent = file.name;
            figure.appendChild(img);
            figure.appendChild(caption);
            previews.appendChild(figure);
        });
    };

    if (imageInput) {
        imageInput.addEventListener('change', () => {
            if (imageInput.files.length > maxImages) {
                const transfer = new DataTransfer();
                Array.from(imageInput.files).slice(0, maxImages).forEach((file) => transfer.items.add(file));
                imageInput.files = transfer.files;
                window.alert('Puedes subir hasta ' + maxImages + ' imágenes por caso.');
            }
            renderPreviews(imageInput.files);
        });
    }

    $$('[data-file-drop]').forEach((drop) => {
        const input = $('[data-image-input]', drop);
        if (!input) return;
        ['dragenter', 'dragover'].forEach((eventName) => {
            drop.addEventListener(eventName, (event) => {
                event.preventDefault();
                drop.classList.add('is-dragging');
            });
        });
        ['dragleave', 'drop'].forEach((eventName) => {
            drop.addEventListener(eventName, (event) => {
                event.preventDefault();
                drop.classList.remove('is-dragging');
            });
        });
        drop.addEventListener('drop', (event) => {
            if (!event.dataTransfer || !event.dataTransfer.files.length) return;
            const transfer = new DataTransfer();
            Array.from(event.dataTransfer.files).slice(0, maxImages).forEach((file) => {
                if (file.type.startsWith('image/')) transfer.items.add(file);
            });
            input.files = transfer.files;
            renderPreviews(input.files);
        });
    });

    // ---------------------------------------------------------------------
    // Reacciones «Me resultó útil» sin recargar
    // ---------------------------------------------------------------------
    $$('[data-reaction-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = $('[data-reaction-button]', form);
            const label = $('[data-reaction-label]', form);
            const count = $('[data-reaction-count]', form);
            if (!button) return;
            button.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                if (!response.ok) throw new Error('No se pudo registrar');
                const data = await response.json();
                if (count) count.textContent = String(data.count);
                if (label) label.textContent = data.active ? 'Marcado como útil' : 'Me resultó útil';
                button.classList.toggle('btn-primary', !!data.active);
                button.classList.toggle('btn-outline', !data.active);
            } catch (error) {
                form.submit();
            } finally {
                button.disabled = false;
            }
        });
    });

    // ---------------------------------------------------------------------
    // Apoyo diagnóstico
    // ---------------------------------------------------------------------
    const diagnosticForm = $('[data-diagnostic-form]');
    if (diagnosticForm) {
        const maxSigns = 20;
        const countEl = $('[data-selected-count]', diagnosticForm);
        const chipsEl = $('[data-selected-chips]', diagnosticForm);
        const checkboxes = $$('[data-symptom]', diagnosticForm);

        const selected = () => checkboxes.filter((checkbox) => checkbox.checked);

        const renderChips = () => {
            if (!chipsEl) return;
            const checked = selected();
            chipsEl.innerHTML = '';
            if (!checked.length) {
                const empty = document.createElement('span');
                empty.className = 'muted';
                empty.textContent = 'Aún no has seleccionado síntomas.';
                chipsEl.appendChild(empty);
                return;
            }
            checked.forEach((checkbox) => {
                const label = checkbox.closest('.symptom-option');
                const name = label ? $('span', label)?.textContent?.trim() : 'Signo';
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'tag';
                chip.textContent = name + ' ×';
                chip.setAttribute('aria-label', 'Quitar ' + name);
                chip.addEventListener('click', () => {
                    checkbox.checked = false;
                    update();
                });
                chipsEl.appendChild(chip);
            });
        };

        const update = () => {
            const checked = selected();
            if (countEl) countEl.textContent = String(checked.length);
            if (checked.length > maxSigns) {
                const last = checked[checked.length - 1];
                last.checked = false;
                window.alert('Puedes seleccionar hasta ' + maxSigns + ' síntomas. Quita alguno para agregar otro.');
            }
            renderChips();
        };

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', update));
        update();

        const filterInput = $('[data-symptom-filter]', diagnosticForm);
        if (filterInput) {
            const normalize = (value) => value.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            filterInput.addEventListener('input', () => {
                const term = normalize(filterInput.value.trim());
                $$('[data-symptom-group]', diagnosticForm).forEach((group) => {
                    let visible = 0;
                    $$('[data-symptom-option]', group).forEach((option) => {
                        const match = !term || normalize(option.getAttribute('data-symptom-label') || '').includes(term);
                        option.hidden = !match;
                        if (match) visible++;
                    });
                    group.hidden = visible === 0;
                });
            });
        }

        diagnosticForm.addEventListener('submit', (event) => {
            if (selected().length === 0) {
                event.preventDefault();
                window.alert('Selecciona al menos un síntoma clínico para calcular los diferenciales.');
            }
        });
    }


    // ---------------------------------------------------------------------
    // Filtro del glosario
    // ---------------------------------------------------------------------
    const glossaryFilter = $('[data-glossary-filter]');
    const glossaryList = $('[data-glossary-list]');
    const glossaryEmpty = $('[data-glossary-empty]');
    if (glossaryFilter && glossaryList) {
        const normalize = (value) => value.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        glossaryFilter.addEventListener('input', () => {
            const term = normalize(glossaryFilter.value.trim());
            let visible = 0;
            $$('[data-glossary-item]', glossaryList).forEach((item) => {
                const match = !term || normalize(item.getAttribute('data-glossary-label') || '').includes(term);
                item.hidden = !match;
                if (match) visible++;
            });
            if (glossaryEmpty) glossaryEmpty.hidden = visible !== 0;
        });
    }

    // ---------------------------------------------------------------------
    // Confirmación de formularios marcados
    // ---------------------------------------------------------------------
    $$('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.getAttribute('data-confirm') || '¿Continuar?')) {
                event.preventDefault();
            }
        });
    });

    // ---------------------------------------------------------------------
    // Sombra del header al hacer scroll
    // ---------------------------------------------------------------------
    const header = $('[data-header]');
    if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 12);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();

// IA de emergencia: ejemplos que rellenan el textarea.
document.querySelectorAll('[data-ai-example]').forEach((button) => {
    button.addEventListener('click', () => {
        const form = button.closest('[data-ai-form]');
        const textarea = form ? form.querySelector('[data-ai-text]') : null;
        if (!textarea) return;
        textarea.value = button.getAttribute('data-ai-example') || '';
        textarea.focus();
    });
});

// Mostrar u ocultar contraseña sin perder el foco del campo.
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const wrapper = button.closest('.password-field');
        const input = wrapper ? wrapper.querySelector('input') : null;
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        button.setAttribute('title', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
        input.focus({ preventScroll: true });
    });
});

// Vista previa de la foto de perfil antes de subirla.
document.querySelectorAll('[data-avatar-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        const filename = document.querySelector('[data-avatar-filename]');
        if (filename) filename.textContent = file ? file.name : 'JPG, PNG o WebP · máx. 5 MB';
        const preview = document.querySelector('[data-avatar-preview]');
        if (!file || !preview) return;
        const reader = new FileReader();
        reader.addEventListener('load', () => {
            const image = document.createElement('img');
            image.className = 'avatar avatar-lg';
            image.src = String(reader.result);
            image.alt = 'Vista previa de la foto de perfil';
            preview.replaceChildren(image);
        });
        reader.readAsDataURL(file);
    });
});

// Menú lateral del panel de campo.
document.querySelectorAll('[data-panel-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const sidebar = document.getElementById(button.getAttribute('aria-controls') || 'panel-sidebar');
        if (!sidebar) return;
        const open = sidebar.classList.toggle('is-open');
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
});

// Imprimir receta o comprobante.
document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

// Total de una venta de la parcela.
document.querySelectorAll('[data-sale-form]').forEach((form) => {
    const rows = Array.from(form.querySelectorAll('[data-sale-row]'));
    const totalNode = form.querySelector('[data-sale-total]');
    const update = () => {
        let total = 0;
        rows.forEach((row) => {
            const input = row.querySelector('[data-sale-input]');
            const qty = row.querySelector('[data-sale-quantity]');
            const priceNode = row.querySelector('[data-sale-price]');
            const subtotalNode = row.querySelector('[data-sale-subtotal]');
            const option = input && input.options[input.selectedIndex];
            const price = option ? Number(option.getAttribute('data-price') || 0) : 0;
            const quantity = qty ? Math.max(0, Number(qty.value || 0)) : 0;
            const subtotal = price * quantity;
            total += subtotal;
            if (priceNode) priceNode.textContent = '$' + price.toFixed(2);
            if (subtotalNode) subtotalNode.textContent = '$' + subtotal.toFixed(2);
        });
        if (totalNode) totalNode.textContent = '$' + total.toFixed(2);
    };
    form.addEventListener('change', update);
    form.addEventListener('input', update);
    update();
});

// Filtro del directorio de revistas.
document.querySelectorAll('[data-journal-search]').forEach((input) => {
    input.addEventListener('input', () => {
        const query = input.value.toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('[data-journal-card]').forEach((card) => {
            const matches = query === '' || (card.getAttribute('data-journal-text') || '').includes(query);
            card.hidden = !matches;
            if (matches) visible += 1;
        });
        document.querySelectorAll('[data-journal-group]').forEach((group) => {
            const hasVisible = Array.from(group.querySelectorAll('[data-journal-card]')).some((card) => !card.hidden);
            group.hidden = !hasVisible;
        });
        const empty = document.querySelector('[data-journal-empty]');
        if (empty) empty.hidden = visible !== 0;
    });
});
