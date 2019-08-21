<template>
    <div class="row">

        <div class="col-md-4">
            <div class="nav flex-column nav-pills border-right pr-2">
                <a v-for="dpt in departments"
                   class="nav-link" id="v-pills-messages-tab" href="#"
                   :class="{active: dpt.slug === selectedDepartmentSlug}"
                   @click.prevent="departmentChange(dpt)"
                >
                    <span class="statusNotify"
                        :style="getStatusStyleBySlug(dpt.slug)"></span>
                    {{ dpt.name }}
                </a>
            </div>
        </div>

        <div class="col-md-8">

            <div class="row" v-for="dpt in innerData">

                <template v-if="dpt.departmentSlug === selectedDepartmentSlug">
                    <div class="col-md-7">

                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" v-model="dpt.status" :style="getStatusStyle(dpt.status)">
                                <option
                                    v-for="status in statuses"
                                    :value="status.value"
                                    v-text="status.name"
                                    style="background-color: white"
                                ></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Realizacja od</label><br>
                            <date-picker v-model="dpt.dateStart" :is-range="false" />
                        </div>

                        <div class="form-group">
                            <label>Realizacja do</label><br>
                            <date-picker v-model="dpt.dateEnd" :is-range="false"/>
                        </div>

                        <div class="mb-2">
                            <a href="#" @click.prevent="toggleHistory(dpt)">
                                <template v-if="showHistoryForSlugs.indexOf(dpt.departmentSlug) !== -1">
                                    Ukryj historię zmian statusów
                                </template>
                                <template v-else>
                                    Pokaż historię zmian statusów
                                </template>
                            </a>
                        </div>

                        <table class="table table-bordered" v-if="showHistoryForSlugs.indexOf(dpt.departmentSlug) !== -1">
                            <tr>
                                <th>Data zmiany</th>
                                <th>Nowy status</th>
                            </tr>

                            <tr v-for="status in dpt.statusLog">
                                <td v-text="status.createdAt"></td>
                                <td v-text="getStatusName(status.currentStatus)"></td>
                            </tr>
                        </table>
                    </div>
                </template>

            </div>

        </div>

    </div>
</template>

<script>
    import DatePicker from "../../base/datepicker";
    import Helpers from "../../../helpers";

    export default {
        name: "ProductionWidget",
        props: ['value', 'departments'],
        components: { DatePicker },

        data() {
            return {
                selectedDepartmentSlug: 'dpt01',
                statuses: Helpers.statuses,
                innerData: this.value,
                showHistoryForSlugs: []
            }
        },

        methods: {
            departmentChange(dpt) {
                this.selectedDepartmentSlug = dpt.slug;
            },

            getStatusStyleBySlug(slug) {
                let dpt = this.innerData.find(department => { return department.departmentSlug === slug });
                if (dpt) {
                    return this.getStatusStyle(dpt.status)
                } else {
                    return '';
                }
            },

            getStatusStyle(statusId) {
                let status = this.statuses.find(item => item.value == statusId);
                if (status) {
                    return 'background-color: '.concat(status.color);
                }
                return '';
            },

            getStatusName(statusCode) {
                let status = this.statuses.find(item => item.value == statusCode);
                return status ? status.name : 'nieznany';
            },

            toggleHistory(dpt) {
                let idx = this.showHistoryForSlugs.indexOf(dpt.departmentSlug);
                if (idx !== -1) {
                    this.showHistoryForSlugs.splice(idx, 1);
                } else {
                    this.showHistoryForSlugs.push(dpt.departmentSlug);
                }
            }
        },

        watch: {
            // value: {
            //     handler(val) {
            //         console.log('test')
            //         this.innerData = val;
            //
            //     },
            //     deep: true
            // },
            //
            // departments: {
            //     handler(val) {
            //         if (Array.isArray(val)) {
            //             this.selectedDepartmentSlug = val[0].slug;
            //             console.log(this.selectedDepartmentSlug);
            //         }
            //     },
            //     deep: true
            // }
        }
    }
</script>

<style scoped>
    .statusNotify {
        display: inline-block; width: 12px; height: 12px; margin-right: 5px; border: 1px solid #666; background-color: white;
    }
</style>