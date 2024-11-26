let loadedEggs = [],
    loadedEggsConfigurations = null,
    loadedTranslations = [];

document.addEventListener('DOMContentLoaded', function() {
    disableEggSelector(true);
    const nestSelector = document.querySelector('select.nest-selector'),
        eggSelector = document.querySelector('select.egg-selector');

    if (nestSelector) {
        nestSelector.addEventListener('change', loadEggs);
        nestSelector.dispatchEvent(new Event('change'));
    }

    if (eggSelector) {
        eggSelector.addEventListener('change', loadEggsData);
        eggSelector.dispatchEvent(new Event('change'));
    }
});

function loadEggs() {
    disableEggSelector(true);
    const eggSelector = document.querySelector('select.egg-selector');

    const nestId = this.value;
    if (!nestId) {
        eggSelector.tomselect.clear();
        eggSelector.tomselect.clearOptions();
        return;
    }

    fetch(`/index.php/panel/api/get-eggs/${nestId}`)
        .then(response => response.json())
        .then(data => {
            loadedEggs = data.eggs;
            loadedTranslations = data.translations;
            let preparedData = [];
            for (const [name, id] of Object.entries(data.choices)) {
                preparedData.push({value: id, text: name});
            }

            const currentSelectedOptions = eggSelector.tomselect?.getValue().map(value => parseInt(value));
            eggSelector.innerHTML = '';

            for (const [name, id] of Object.entries(data)) {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = name;
                eggSelector.appendChild(option);
            }

            if (eggSelector.tomselect) {
                eggSelector.tomselect.clear();
                eggSelector.tomselect.clearOptions();
                eggSelector.tomselect.addOptions(preparedData);
                eggSelector.tomselect.setValue(currentSelectedOptions);
            }

            setTimeout(() => disableEggSelector(false), 100);
        })
        .catch(error => console.error('Error fetching eggs:', error))
}

function loadEggsData() {
    clearEggsData();
    const eggSelector = document.querySelector('select.egg-selector');
    const eggIds = eggSelector.tomselect.getValue().map(value => parseInt(value));

    if (!eggIds.length) {
        return;
    }

    const container = document.createElement('div');
    container.className = 'nav-tabs-custom form-tabs-tablist eggs-tabs';
    const ul = document.createElement('ul');
    ul.className = 'nav nav-tabs eggs-data mt-3';
    container.appendChild(ul);
    const contentContainer = document.createElement('div');
    contentContainer.className = 'tab-content eggs-data-content';

    eggIds.forEach((eggId, index) => {
        const li = document.createElement('li'),
            a = document.createElement('a'),
            eggData = loadedEggs[eggId];
        if (!eggData) {
            return;
        }
        a.href = '#tab_' + eggId;
        a.textContent = eggData.name;
        a.dataset.bsToggle = 'tab';
        a.dataset.tab = 'tab';
        a.className = 'nav-link';
        if (index === 0) {
            a.classList.add('active');
        }
        li.appendChild(a);
        ul.appendChild(li);

        const tabContent = prepareEggTabContent(index, eggData);
        contentContainer.appendChild(tabContent);
    });

    eggSelector.parentElement.appendChild(container);
    eggSelector.parentElement.appendChild(contentContainer);
    setTimeout(() => loadSavedEggsConfiguration(), 1000);
}

function prepareEggTabContent(index, eggData) {
    const tabContent = document.createElement('div');
    tabContent.id = 'tab_' + eggData.id;
    tabContent.className = 'tab-pane';

    if (index === 0) {
        tabContent.classList.add('active');
    }

    tabContent.innerHTML = '<h4 class="mb-4 mt-4">' + eggData.name + ' - Configuration</h4>';
    tabContent.innerHTML += '<h5 class="mb-3 mt-4">Default configuration</h5>';
    tabContent.innerHTML += generateVariablesTable({
        0: {
            id: 'startup',
            name: 'Startup',
            description: 'Server startup command',
            default_value: eggData.startup,
            user_viewable: false,
            user_editable: false,
            egg_id: eggData.id
        },
        1: {
            id: 'docker_image',
            name: 'Docker image',
            description: 'Docker image used by this egg',
            default_value: eggData.docker_image,
            user_viewable: false,
            user_editable: false,
            egg_id: eggData.id,
            options: eggData.docker_images
        }
    })
    tabContent.innerHTML += '<h5 class="mb-3 mt-4">Variables</h5>';
    tabContent.innerHTML += generateVariablesTable(eggData?.relationships?.variables)

    tabContent.innerHTML += '<h4 class="mb-4 mt-4">' + eggData.name + ' - ' + loadedTranslations.egg_information + '</h4>';
    tabContent.innerHTML += '<div class="alert alert-info mb-4"><i class="fas fa-info-circle"></i> ' + loadedTranslations.alert + '</div>';
    tabContent.innerHTML += generateTableFromObject(eggData);
    return tabContent;
}

function generateTableFromObject(data){
    if (data === null || typeof data === "undefined") {
        data = {};
    }

    const table = document.createElement('table'),
        tbody = document.createElement('tbody');

    table.className = 'table table-bordered table-striped';

    for (const [key, value] of Object.entries(data)) {
        let preparedValue = value;
        if (typeof value === 'object') {
            preparedValue = generateTableFromObject(value);
        }
        const tr = document.createElement('tr'),
            th = document.createElement('th'),
            td = document.createElement('td');
        th.textContent = key;
        td.innerHTML = preparedValue;
        tr.appendChild(th);
        tr.appendChild(td);
        tbody.appendChild(tr);
    }

    table.appendChild(tbody);
    return table.outerHTML;
}

