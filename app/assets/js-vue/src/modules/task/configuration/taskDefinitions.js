import i18n from "../../../../i18n";

export const TASK_TYPE_CUSTOM = "task_custom";
export const TASK_TYPE_CONFIRM_REALIZATION_DATE = "task_confirm_realization_date";

export function getTaskDefinition(type) {
    return tasks.find(t => t.type === type);
}

const tasks = [
    { type: TASK_TYPE_CUSTOM, name: i18n.t("taks.types.taskCustom") },
    { type: TASK_TYPE_CONFIRM_REALIZATION_DATE, name: i18n.t("taks.types.confirmRealizationDate") },

];