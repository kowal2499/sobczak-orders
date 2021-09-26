export const MONTHS = [
    {number: 0, name: '_january'},
    {number: 1, name: '_february'},
    {number: 2, name: '_march'},
    {number: 3, name: '_april'},
    {number: 4, name: '_may'},
    {number: 5, name: '_june'},
    {number: 6, name: '_july'},
    {number: 7, name: '_august'},
    {number: 8, name: '_september'},
    {number: 9, name: '_october'},
    {number: 10, name: '_november'},
    {number: 11, name: '_december'}
];

export function dateToString(date) {
    return [date.getFullYear(), date.getMonth() + 1, date.getDate()]
        .map(num => num < 10 ? `0${num}` : num)
        .join('-');
}

export function firstDay(year, month) {
    return new Date(year, month, 1);
}

export function lastDay(year, month) {
    return new Date(year, month + 1, 0);
}