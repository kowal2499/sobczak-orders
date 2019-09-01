
const statuses = [
    { value: 0, name: 'oczekuje', color: '#FFF' },
    { value: 1, name: 'rozpoczęte', color: '#FFA07A' },
    { value: 2, name: 'w trakcie', color: '#87CEFA' },
    { value: 3, name: 'zakończone', color: '#8FBC8F' },

    { value: 10, name: 'do zamówienia', color: '#FFF' },
    { value: 11, name: 'zamówione i oczekiwanie', color: '#87CEFA' },
    { value: 12, name: 'zrealizowane na stanie', color: '#8FBC8F' }
];


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
                result = statuses.slice(0, 4);
                break;

            case 'custom_task':
                result = statuses.slice(4);
                break;
        }

        return result;
    },

    statuses

}