import i18n from "../../../../i18n";
import { TASK_TYPE_CUSTOM } from "./taskDefinitions";

export const TASK_STATUS_CUSTOM_TO_ORDER = 10;
export const TASK_STATUS_CUSTOM_WAITING = 11;
export const TASK_STATUS_CUSTOM_DONE = 12;

export function getTaskStatuses(type) {
    return taskStatuses
        .filter(s => s.taskType === type)
        .map(s => ({ ...s, name: i18n.t(s.nameKey) }));
}

const taskStatuses = [
    { taskType: TASK_TYPE_CUSTOM, value: TASK_STATUS_CUSTOM_TO_ORDER, nameKey: "_status_dpt__to_order", color: "#FFF", className: "dropdown-white" },
    { taskType: TASK_TYPE_CUSTOM, value: TASK_STATUS_CUSTOM_WAITING, nameKey: "_status_dpt__ordered_waiting", color: "#87CEFA", className: "dropdown-blue" },
    { taskType: TASK_TYPE_CUSTOM, value: TASK_STATUS_CUSTOM_DONE, nameKey: "_status_dpt__realized_in_stock", color: "#8FBC8F", className: "dropdown-green1" }
];