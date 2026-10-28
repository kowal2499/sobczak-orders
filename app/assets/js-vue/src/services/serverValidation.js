/**
 * Shows a 422 `{ error, field }` answer under the matching ValidationProvider (by `vid`),
 * and anything else as a flash message.
 */
export function applyServerError(error, observer, fallbackMessage) {
    const response = error && error.response
    const message = (response && response.data && response.data.error) || fallbackMessage

    if (response && response.status === 422 && response.data.field && observer) {
        observer.setErrors({ [response.data.field]: [message] })
        return
    }

    window.EventBus.$emit('message', { type: 'danger', content: message })
}
