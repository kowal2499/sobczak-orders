<template>
    <div>
        <div class="row">
            <div class="col-md-10">
                <div class="form-group">
                    <label>Data realizacji</label><br>
                    <date-picker v-model="innerData.confirmedDate" />
                </div>

            </div>
        </div>

        <div class="row">
            <div class="col-md-10">
                <div class="form-group">
                    <label>Wymagania</label>
                    <textarea class="form-control" cols="30" rows="7" v-model="innerData.description"/>
                </div>

            </div>
        </div>
    </div>

</template>

<script>
    import DatePicker from "../../base/datepicker";

    export default {
        name: "RequirementsWidget",
        props: ['value'],
        components: { DatePicker },

        data() {
            return {
                innerData: this.value
            }
        }
    }
</script>

<style scoped>

</style>