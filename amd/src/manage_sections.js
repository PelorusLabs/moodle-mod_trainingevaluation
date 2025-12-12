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
 * JavaScript for managing sections in training evaluation.
 *
 * @module      mod_trainingevaluation/manage_sections
 * @copyright   Pelorus Labs
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';

export const init = (trainingevaluationId) => {
    initAddSectionButton(trainingevaluationId);
    initAddSubsectionButtons(trainingevaluationId);
    initAddItemButtons(trainingevaluationId);

    initEditSectionButtons(trainingevaluationId);
    initEditItemButtons(trainingevaluationId);

    initDeleteSectionButtons(trainingevaluationId);
    initDeleteItemButtons(trainingevaluationId);

    initReorderSectionButtons();
    initReorderItemButtons();
};

/**
 * Initialise the add section button.
 *
 * @param {number} trainingevaluationId
 */
const initAddSectionButton = (trainingevaluationId) => {
    const addSectionButton = document.getElementById('mod-trainingevaluation-add-section-btn');

    if (!addSectionButton) {
        return;
    }

    addSectionButton.addEventListener('click', () => {
        showSectionModal(trainingevaluationId);
    });
};

/**
 * Initialise "Add Subsection" buttons
 *
 * @param {number} trainingevaluationId
 */
const initAddSubsectionButtons = (trainingevaluationId) => {
    document.querySelectorAll('.mod-trainingevaluation-add-section-btn').forEach(button => {
        button.addEventListener('click', () => {
            const parentSectionId = button.dataset.parentSectionId;
            showSectionModal(trainingevaluationId, parentSectionId);
        });
    });
};

/**
 * Initialise add item buttons
 *
 * @param {number} trainingevaluationId
 */
const initAddItemButtons = (trainingevaluationId) => {
    document.querySelectorAll('.mod-trainingevaluation-add-item-btn').forEach(button => {
        button.addEventListener('click', () => {
            const sectionId = button.dataset.sectionId;
            showItemModal(trainingevaluationId, sectionId);
        });
    });
};

/**
 * Initialise edit item buttons
 *
 * @param {number} trainingevaluationId
 */
const initEditItemButtons = (trainingevaluationId) => {
    document.querySelectorAll('.mod-trainingevaluation-edit-item-btn').forEach(button => {
        button.addEventListener('click', e => {
            e.stopPropagation(); // Prevent triggering the section toggle
            const itemId = parseInt(button.dataset.itemId);
            showEditItemModal(trainingevaluationId, itemId);
        });
    });
};

/**
 * Show the modal for editing an item
 *
 * @param {number} trainingevaluationId
 * @param {number} itemId
 */
const showEditItemModal = async (trainingevaluationId, itemId) => {
    try {
        const itemData = await getItemData(itemId);

        const title = await getString('edititem', 'trainingevaluation');

        const {html, js} = await renderTypeConfig(itemData.type, itemData.config || {});

        // Create the modal with existing item data
        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: Templates.render('mod_trainingevaluation/edit_item_modal', {
                name: itemData.name,
                description: itemData.description,
                isrequired: itemData.isrequired,
                typeConfigHtml: html
            })
        });

        // Run the JS in the modal body after it's rendered so any JS needing to modify the DOM can run.
        modal.getRoot().on(ModalEvents.bodyRendered, () => {
            if (js) {
                Templates.runTemplateJS(js);
            }
        });

        modal.getRoot().on(ModalEvents.save, e => {
            e.preventDefault();

            const itemNameInput = modal.getRoot().find('#edititemname');
            const itemName = itemNameInput.val().trim();

            if (!itemName) {
                Notification.alert('', getString('enteritemname', 'trainingevaluation'));
                return;
            }

            const itemDescriptionInput = modal.getRoot().find('#edititemdescription');
            const itemDescription = itemDescriptionInput.val().trim();

            const itemIsRequired = modal.getRoot().find('#edititemisrequired').is(':checked');

            const config = collectTypeConfig(itemData.type, modal.getRoot());

            updateItem(itemId, itemName, itemDescription, itemIsRequired, null, config)
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Render the type-specific configuration template
 *
 * @param {string} itemType - The item type
 * @param {object} config - The item configuration
 * @return {Promise<{html: *, js: *}>} - The rendered HTML
 */
const renderTypeConfig = async (itemType, config) => {
    const templateName = `mod_trainingevaluation/item_config_${itemType}`;

    try {
        const {html, js} = await Templates.renderForPromise(templateName, config);

        return {html, js};
    } catch (error) {
        return '';
    }
};

/**
 * Collect type-specific configuration from the form
 *
 * @param {string} itemType - The item type
 * @param {jQuery} modalRoot - The modal root element
 * @return {object} - The configuration object
 */
const collectTypeConfig = (itemType, modalRoot) => {
    const config = {};

    switch (itemType) {
        case 'textinput': {
            const maxLength = modalRoot.find('#editmaxlength').val();
            const rows = modalRoot.find('#editrows').val();
            const placeholdertext = modalRoot.find('#editplaceholdertext').val();
            if (maxLength) {
                config.maxlength = maxLength;
            }
            if (placeholdertext) {
                config.placeholdertext = placeholdertext;
            }
            if (rows) {
                config.rows = rows;
            }
            break;
        }

        case 'selectmenu': {
            const options = modalRoot.find('#editselectmenuoptions').val();
            if (options) {
                config.options = JSON.parse(options);
            }
            break;
        }

        case 'fileupload': {
            const filetypes = modalRoot.find('#editfiletypes').val();
            if (filetypes) {
                config.filetypes = filetypes;
            }
            break;
        }
    }

    return config;
};

/**
 * Update an item via AJAX
 *
 * @param {number} id
 * @param {string} name
 * @param {string} description
 * @param {bool} isrequired
 * @param {string|null} movement direction of movement
 * @param {object|null} config item type config
 * @returns {Promise<void>}
 */
const updateItem = async (id, name, description, isrequired, movement = null, config = null) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_trainingevaluation_update_item',
            args: {
                id: id,
                name: name,
                description: description,
                isrequired: isrequired,
                movement: movement,
                config: config !== null ? JSON.stringify(config) : null
            }
        }]);

        return response[0];
    } catch (error) {
        Notification.exception(error);
        throw error;
    }
};

