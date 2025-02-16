document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('#Setting_value')
        .addEventListener('change', loadTemplateInfo)

    loadTemplateInfo()
})

function loadTemplateInfo() {
    const valueInput = document.querySelector('#Setting_value').tomselect,
        templateName = valueInput.getValue()

    fetch(`/index.php/panel/api/template/${templateName}`)
        .then(response => response.json())
        .then(data => {
            prepareTemplateTableContent(data)
        })
}

function prepareTemplateTableContent(data) {
    const row = document.createElement('div')
    row.classList.add('row')
    row.classList.add('col-12')
    row.classList.add('mt-3')
    row.classList.add('template-info')

    for([key, value] of Object.entries(data)) {
        const keyCol = document.createElement('div'),
            valueCol = document.createElement('div')

        keyCol.classList.add('col-3')
        keyCol.classList.add('fw-bolder')
        keyCol.innerHTML = `${key}:`

        valueCol.classList.add('col-9')
        valueCol.innerHTML = value

        row.appendChild(keyCol)
        row.appendChild(valueCol)
    }

    const formGroups = document.querySelectorAll('.form-group'),
        lastFormGroup = formGroups[formGroups.length - 1]

    document.querySelector('.template-info')?.remove()
    lastFormGroup.parentNode.after(row)
}

