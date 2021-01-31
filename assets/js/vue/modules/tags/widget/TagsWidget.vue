<template>
    <div>
        <b-alert v-if="fetchingDefinitions" show variant="info">
            {{ $t('tags.moduleDefinitionsFetching', {moduleName}) }}
        </b-alert>
        <b-alert v-else-if="definitions.length === 0" show variant="info">
            {{ $t('tags.noDefinitions', {moduleName}) }}
        </b-alert>
        <div v-else>
            <vue-select
                v-model="tagsProxy"
                :options="selectOptions"
                :multiple="true"
                :taggable="false"
                :reduce="option => option.value"
                :searchable="false"
                :appendToBody="true"
            >
                <template #selected-option="option">
                    <div class="tag-badge">
                        <b-icon :icon="option.icon"></b-icon>
                        {{ option.label }}
                    </div>
                </template>
            </vue-select>
        </div>
    </div>
</template>

<script>
import {tagModules} from "../definitions";
import {search} from "../repository";
import VueSelect from 'vue-select';

export default {
    name: "TagsWidget",
    props: {
        moduleName: {
            type: String,
            required: true,
            validator: value => {
                return tagModules.includes(value);
            }
        },
        value: {
            type: Array,
            default: () => ([])
        }
    },
    components: {
        VueSelect
    },
    computed: {
        selectOptions() {
            return this.definitions.map(def => ({
                label: def.name,
                icon: def.icon,
                color: def.color,
                value: def.id
            }))
        }
    },
    watch: {
        value: {
            deep: true,
            handler() {
                this.tagsProxy = [...this.value];
            }
        },
        tagsProxy: {
            deep: true,
            handler() {
                console.log('zmiana proxy');
                if (this.tagsProxy.status !== this.oldStatus) {
                    console.log('zmiana statusu')
                    this.statusChangeHandler();
                }
                this.oldStatus = this.tagsProxy.status;
                this.$emit('input', [...this.tagsProxy]);
            }
        }
    },
    mounted() {
        this.fetchingDefinitions = true;
        search(module = this.moduleName)
            .then(({data}) => {
                this.definitions = data;
            })
            .finally(() => this.fetchingDefinitions = false)
    },

    data: () => ({
        tagsProxy: [],
        definitions: [],
        fetchingDefinitions: false,
        isBusy: false
    })
}
</script>

<style scoped lang="scss">
    .tag-badge {
        padding: 6px 10px;
    }
</style>