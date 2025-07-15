const LocalStorage = require('localstorage')

const storage = new LocalStorage('sobczak-app')
export function get(key) {
    if (!storage.has(key)) {
        return undefined
    }
    const [err, value] = storage.get(key);
    if (err || !value) {
        return undefined;
    }

    const { data, validTo } = value;
    if (typeof validTo !== 'number' || Date.now() > validTo) {
        return undefined;
    }

    return data;
}

export function set(key, data, validityInMinutes) {
    const validTo = Date.now() + validityInMinutes * 60 * 1000;
    storage.put(key, { data, validTo })
}