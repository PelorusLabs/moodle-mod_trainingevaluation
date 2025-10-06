// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JavaScript for select menu option configuration.
 *
 * @module      mod_trainingevaluation/item_config_selectmenu
 * @copyright   Pelorus Labs
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';

/**
 * Listen for DOM changes to initialize the select menu configuration interface when ready.
 */
export const init = () => {
    const initWhenReady = () => {
        const container = document.getElementById('selectmenu-options-container');
        const addButton = document.getElementById('selectmenu-add-option');
        const hiddenInput = document.getElementById('editselectmenuoptions');

        if (container && addButton && hiddenInput) {
            initSelectMenu(container, addButton, hiddenInput);
            return true;
        }
        return false;
    };

    // Try immediate initialization
    if (initWhenReady()) {
        return;
    }

    // If not ready, watch for DOM changes
    const observer = new MutationObserver((mutations, obs) => {
        if (initWhenReady()) {
            obs.disconnect();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Fallback timeout to stop observing after 5 seconds
    setTimeout(() => observer.disconnect(), 5000);
};

/**
 * Initialize the select menu configuration interface
 *
 * @param {HTMLElement} container
 * @param {HTMLElement} addButton
 * @param {HTMLElement} hiddenInput
 */
const initSelectMenu = (container, addButton, hiddenInput) => {
    if (!container || !addButton || !hiddenInput) {
        return;
    }

    addButton.addEventListener('click', async () => {
        if (anyEmptyInputs()) {
            return;
        }
        const optionRow = document.createElement('div');
        optionRow.className = 'input-group mb-2 selectmenu-option-row';

        const placeholder = await getString('enteroption', 'mod_trainingevaluation');

        optionRow.innerHTML = `
            <input type="text" class="form-control selectmenu-option-input"
                   value="" placeholder="${placeholder}" />
            <div class="input-group-append">
                <button type="button" class="btn btn-danger selectmenu-remove-option">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        `;

        container.appendChild(optionRow);

        // Focus the new input
        optionRow.querySelector('.selectmenu-option-input').focus();

        // Attach remove handler
        optionRow.querySelector('.selectmenu-remove-option')
            .addEventListener('click', () => removeOption(optionRow));

        updateHiddenInput();
    });

    const removeOption = (row) => {
        // Keep at least one option row
        const rows = container.querySelectorAll('.selectmenu-option-row');
        if (rows.length > 1) {
            row.remove();
            updateHiddenInput();
        } else {
            // Just clear the input if it's the last one
            row.querySelector('.selectmenu-option-input').value = '';
            updateHiddenInput();
        }
    };

    const anyEmptyInputs = () => {
        const inputs = container.querySelectorAll('.selectmenu-option-input');
        return Array.from(inputs).some(input => input.value.trim() === '');
    };

    const updateHiddenInput = () => {
        const inputs = container.querySelectorAll('.selectmenu-option-input');
        const options = [];

        inputs.forEach((input, i) => {
            const value = input.value.trim();
            if (value !== '') {
                options.push({'id': i, 'value': value});
            }
        });

        hiddenInput.value = JSON.stringify(options);
    };

    // Attach remove handlers to existing rows
    container.querySelectorAll('.selectmenu-remove-option').forEach(button => {
        button.addEventListener('click', (e) => {
            const row = e.target.closest('.selectmenu-option-row');
            removeOption(row);
        });
    });

    // Update hidden input on any input change
    container.addEventListener('input', (e) => {
        if (e.target.classList.contains('selectmenu-option-input')) {
            updateHiddenInput();
        }
    });

    // Initial update
    updateHiddenInput();
};