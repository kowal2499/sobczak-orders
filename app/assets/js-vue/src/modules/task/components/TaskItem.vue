<script>
import ShowcaseBadge from "@/components/base/Showcase/ShowcaseBadge";
import DatePicker from "@/components/base/DatePicker";
import { getTaskStatuses } from "../configuration/taskStatuses";
import { getTaskDefinition, TASK_TYPE_CUSTOM } from "../configuration/taskDefinitions";
import proxyValue from "@/mixins/proxyValue";

export default {
    name: "TaskItem",
    components: {
        ShowcaseBadge,
        DatePicker,
    },

    mixins: [proxyValue],

    computed: {
        taskDefinition() {
            return getTaskDefinition(TASK_TYPE_CUSTOM);
        },
        statusOptions() {
            return getTaskStatuses(TASK_TYPE_CUSTOM);
        },
        activeOption() {
            return this.statusOptions.find(opt => opt.value === this.proxyData.status);
        }
    },

    methods: {
        onStatusClick(opt) {
            this.proxyData.status = opt.value;
        }
    },
};
</script>

<template>
    <div>
        <div class="details">
            <div class="row">
                <div class="col-12 col-lg d-flex flex-column">
                    <showcase-badge label="Tytuł">
                        <template #value>
                            <b-form-input v-model="proxyData.title" />
                        </template>
                    </showcase-badge>

                    <showcase-badge label="Opis">
                        <template #value>
                            <b-textarea v-model="proxyData.description" rows="4"></b-textarea>
                        </template>
                    </showcase-badge>
                </div>

                <div class="col-12 col-lg d-flex flex-column gap-2">

                    <div class="row">
                        <div class="col-lg-6">
                            <showcase-badge label="Realizacja od">
                                <template #value>
                                    <date-picker v-model="proxyData.dateStart" :is-range="false" :date-only="false" style="width: 100%" />
                                </template>
                            </showcase-badge>
                        </div>

                        <div class="col-lg-6">
                            <showcase-badge label="Realizacja do">
                                <template #value>
                                    <date-picker v-model="proxyData.dateEnd" :is-range="false" :date-only="false" style="width: 100%" />
                                </template>
                            </showcase-badge>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <showcase-badge label="Status">
                                <template #value>
                                    <b-dropdown :text="activeOption ? activeOption.name : 'Wybierz'" size="sm">
                                        <b-dropdown-item
                                            v-for="opt in statusOptions"
                                            :key="opt.value"
                                            :active="opt.value === proxyData.status"
                                            @click="onStatusClick(opt)"
                                        >
                                            {{ opt.name }}
                                        </b-dropdown-item>
                                    </b-dropdown>
                                </template>
                            </showcase-badge>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-auto d-flex flex-column gap-2 align-items-end">
                    <button class="btn btn-danger btn-sm" @click="$emit('remove', proxyData)">
                        <font-awesome-icon icon="trash" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