/**
 * Initialise edit section buttons
 *
 * @param {number} trainingevaluationId
 */
const initEditSectionButtons = (trainingevaluationId) => {
    document.querySelectorAll('.mod-trainingevaluation-edit-section-btn').forEach(button => {
        button.addEventListener('click', e => {
            e.stopPropagation(); // Prevent triggering the section toggle
            const sectionId = parseInt(button.dataset.sectionId);
            showEditSectionModal(trainingevaluationId, sectionId);
        });
    });
};

/**
 * Show the modal for editing a section
 *
 * @param {number} trainingevaluationId training evaluation instance ID
 * @param {number} sectionId section ID to edit
 */
const showEditSectionModal = async (trainingevaluationId, sectionId) => {
    try {
        const sectionData = await getSectionData(sectionId);

        const title = await getString('editsection', 'trainingevaluation');

        // Create the modal with existing section data
        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: Templates.render('mod_trainingevaluation/edit_section_modal', {
                sectionName: sectionData.name
            })
        });

        // Populate the form fields when modal is ready
        modal.getRoot().on(ModalEvents.shown, () => {
            const sectionNameInput = modal.getRoot().find('#editsectionname');
            sectionNameInput.val(sectionData.name);
            sectionNameInput.focus();
        });

        modal.getRoot().on(ModalEvents.save, e => {
            e.preventDefault();

            const sectionNameInput = modal.getRoot().find('#editsectionname');
            const sectionName = sectionNameInput.val().trim();

            if (!sectionName) {
                Notification.alert('', 'Please enter a section name');
                return;
            }

            updateSection(sectionId, sectionName)
                .then(() => {
                    modal.destroy();
                    // Update the section name in the DOM without reloading
                    updateSectionNameInDOM(sectionId, sectionName);
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Initialise delete section buttons
 *
 * @param {number} trainingevaluationId
 */
const initDeleteSectionButtons = (trainingevaluationId) => {
    document.querySelectorAll('.mod-trainingevaluation-delete-section-btn').forEach(button => {
        button.addEventListener('click', e => {
            e.stopPropagation(); // Prevent triggering the section toggle
            const sectionId = parseInt(button.dataset.sectionId);
            showDeleteSectionModal(trainingevaluationId, sectionId);
        });
    });
};

/**
 * Show the modal for deleting a section
 *
 * @param {number} trainingevaluationId training evaluation instance ID
 * @param {number} sectionId section ID to edit
 */
const showDeleteSectionModal = async (trainingevaluationId, sectionId) => {
    try {
        const sectionData = await getSectionData(sectionId);

        const title = await getString('deletesection', 'trainingevaluation');

        // Create the modal with existing section data
        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: Templates.render('mod_trainingevaluation/delete_section_modal', {
                sectionName: sectionData.name
            })
        });

        modal.getRoot().on(ModalEvents.save, e => {
            e.preventDefault();

            deleteSection(sectionId)
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Initialise delete item buttons
 *
 * @param {number} trainingevaluationId
 */
const initDeleteItemButtons = (trainingevaluationId) => {
    document.querySelectorAll('.mod-trainingevaluation-delete-item-btn').forEach(button => {
        button.addEventListener('click', e => {
            e.stopPropagation(); // Prevent triggering the section toggle
            const itemId = parseInt(button.dataset.itemId);
            showDeleteItemModal(trainingevaluationId, itemId);
        });
    });
};

/**
 * Show the modal for deleting an item
 *
 * @param {number} trainingevaluationId
 * @param {number} itemId
 */
const showDeleteItemModal = async (trainingevaluationId, itemId) => {
    try {
        const itemData = await getItemData(itemId);

        const title = await getString('deleteitem', 'trainingevaluation');

        // Create the modal with existing item data
        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: Templates.render('mod_trainingevaluation/delete_item_modal', {
                itemName: itemData.name
            })
        });

        modal.getRoot().on(ModalEvents.save, e => {
            e.preventDefault();
            deleteItem(itemId)
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });

        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Initialise reorder section buttons
 */
const initReorderSectionButtons = () => {
    document.querySelectorAll('.mod-trainingevaluation-up-section-btn').forEach(button => {
        button.addEventListener('click', () => {
            const sectionId = parseInt(button.dataset.sectionId);
            updateSection(sectionId, null, 'up')
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });
    });
    document.querySelectorAll('.mod-trainingevaluation-down-section-btn').forEach(button => {
        button.addEventListener('click', () => {
            const sectionId = parseInt(button.dataset.sectionId);
            updateSection(sectionId, null, 'down')
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });
    });
};

/**
 * Initialise reorder item buttons
 */
const initReorderItemButtons = () => {
    document.querySelectorAll('.mod-trainingevaluation-up-item-btn').forEach(button => {
        button.addEventListener('click', () => {
            const itemId = parseInt(button.dataset.itemId);
            updateItem(itemId, null, null, null, 'up')
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });
    });
    document.querySelectorAll('.mod-trainingevaluation-down-item-btn').forEach(button => {
        button.addEventListener('click', () => {
            const itemId = parseInt(button.dataset.itemId);
            updateItem(itemId, null, null, null, 'down')
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });
    });
};

/**
 * Get item data
 *
 * @param {number} itemId
 * @returns {Promise<*>}
 */
const getItemData = async (itemId) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_trainingevaluation_get_item',
            args: {
                id: itemId
            }
        }])[0];

        if (response.config !== null) {
            response.config = JSON.parse(response.config);
        }

        return response;
    } catch (error) {
        Notification.exception(error);
        throw error;
    }
};

/**
 * Delete an item via AJAX
 *
 * @param {number} itemId
 * @returns {*}
 */
const deleteItem = (itemId) => {
    return Ajax.call([{
        methodname: 'mod_trainingevaluation_delete_item',
        args: {
            id: itemId
        }
    }])[0];
};

/**
 * Get section data for editing
 *
 * @param {number} sectionId section ID to get data for
 * @return {Promise<Object>} promise resolving to section data
 */
const getSectionData = async (sectionId) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_trainingevaluation_get_section',
            args: {
                id: sectionId
            }
        }]);

        return response[0];
    } catch (error) {
        Notification.exception(error);
        throw error;
    }
};

