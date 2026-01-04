import { die } from '../../../../utility/utility.js'

const taskForm = document.querySelector('#task_form')

const workerInfo = taskForm?.querySelector('#worker_info')
if (!workerInfo)
    die('Worker info element not found')

const addWorkerButton = workerInfo.querySelector('#add_worker_button')