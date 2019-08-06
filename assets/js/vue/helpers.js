
const statuses = [
    { value: 0, name: 'oczekuje', color: '#FFF' },
    { value: 1, name: 'rozpoczęte', color: '#FFA07A' },
    { value: 2, name: 'w trakcie', color: '#87CEFA' },
    { value: 3, name: 'zakończone', color: '#8FBC8F' }
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

    statuses

}