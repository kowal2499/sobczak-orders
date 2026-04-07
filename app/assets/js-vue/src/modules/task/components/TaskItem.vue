<script>
import ShowcaseBadge from '@/components/base/Showcase/ShowcaseBadge'
import DatePicker from '@/components/base/DatePicker'
import { TASK_TYPE_CUSTOM, getTaskDefinition, getTaskStatuses } from '../configuration/statuses'

export default {
    name: 'TaskItem',
    components: {
        ShowcaseBadge,
        DatePicker,
    },

    computed: {
        taskDefinition() {
            return getTaskDefinition(TASK_TYPE_CUSTOM)
        },
        statusOptions() {
            return getTaskStatuses(TASK_TYPE_CUSTOM)
        },
        activeOption() {
            return this.statusOptions.find(opt => opt.value === this.status)
        }
    },

    methods: {
        onClick(e) {
            this.status = e.value
            console.log('Task item clicked', e)
        }
    },

    data: () => ({
        title: null,
        description: null,
        dateStart: null,
        dateEnd: null,
        status: null,
    })
}
</script>

<template>
    <div>
        <div class="details">
            <div class="d-flex flex-column flex-md-row gap-2">
                <div class="d-flex flex-column flex-fill gap-2">
                    <showcase-badge label="Tytuł">
                        <template #value>
                            <b-form-input v-model="title" />
                        </template>
                    </showcase-badge>

                    <showcase-badge label="Opis">
                        <template #value>
                            <b-textarea v-model="description" rows="4"></b-textarea>
                        </template>
                    </showcase-badge>
                </div>

                <div class="d-flex flex-column flex-fill gap-2">
                    <showcase-badge label="Realizacja od">
                        <template #value>
                            <date-picker v-model="dateStart" :is-range="false" :date-only="false" style="width: 100%" />
                        </template>
                    </showcase-badge>

                    <showcase-badge label="Realizacja do">
                        <template #value>
                            <date-picker v-model="dateEnd" :is-range="false" :date-only="false" style="width: 100%" />
                        </template>
                    </showcase-badge>

                    <showcase-badge label="Status">
                        <template #value>
                            <b-dropdown :text="activeOption ? activeOption.name : 'Wybierz'" size="sm">
                                <b-dropdown-item
                                    v-for="opt in statusOptions"
                                    :active="opt.value === status"
                                    @click="onClick(opt)"
                                >
                                    {{ opt.name }}
                                </b-dropdown-item>
                            </b-dropdown>
                        </template>
                    </showcase-badge>
                </div>

                <div class="d-flex flex-column gap-2 align-items-end">
                    <button class="btn btn-success btn-sm">
                        <font-awesome-icon icon="plus" />
                    </button>

                    <button class="btn btn-danger btn-sm">
                        <font-awesome-icon icon="trash" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>