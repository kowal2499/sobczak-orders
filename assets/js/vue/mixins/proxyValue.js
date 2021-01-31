import {cloneDeep} from 'lodash';

export default {
    props: {
        value: {
            type: Object,
            default: () => ({})
        }
    },

    watch: {
        value: {
            deep: true,
            immediate: true,
            handler() {
                if (JSON.stringify(this.value) !== JSON.stringify(this.proxyData)) {
                    this.proxyData = cloneDeep(this.value);
                }
            }
        },

        proxyData: {
            deep: true,
            immediate: true,
            handler() {
                this.$emit('input', cloneDeep(this.proxyData));
            }
        }
    },

    data() {
        return {
            proxyData: {}
        }
    }
}