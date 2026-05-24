<template>
  <b-modal
    :visible="visible"
    title="Nowe zlecenie"
    ok-title="Zapisz"
    cancel-title="Anuluj"
    @ok.prevent="onSave"
    @cancel="$emit('cancel')"
    @hidden="$emit('cancel')"
  >
    <b-form @submit.prevent="onSave">
      <b-form-group label="Dział">
        <b-form-input :value="form.resourceName" readonly />
      </b-form-group>
      <b-form-group label="Nazwa zlecenia" :state="nameState">
        <b-form-input
          v-model="form.orderName"
          placeholder="np. Zlecenie #1001"
          :state="nameState"
          autofocus
        />
        <b-form-invalid-feedback>Podaj nazwę zlecenia.</b-form-invalid-feedback>
      </b-form-group>
      <b-form-group label="Status">
        <b-form-select v-model="form.orderStatus" :options="statusOptions" />
      </b-form-group>
      <b-form-row>
        <b-col>
          <b-form-group label="Data od">
            <b-form-input type="date" v-model="form.dateStart" />
          </b-form-group>
        </b-col>
        <b-col>
          <b-form-group label="Data do">
            <b-form-input type="date" v-model="form.dateEnd" />
          </b-form-group>
        </b-col>
      </b-form-row>
      <b-form-group label="Typ eventu">
        <b-form-select v-model="form.eventType" :options="eventTypeOptions" />
      </b-form-group>
    </b-form>
  </b-modal>
</template>

<script>
export default {
  name: 'NewEventModal',
  props: {
    visible: { type: Boolean, default: false },
    prefill: { type: Object, default: null }
  },
  data() {
    return {
      submitted: false,
      form: {
        orderName: '',
        orderStatus: 'pending',
        eventType: 'order',
        resourceId: '',
        resourceName: '',
        dateStart: '',
        dateEnd: ''
      },
      statusOptions: [
        { value: 'pending', text: 'Oczekuje' },
        { value: 'in_progress', text: 'W trakcie' },
        { value: 'completed', text: 'Zakończone' },
        { value: 'cancelled', text: 'Anulowane' }
      ],
      eventTypeOptions: [
        { value: 'order', text: 'Zlecenie' },
        { value: 'maintenance', text: 'Serwis / przerwa' },
        { value: 'background', text: 'Tło (background)' }
      ]
    }
  },
  computed: {
    nameState() {
      if (!this.submitted) return null
      return this.form.orderName.trim().length > 0 ? true : false
    }
  },
  watch: {
    prefill(val) {
      if (val) {
        Object.assign(this.form, val)
        this.submitted = false
      }
    }
  },
  methods: {
    onSave() {
      this.submitted = true
      if (!this.form.orderName.trim()) return
      this.$emit('save', {
        ...this.form,
        id: Date.now().toString()
      })
      this.reset()
    },
    reset() {
      this.submitted = false
      this.form.orderName = ''
      this.form.orderStatus = 'pending'
      this.form.eventType = 'order'
    }
  }
}
</script>
