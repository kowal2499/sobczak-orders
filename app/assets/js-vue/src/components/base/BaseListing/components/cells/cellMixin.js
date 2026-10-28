/**
 * Wspólny kontrakt komórek listingu - komplet dostaje każdy komponent kolumny.
 */
export default {
    props: {
        record: {
            type: Object,
            required: true
        },
        column: {
            type: Object,
            default: null
        },
        disabled: {
            type: Boolean,
            default: false
        }
    }
}