function generateVariablesTable(variables) {
    if (variables === null || typeof variables === "undefined") {
        return '';
    }

    const table = document.createElement('table'),
        thead = document.createElement('thead'),
        tbody = document.createElement('tbody'),
        tr = document.createElement('tr'),
        thName = document.createElement('th'),
        thDescription = document.createElement('th'),
        thValue = document.createElement('th'),
        thUserViewable = document.createElement('th'),
        thUserEditable = document.createElement('th');

    table.className = 'table table-bordered table-striped';
    thName.textContent = 'Nazwa';
    thDescription.textContent = 'Opis';
    thDescription.style.minWidth = '200px';
    thValue.textContent = 'Wartość';
    thValue.style.minWidth = '300px';
    thUserEditable.textContent = 'Edytowalne przez użytkownika';
    thUserViewable.textContent = 'Widoczne dla użytkownika';

    tr.appendChild(thName);
    tr.appendChild(thDescription);
    tr.appendChild(thValue);
    tr.appendChild(thUserViewable);
    tr.appendChild(thUserEditable);

    thead.appendChild(tr);
    table.appendChild(thead);

    for (const [index, value] of Object.entries(variables)) {
        const tr = document.createElement('tr'),
            tdName = document.createElement('td'),
            tdDescription = document.createElement('td'),
            tdValue = document.createElement('td'),
            tdUserViewable = document.createElement('td'),
            tdUserEditable = document.createElement('td');

        tr.dataset.id = value.id;
        tdName.textContent = value.name;
        tdDescription.textContent = value.description;
        tdValue.innerHTML = createInput((value.default_value || ''), value.egg_id, value.id, value.options, 'value');
        tdUserViewable.innerHTML = createCheckbox(value.user_viewable, value.egg_id, value.id, 'user_viewable');
        tdUserEditable.innerHTML = createCheckbox(value.user_editable, value.egg_id, value.id, 'user_editable');

        tr.appendChild(tdName);
        tr.appendChild(tdDescription);
        tr.appendChild(tdValue);
        tr.appendChild(tdUserViewable);
        tr.appendChild(tdUserEditable);

        tbody.appendChild(tr);
    }

    table.appendChild(tbody);
    return table.outerHTML;
}

function getInputName(eggId, variableId, name) {
    let inputName = `eggs_configuration[${eggId}]`;
    if (variableId && isNaN(variableId) === false) {
        inputName += `[variables]`;
    } else {
        inputName += '[options]';
    }
    inputName += `[${variableId}][${name}]`;
    return inputName;
}

function createInput(value, eggId, variableId, options, name) {
    if (options) {
        return createSelect(value, eggId, options, name);
    }
    return `<input type="text" value="${value}" name="${getInputName(eggId, variableId || 'startup', name)}" class="form-control">`;
}

function createCheckbox(checked, eggId, variableId, name) {
    return `<input type="checkbox" class="form-check-input" name="${getInputName(eggId, variableId, name)}" ${checked ? 'checked' : ''}>`;
}

function createSelect(value, eggId, options, name) {
    let select = `<select name="${getInputName(eggId, 'docker_image', name)}" class="form-control" style="font-size: 14px;">`;
    for (const [key, option] of Object.entries(options)) {
        select += `<option value="${option}" ${value === key ? 'selected' : ''}>${option}</option>`;
    }
    select += '</select>';
    return select;
}

function clearEggsData() {
    const container = document.querySelector('.eggs-data'),
        contentContainer = document.querySelector('.eggs-data-content'),
        eggsTabs = document.querySelector('.eggs-tabs');
    if (container) {
        container.remove();
    }
    if (contentContainer) {
        contentContainer.remove();
    }
    if (eggsTabs) {
        eggsTabs.remove();
    }
}

function disableEggSelector(disable = true) {
    const eggSelector = document.querySelector('select.egg-selector');
    if (!eggSelector) {
        return;
    }
    eggSelector.classList.toggle('disabled', disable);
    eggSelector.classList.toggle('locked', disable);
    if (disable) {
        eggSelector.tomselect.disable();
    } else {
        eggSelector.tomselect.enable();
    }
}

function loadSavedEggsConfiguration() {
    if (loadedEggsConfigurations !== null) {
        return;
    }

    const savedEggsConfigurations = document.querySelector('#Product_eggsConfiguration').value;
    let eggsConfigurationsToLoad;
    try {
        eggsConfigurationsToLoad = JSON.parse(savedEggsConfigurations);
    } catch (e) {
        eggsConfigurationsToLoad = null;
    }
    if (typeof eggsConfigurationsToLoad !== 'object' || eggsConfigurationsToLoad === null || Object.keys(eggsConfigurationsToLoad).length === 0) {
        return;
    }
    loadedEggsConfigurations = eggsConfigurationsToLoad;
    Object.entries(eggsConfigurationsToLoad).forEach(([eggId, configurations]) => {
       const inputName = `eggs_configuration[${eggId}]`;
       Object.entries(configurations.options).forEach(([name, value]) => {
            setConfigurationInputValue(inputName, 'options', name, value);
       });
       Object.entries(configurations.variables).forEach(([variableId, variable]) => {
          setConfigurationInputValue(inputName, 'variables', variableId, variable);
       });
    });
}

function setConfigurationInputValue(inputName, optionIndex, optionName, value) {
    const valueInput = document.querySelector(`input[name="${inputName}[${optionIndex}][${optionName}][value]"]`),
        viewableInput = document.querySelector(`input[name="${inputName}[${optionIndex}][${optionName}][user_viewable]"]`),
        editableInput = document.querySelector(`input[name="${inputName}[${optionIndex}][${optionName}][user_editable]"]`);
    if (valueInput) {
        valueInput.value = value.value;
    }
    if (viewableInput) {
        viewableInput.checked = value.user_viewable || false;
    }
    if (editableInput) {
        editableInput.checked = value.user_editable || false;
    }
}
