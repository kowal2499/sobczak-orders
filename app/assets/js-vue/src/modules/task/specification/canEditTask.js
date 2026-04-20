/**
 * A task can be edited by the current user when:
 * - the task has no owner (unassigned tasks are editable by anyone), or
 * - the task owner is the current user.
 *
 * @param {object|null} taskOwner - owner object { id, userFullName } or null
 * @param {number|null} currentUserId
 * @returns {boolean}
 */
export function canEditTask(taskOwner, currentUserId) {
    if (!taskOwner) {
        return true;
    }
    return taskOwner.id === currentUserId;
}
