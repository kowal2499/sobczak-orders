<template>
    <div v-if="showMe">
        <b-alert v-if="fetchingDefinitions" show variant="info">
            {{ $t('tags.moduleDefinitionsFetching', {moduleName}) }}
        </b-alert>
        <b-alert v-else-if="definitions.length === 0" show variant="info">
            {{ $t('tags.noDefinitions', {moduleName}) }}
        </b-alert>
        <div v-else>
            <vue-select
                v-model="proxyData"
                :options="selectOptions"
                :multiple="true"
                :taggable="false"
                :reduce="option => option.value"
                :searchable="false"
                :appendToBody="true"
                :placeholder="$t('tags.productionTags')"
            >
                <template #selected-option="option">
                    <div class="tag-badge">
                        <b-icon :icon="option.icon" :style="{color: option.color}"></b-icon>
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
import proxyValue from "../../../mixins/proxyValue";

export default {
    name: "TagsWidget",
    props: {
        moduleName: {
            type: String,
            required: true,
            validator: value => {
                return tagModules.includes(value);
            }
        }
    },
    mixins: [proxyValue],
    components: {
        VueSelect
    },
    computed: {
        showMe() {
            return this.definitions.length > 0 && this.$user.can(this.$privilages.CAN_PRODUCTION)
        },
        selectOptions() {
            return this.definitions.map(def => ({
                label: def.name,
                icon: def.icon,
                color: def.color,
                value: def.id
            }))
        }
    },
    mounted() {
        this.fetchingDefinitions = true;
        search(this.moduleName)
            .then(({data}) => {
                this.definitions = data;
            })
            .finally(() => this.fetchingDefinitions = false)
    },

    data: () => ({
        definitions: [],
        fetchingDefinitions: false,
        isBusy: false
    })
}
</script>

<style scoped lang="scss">
    .tag-badge {
        padding: 4px 8px;
    }
</style>