/**
 * Update a section via AJAX
 *
 * @param {number} sectionId section ID to update
 * @param {string} sectionName new section name
 * @param {string|null} movement direction of movement
 * @return {Promise} Promise resolving when a section is updated
 */
const updateSection = async (sectionId, sectionName, movement = null) => {
    try {
        const response = await Ajax.call([{
            methodname: 'mod_trainingevaluation_update_section',
            args: {
                id: sectionId,
                name: sectionName,
                movement: movement
            }
        }]);

        return response[0];
    } catch (error) {
        Notification.exception(error);
        throw error;
    }
};

/**
 * Update the section name in the DOM without requiring a page reload
 *
 * @param {number} sectionId section ID
 * @param {string} sectionName new section name
 */
const updateSectionNameInDOM = (sectionId, sectionName) => {
    // Find the section element
    const sectionElement = document.querySelector(`.mod-trainingevaluation-section[data-section-id="${sectionId}"]`);
    if (sectionElement) {
        // Find and update the title element
        const titleElement = sectionElement.querySelector('.mod-trainingevaluation-section-title');
        if (titleElement) {
            titleElement.textContent = sectionName;
        }
    }
};

/**
 * Delete the section
 *
 * @param {number} sectionId
 */
const deleteSection = (sectionId) => {
    return Ajax.call([{
        methodname: 'mod_trainingevaluation_delete_section',
        args: {
            id: sectionId
        }
    }])[0];
};

