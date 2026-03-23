import Vue from 'vue'
import { ValidationProvider, ValidationObserver } from 'vee-validate';
import { extend } from 'vee-validate';
import { required, email, numeric, min_value, max_value, excluded } from 'vee-validate/dist/rules';
import i18n from '../../i18n';
import { parseYMD } from '../services/datesService';

Vue.component('ValidationProvider', ValidationProvider)
Vue.component('ValidationObserver', ValidationObserver)

const rulesToRegister = [
    ['required', required],
    ['email', email],
    ['numeric', numeric],
    ['min_value', min_value],
    ['max_value', max_value],
    ['excluded', excluded],
]
rulesToRegister.forEach(([name, rule]) => {
    extend(name, {
        ...rule,
        message: (field, params) =>
        {
            return i18n.t(`_validation.${name}`, { ...params, _field_: field })
        }
    })
})


const isValidDate = d => d instanceof Date && !isNaN(d.getTime())

extend('dateFrom', {
	params: ['target'],
	validate(value, { target }) {
		if (!target) {
			return true
		}
		if (!value) {
			return false
		}
        const targetDate = parseYMD(target)
        const valueDate = parseYMD(value)
        if (!isValidDate(targetDate) || !isValidDate(valueDate)) {
            return false
        }
        return valueDate.getTime() < targetDate.getTime()
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
        const targetDate = parseYMD(target)
        const valueDate = parseYMD(value)
        if (!isValidDate(targetDate) || !isValidDate(valueDate)) {
            return false
        }
		return valueDate.getTime() > targetDate.getTime()
	},
	message: i18n.t('_validation.dateTo')
})

extend('dateFromOrEqual', {
	params: ['target'],
	validate(value, { target }) {
		if (!target) {
			return true
		}
		if (!value) {
			return false
		}
        const targetDate = parseYMD(target)
        const valueDate = parseYMD(value)
        if (!isValidDate(targetDate) || !isValidDate(valueDate)) {
            return false
        }
        return valueDate.getTime() <= targetDate.getTime()
	},
	message: i18n.t('_validation.dateFromOrEqual')
})

extend('dateToOrEqual', {
	params: ['target'],
	validate(value, { target }) {
		if (!target) {
			return true
		}
		if (!value) {
			return false
		}
        const targetDate = parseYMD(target)
        const valueDate = parseYMD(value)
        if (!isValidDate(targetDate) || !isValidDate(valueDate)) {
            return false
        }
		return valueDate.getTime() >= targetDate.getTime()
	},
	message: i18n.t('_validation.dateToOrEqual')
})

extend('dateEarlierThan', {
    params: ['deadline'],
    validate(value, { deadline }) {

        if (!deadline) return true
        if (!value) return false

        const valueDate = parseYMD(value)

        if (!isValidDate(valueDate) || !isValidDate(deadline)) {
            return false
        }
        const deadlineDate = new Date(deadline.getTime())
        deadlineDate.setHours(23, 59, 59, 999)
        return valueDate.getTime() < deadlineDate.getTime()
    },
    message: i18n.t('_validation.dateEarlierThan')
})