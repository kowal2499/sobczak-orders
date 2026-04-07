import i18n from "../../../../i18n";

export const TASK_TYPE_CUSTOM = 'task_custom'

export function getTaskDefinition(type) {
    return tasks.find(t => t.type === type);
}

export function getTaskStatuses(type) {
    return statuses.filter(s => s.taskType === type);
}

const tasks = [
    { type: TASK_TYPE_CUSTOM, name: i18n.t('taks.types.taskCustom') }
]

const statuses = [
    { taskType: TASK_TYPE_CUSTOM, value: 10, name: i18n.t('_status_dpt__to_order'), color: '#FFF', className: 'dropdown-white' },
    { taskType: TASK_TYPE_CUSTOM, value: 11, name: i18n.t('_status_dpt__ordered_waiting'), color: '#87CEFA', className: 'dropdown-blue' },
    { taskType: TASK_TYPE_CUSTOM, value: 12, name: i18n.t('_status_dpt__realized_in_stock'), color: '#8FBC8F', className: 'dropdown-green1' }
]