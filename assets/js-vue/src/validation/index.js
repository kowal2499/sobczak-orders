import Vue from 'vue'
import { ValidationProvider, ValidationObserver } from 'vee-validate';
import { extend } from 'vee-validate';
import { required, email } from 'vee-validate/dist/rules';
import i18n from '../../i18n';

Vue.component('ValidationProvider', ValidationProvider)
Vue.component('ValidationObserver', ValidationObserver)

extend('email', email)
extend('required', {
	...required,
	message: i18n.t('_validation.required')
});

extend('dateFrom', {
	params: ['target'],
	validate(value, { target }) {
		if (!target) {
			return true
		}
		if (!value) {
			return false
		}
		return new Date(value) < new Date(target)
	},
	message: i18n.t('_validation.dateFrom')
})

extend('dateTo', {
	params: ['target'],
	validate(value, { target }) {
		if (!target) {
			return true
		}
		if (!value) {
			return false
		}
		return new Date(value) > new Date(target)
	},
	message: i18n.t('_validation.dateTo')
})