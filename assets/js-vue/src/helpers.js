
const statuses = [
    { value: 0, name: 'oczekuje', color: '#FFF', className: 'dropdown-white' },
    { value: 1, name: 'rozpoczęte', color: '#FFA07A', className: 'dropdown-orange' },
    { value: 2, name: 'w trakcie', color: '#87CEFA', className: 'dropdown-blue' },
    { value: 3, name: 'zakończone', color: '#8FBC8F', className: 'dropdown-green1' },
    { value: 4, name: 'nie dotyczy', color: '#419D78', className: 'dropdown-green2' },

    { value: 10, name: 'do zamówienia', color: '#FFF', className: 'dropdown-white' },
    { value: 11, name: 'zamówione i oczekiwanie', color: '#87CEFA', className: 'dropdown-blue' },
    { value: 12, name: 'zrealizowane na stanie', color: '#8FBC8F', className: 'dropdown-green1' }
];

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

export const DEPARTMETNS = [
    {name: 'Klejenie', slug: DPT_GLUEING, grant: 'production.show.gluing'},
    {name: 'CNC', slug: DPT_CNC, grant: 'production.show.cnc'},
    {name: 'Szlifowanie', slug: DPT_GRINDING, grant: 'production.show.grinding'},
    {name: 'Lakierowanie', slug: DPT_LACQUERING, grant: 'production.show.laquering'},
    {name: 'Pakowanie', slug: DPT_PACKING, grant: 'production.show.packing'},
];
export function getDepartmentName(slug) {
    const department = DEPARTMETNS.find(dpt => dpt.slug === slug)
    return (department && department.name) || slug
}

/**
 * @param { Date } date
 * @returns {string}
 */
export function getLocalDate(date) {
    const yyyy = date.getFullYear()
    const mm = String(date.getMonth() + 1).padStart(2, '0')
    const dd = String(date.getDate()).padStart(2, '0')

    return `${yyyy}-${mm}-${dd}`;
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
        return DEPARTMETNS;
    },

    getDepartmentsSlugs() {
        return DEPARTMETNS.map(d => d.slug);
    },

    getStatusStyle(statusId) {
        const status = statuses.find(item => item.value === parseInt(statusId));
        return status ? `background-color: ${status.color};` : '';
    },

    statuses,
    roles

}