let loadedEggs = [],
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
            // Clear existing options
            eggSelector.innerHTML = '';

            // Add new options
            for (const [name, id] of Object.entries(data)) {
                const option = document.createElement('option');
                option.value = id;
                option.textContent = name;
                eggSelector.appendChild(option);
            }

            // Update Tom Select instance
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
}

function prepareEggTabContent(index, eggData) {
    const tabContent = document.createElement('div');
    tabContent.id = 'tab_' + eggData.id;
    tabContent.className = 'tab-pane';
    if (index === 0) {
        tabContent.classList.add('active');
    }
    tabContent.innerHTML = '<h4 class="mb-4 mt-4">' + eggData.name + ' - ' + loadedTranslations.egg_information + '</h4>';
    tabContent.innerHTML += '<div class="alert alert-info mb-4"><i class="fas fa-info-circle"></i> ' + loadedTranslations.alert + '</div>';
    tabContent.innerHTML += generateTableFromObject(eggData);
    return tabContent;
}

function generateTableFromObject(data) {
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
    eggSelector.classList.toggle('disabled', disable);
    eggSelector.classList.toggle('locked', disable);
    if (disable) {
        eggSelector.tomselect.disable();
    } else {
        eggSelector.tomselect.enable();
    }
}
