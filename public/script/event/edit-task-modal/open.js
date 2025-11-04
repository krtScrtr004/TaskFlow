const viewTaskInfo = document.querySelector('.view-task-info')

const editTaskButton = viewTaskInfo?.querySelector('#edit_task_button')
if (!editTaskButton) {
    console.error('Edit Task button not found.')
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

const editTaskModalTemplate = document.querySelector('#edit_task_modal_template')
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

const editTaskCloseButton = editTaskModalTemplate?.querySelector('#edit_task_close_button')
if (!editTaskCloseButton) {
    console.error('Edit Task modal close button not found.')
}

editTaskCloseButton?.addEventListener('click', e => {
    e.preventDefault()
    
    if (!editTaskModalTemplate) {
        console.error('Edit Task modal template not found.')
        Dialog.somethingWentWrong()
        return
    }

    editTaskModalTemplate.classList.remove('flex-col')
    editTaskModalTemplate.classList.add('no-display')
})