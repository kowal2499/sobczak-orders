export default {
    props: {
        data: {
            type: [Object, Array]
        },
        isBusy: {
            type: Boolean,
            default: false
        },
        filters: {
            type: Object,
            default: () => ({})
        }
    }
}