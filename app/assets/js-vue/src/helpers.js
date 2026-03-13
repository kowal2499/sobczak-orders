import Vue from 'vue'
import i18n from "../i18n";

export const statuses = [
    { value: 0, name: i18n.t('_status_dpt__waiting'), color: '#FFF', className: 'dropdown-white' },
    { value: 1, name: i18n.t('_status_dpt__started'), color: '#FFA07A', className: 'dropdown-orange' },
    { value: 2, name: i18n.t('_status_dpt__in_progress'), color: '#87CEFA', className: 'dropdown-blue' },
    { value: 3, name: i18n.t('_status_dpt__finished'), color: '#8FBC8F', className: 'dropdown-green1' },
    { value: 4, name: i18n.t('_status_dpt__not_applicable'), color: '#419D78', className: 'dropdown-green2' },

    { value: 10, name: i18n.t('_status_dpt__to_order'), color: '#FFF', className: 'dropdown-white' },
    { value: 11, name: i18n.t('_status_dpt__ordered_waiting'), color: '#87CEFA', className: 'dropdown-blue' },
    { value: 12, name: i18n.t('_status_dpt__realized_in_stock'), color: '#8FBC8F', className: 'dropdown-green1' }
];

export const agreementStatuses = [
    { value: 5, className: 'badge-danger', name: i18n.t('_status_agreement__cancelled') },
    { value: 10, className: 'badge-primary', name: i18n.t('_status_agreement__new') },
    { value: 15, className: 'badge-warning', name: i18n.t('_status_agreement__in_realization') },
    { value: 20, className: 'badge-success', name: i18n.t('_status_agreement__finished') },
];

export const agreementStatusesMap = agreementStatuses.reduce((acc, status) => {
    acc[status.value] = status;
    return acc;
}, {});

const roles = [
    { value: 'ROLE_ADMIN', name: 'Administrator' },
    { value: 'ROLE_USER', name: 'Handlowiec' },
    { value: 'ROLE_PRODUCTION', name: 'Produkcja' },
    { value: 'ROLE_CUSTOMER', name: 'Klient' },
];

export const DPT_GLUEING = 'dpt01'
export const DPT_CNC = 'dpt02'
export const DPT_GRINDING = 'dpt03'
export const DPT_LACQUERING = 'dpt04'
export const DPT_PACKING = 'dpt05'
export const DPT_INTOREX = 'dpt06'

export const DEPARTMENTS = [
    {name: 'Klejenie', slug: DPT_GLUEING, grant: 'production.show.gluing'},
    {name: 'CNC', slug: DPT_CNC, grant: 'production.show.cnc'},
    {name: 'Szlifowanie', slug: DPT_GRINDING, grant: 'production.show.grinding'},
    {name: 'Lakierowanie', slug: DPT_LACQUERING, grant: 'production.show.laquering'},
    {name: 'Pakowanie', slug: DPT_PACKING, grant: 'production.show.packing'},
    {name: 'INTOREX', slug: DPT_INTOREX, grant: 'production.show.intorex'},
];
export function getDepartmentName(slug) {
    const department = DEPARTMENTS.find(dpt => dpt.slug === slug)
    return (department && department.name) || slug
}

export function getUserDepartments() {
    return DEPARTMENTS.filter(dpt => Vue.prototype.$user.can(dpt.grant))
}

/**
 * @param { Date } date
 * @returns {string}
 */
export function getLocalDate(date) {
    if (typeof date === 'string') {
        date = new Date(date)
    }
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}


export default {

    customerName(customer) {

        if (!customer) {
            return '';
        }
        let result = customer.name;

        if (customer.first_name || customer.last_name) {
            result = result + ' (' + [customer.first_name, customer.last_name].join(' ') + ')';
        }

        return result;
    },

    userName(user) {
        if (!user) {
            return '';
        }
        return user.firstName.concat(' ', user.lastName)
    },

    statusesPerTaskType(taskType) {

        let result = [];

        switch (taskType) {
            case 'dpt01':
            case 'dpt02':
            case 'dpt03':
            case 'dpt04':
            case 'dpt05':
            case 'dpt06':
                result = statuses.slice(0, 5);
                break;

            case 'custom_task':
                result = statuses.slice(5);
                break;
        }

        return result;
    },

    getRoleName(role) {
        let foundRole = this.roles.find(r => { return r.value === role});
        return foundRole ? foundRole.name : '';
    },

    convertNewlinesToHtml(txt) {
        return txt.replace(/(?:\r\n|\r|\n)/g, '<br />');
    },

    getDepartments() {
        return DEPARTMENTS;
    },

    getDepartmentsSlugs() {
        return DEPARTMENTS.map(d => d.slug);
    },

    getStatusStyle(statusId) {
        const status = statuses.find(item => item.value === parseInt(statusId));
        return status ? `background-color: ${status.color};` : '';
    },

    statuses,
    agreementStatuses,
    agreementStatusesMap,
    roles

}