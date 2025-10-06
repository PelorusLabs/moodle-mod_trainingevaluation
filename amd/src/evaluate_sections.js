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
 * JavaScript for a user evaluating a training evaluation instance.
 *
 * @module      mod_trainingevaluation/manage_sections
 * @copyright   Pelorus Labs
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string as getString} from 'core/str';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';

export const init = (trainingevaluationId, userId) => {
    initTextInputForms(userId);
    initSelectMenuForms(userId);
    initDatePickerForms(userId);
    initFinaliseButton(trainingevaluationId, userId);
    initNewEvaluation(trainingevaluationId, userId);
};

/**
 * Initialise the select menu forms.
 *
 * @param {number} userId The user ID
 */
const initSelectMenuForms = (userId) => {
    document.querySelectorAll('.mod-trainingevaluation-selectmenu-option-label').forEach(label => {
        const itemContainer = label.closest('[data-item-id]');
        if (!itemContainer) {
            return;
        }
        const itemId = parseInt(itemContainer.dataset.itemId);

        label.addEventListener('click', () => {
            const select = document.getElementById(label.getAttribute('for'));
            const optionId = select.dataset.optionId;
            saveResponse(itemId, userId, optionId);
        });
    });
};

/**
 * Initialise the text input forms.
 *
 * @param {number} userId The user ID
 */
const initTextInputForms = (userId) => {
    document.querySelectorAll('.mod-trainingevaluation-textinput-form').forEach(textarea => {
        const itemContainer = textarea.closest('[data-item-id]');
        if (!itemContainer) {
            return;
        }

        const itemId = parseInt(itemContainer.dataset.itemId);

        let saveTimeout = null;

        // Save on input with debounce (wait for user to stop typing)
        textarea.addEventListener('input', () => {
            if (saveTimeout) {
                clearTimeout(saveTimeout);
            }

            // Set a new timeout to save after no typing
            saveTimeout = setTimeout(() => {
                if (textarea.value !== textarea.dataset.originalValue) {
                    saveResponse(itemId, userId, textarea.value);
                    textarea.dataset.originalValue = textarea.value;
                }
            }, 500);
        });

        // Also save on blur (when field loses focus)
        textarea.addEventListener('blur', () => {
            // Clear the debounce timeout
            if (saveTimeout) {
                clearTimeout(saveTimeout);
                saveTimeout = null;
            }

            if (textarea.value !== textarea.dataset.originalValue) {
                saveResponse(itemId, userId, textarea.value);
                textarea.dataset.originalValue = textarea.value;
            }
        });

        textarea.dataset.originalValue = textarea.value;
    });
};

/**
 * Initialise the select menu forms.
 *
 * @param {number} userId The user ID
 */
const initDatePickerForms = (userId) => {
    document.querySelectorAll('.mod-trainingevaluation-datepicker-form').forEach(datepicker => {
        const itemContainer = datepicker.closest('[data-item-id]');
        if (!itemContainer) {
            return;
        }
        const itemId = parseInt(itemContainer.dataset.itemId);

        datepicker.addEventListener('change', (event) => {
            saveResponse(itemId, userId, event.target.value);
        });
    });
};

/**
 * Save a response via AJAX
 *
 * @param {number} itemId The item ID
 * @param {number} userId The user ID
 * @param {string} responseData The response data
 * @returns {Promise}
 */
const saveResponse = async (itemId, userId, responseData) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_trainingevaluation_save_response',
            args: {
                itemid: itemId,
                userid: userId,
                response: responseData
            }
        }])[0];
        if (response.error) {
            throw new Error(response.exception);
        }
        showSaveFeedback(itemId, true);

    } catch (error) {
        showSaveFeedback(itemId, false);
        Notification.exception(error);
    }
};

/**
 * Show visual feedback when saving
 *
 * @param {number} itemId - The item ID
 * @param {boolean} success - Whether the save was successful
 */
const showSaveFeedback = async (itemId, success) => {
    const itemContainer = document.querySelector(`[data-item-id="${itemId}"]`);
    if (!itemContainer) {
        return;
    }

    const contentElement = itemContainer.querySelector('.mod-trainingevaluation-item-content');
    if (!contentElement) {
        return;
    }

    const existingFeedback = itemContainer.querySelector('.mod-trainingevaluation-save-feedback');
    if (existingFeedback) {
        existingFeedback.remove();
    }

    const feedbackSpan = document.createElement('span');
    feedbackSpan.className =
        'mod-trainingevaluation-save-feedback mod-trainingevaluation-save-feedback--' + (success ? 'success' : 'error');
    feedbackSpan.textContent = '';
    feedbackSpan.style.opacity = '1';

    if (success) {
        feedbackSpan.textContent = await getString('saved', 'mod_trainingevaluation');
    } else {
        feedbackSpan.textContent = await getString('errorsaving', 'mod_trainingevaluation');
    }

    contentElement.insertAdjacentElement('afterend', feedbackSpan);

    // Fade out and remove after a delay
    setTimeout(() => {
        feedbackSpan.style.opacity = '0';
        setTimeout(() => {
            feedbackSpan.remove();
        }, 500);
    }, success ? 1000 : 2500);
};

const initNewEvaluation = (trainingevaluationId, userId) => {
    const newEvaluationButton = document.getElementById('mod-trainingevaluation-new-evaluation-btn');
    if (!newEvaluationButton) {
        return;
    }

    newEvaluationButton.addEventListener('click', async() => {
        const confirmMessage = await getString('confirmnewevaluate', 'trainingevaluation');
        const confirmTitle = await getString('confirm', 'core');

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: confirmTitle,
            body: confirmMessage,
        });

        await modal.setSaveButtonText(confirmTitle);

        modal.getRoot().on(ModalEvents.save, async() => {
            try {
                const result = await Ajax.call([{
                    methodname: 'mod_trainingevaluation_new_evaluation',
                    args: {
                        wtid: trainingevaluationId,
                        userid: userId
                    }
                }])[0];
                if (result.version) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('version', result.version);
                    window.location.href = url.toString();
                } else {
                    Notification.alert('Error', result.message, 'OK');
                }
            } catch (error) {
                Notification.exception(error);
            }
        });

        modal.getRoot().on(ModalEvents.hidden, () => {
            modal.destroy();
        });

        modal.show();
    });
};

const initFinaliseButton = (trainingevaluationId, userId) => {
    const finaliseButton = document.getElementById('mod-trainingevaluation-finalise-btn');
    if (!finaliseButton) {
        return;
    }

    finaliseButton.addEventListener('click', async() => {
        const confirmMessage = await getString('confirmfinalise', 'trainingevaluation');
        const confirmTitle = await getString('confirm', 'core');

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: confirmTitle,
            body: confirmMessage,
        });

        await modal.setSaveButtonText(confirmTitle);

        modal.getRoot().on(ModalEvents.save, async() => {
            try {
                const result = await Ajax.call([{
                    methodname: 'mod_trainingevaluation_finalise_evaluation',
                    args: {
                        wtid: trainingevaluationId,
                        userid: userId
                    }
                }])[0];
                if (!result.error) {
                    window.location.reload();
                } else {
                    Notification.alert('Error', result.message, 'OK');
                }
            } catch (error) {
                Notification.exception(error);
            }
        });

        modal.getRoot().on(ModalEvents.hidden, () => {
            modal.destroy();
        });

        modal.show();
    });
};
