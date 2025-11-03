const viewTaskInfo = document.querySelector('.view-task-info')
const editTaskButton = viewTaskInfo?.querySelector('#edit_task_button')
const editTaskModalTemplate = document.querySelector('#edit_task_modal_template')

if (!editTaskButton) {
    console.error('Edit Task button not found.')
}

if (!editTaskModalTemplate) {
    console.error('Edit Task modal template not found.')
}

editTaskButton?.addEventListener('click', e => {
    e.preventDefault()

    if (!editTaskModalTemplate) {
        console.error('Edit Task modal template not found.')
        Dialog.somethingWentWrong()
        return
    }

    editTaskModalTemplate.classList.remove('no-display')
    editTaskModalTemplate.classList.add('flex-col')
})

editTaskButton?.addEventListener('click', e => {
    e.preventDefault()

    if (!editTaskModalTemplate) {
        console.error('Edit Task modal template not found.')
        Dialog.somethingWentWrong()
        return
    }

    editTaskModalTemplate.classList.remove('no-display')
    editTaskModalTemplate.classList.add('flex-col')
})
