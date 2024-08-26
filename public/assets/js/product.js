document.addEventListener('DOMContentLoaded', function() {
    const nestSelector = document.querySelector('select.nest-selector');
    const eggSelector = document.querySelector('select.egg-selector');

    if (nestSelector) {
        nestSelector.addEventListener('change', function() {
            const nestId = this.value;
            if (!nestId) {
                eggSelector.tomselect.clear();
                eggSelector.tomselect.clearOptions();
                return;
            }

            fetch(`/index.php/panel/api/get-eggs/${nestId}`)
                .then(response => response.json())
                .then(data => {
                    let preparedData = [];
                    for (const [name, id] of Object.entries(data)) {
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
                })
                .catch(error => console.error('Error fetching eggs:', error));
        });
        nestSelector.dispatchEvent(new Event('change'));
    }
});