/**
 * Show the modal for adding a section or subsection
 *
 * @param {number} trainingevaluationId
 * @param {number|null} parentSectionId parent section ID (for subsections) or null (for top-level sections)
 */
const showSectionModal = async (trainingevaluationId, parentSectionId = null) => {
    try {
        // Get the appropriate title string
        const titleKey = parentSectionId ? 'addsubsection' : 'addsection';
        const title = await getString(titleKey, 'trainingevaluation');

        // Create the modal
        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: title,
            body: Templates.render('mod_trainingevaluation/add_section_modal', {isSubsection: !!parentSectionId})
        });

        // When the save button is clicked
        modal.getRoot().on(ModalEvents.save, e => {
            e.preventDefault();

            const sectionNameInput = modal.getRoot().find('#newsectionname');
            const sectionName = sectionNameInput.val().trim();

            if (!sectionName) {
                // Show error if section name is empty
                Notification.alert(
                    getString('error', 'core'),
                    getString('sectionnamerequired', 'trainingevaluation')
                );
                return;
            }

            // Save the new section via AJAX
            saveNewSection(trainingevaluationId, sectionName, parentSectionId)
                .then(() => {
                    // Reload the page to show the new section
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });

        // Display the modal
        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Show the modal for adding an item to a section
 *
 * @param {number} trainingevaluationId
 * @param {number} sectionId section ID to add the item to
 */
const showItemModal = async (trainingevaluationId, sectionId) => {
    try {
        // Create the modal
        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: await getString('additem', 'trainingevaluation'),
            body: Templates.render('mod_trainingevaluation/add_item_modal', {})
        });

        // When the save button is clicked
        modal.getRoot().on(ModalEvents.save, e => {
            e.preventDefault();

            const itemNameInput = modal.getRoot().find('#newitemname');
            const itemName = itemNameInput.val().trim();
            const itemDescriptionInput = modal.getRoot().find('#newitemdescription');
            const itemDescription = itemDescriptionInput.val().trim();
            const itemIsRequired = modal.getRoot().find('#newitemisrequired').is(':checked');
            const itemTypeInput = modal.getRoot().find('#newitemtype');
            const itemType = itemTypeInput.val().trim();

            if (!itemName || !itemType) {
                // Show error if item name is empty
                Notification.alert(
                    getString('error', 'core'),
                    getString('itemnamerequired', 'trainingevaluation')
                );
                return;
            }

            // Save the new item via AJAX
            saveNewItem(trainingevaluationId, sectionId, itemName, itemDescription, itemIsRequired, itemType)
                .then(() => {
                    // Reload the page to show the new item
                    window.location.reload();
                })
                .catch(error => {
                    Notification.exception(error);
                });
        });

        // Display the modal
        modal.show();
    } catch (error) {
        Notification.exception(error);
    }
};

/**
 * Save a new section via AJAX
 *
 * @param {number} trainingevaluationId
 * @param {string} sectionName name for the new section
 * @param {number|null} parentSectionId parent section ID (for subsections) or null (for top-level sections)
 * @returns {Promise} A promise that resolves when the section is saved
 */
const saveNewSection = (trainingevaluationId, sectionName, parentSectionId = null) => {
    return Ajax.call([{
        methodname: 'mod_trainingevaluation_add_section',
        args: {
            wtid: trainingevaluationId,
            name: sectionName,
            parentsection: parentSectionId
        }
    }])[0];
};

/**
 * Save a new item via AJAX
 *
 * @param {number} trainingevaluationId
 * @param {number} sectionId section ID to add the item to
 * @param {string} name name for the new item
 * @param {string} description description for the new item
 * @param {boolean} isrequired whether the item is required for completion
 * @param {string} type type of the new item
 * @returns {Promise} A promise that resolves when the item is saved
 */
const saveNewItem = (trainingevaluationId, sectionId, name, description, isrequired, type) => {
    return Ajax.call([{
        methodname: 'mod_trainingevaluation_add_item',
        args: {
            wtid: trainingevaluationId,
            sectionid: sectionId,
            name: name,
            description: description,
            isrequired: isrequired,
            type: type
        }
    }])[0];
};
