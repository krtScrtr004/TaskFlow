const viewTaskInfo = document.querySelector('.view-task-info')
const editTaskButton = viewTaskInfo?.querySelector('#edit_task_button')
const editTaskModalTemplate = document.querySelector('#edit_task_modal_template')

if (editTaskButton) {
    editTaskButton.addEventListener('click', e => {
        e.preventDefault()

        if (!editTaskModalTemplate) {
            console.error('Edit Task modal template not found.')
            Dialog.somethingWentWrong()
            return
        }

        editTaskModalTemplate.classList.remove('no-display')
        editTaskModalTemplate.classList.add('flex-col')
    })
} else {
    console.error('Edit Task button not found.')
}

if (editTaskModalTemplate) {
    editTaskModalTemplate.addEventListener('click', e => {
        const editTaskCloseButton = e.target.closest('#edit_task_close_button')
        if (editTaskCloseButton) {
            editTaskModalTemplate.classList.remove('flex-col')
            editTaskModalTemplate.classList.add('no-display')
        }
    })
} else {
    console.error('Edit Task modal template not found.')
}