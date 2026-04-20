import {format, register} from 'timeago.js';

const plPlural = (n, one, few, many) => {
    if (n === 1) {
        return one;
    }
    if (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)) {
        return few;
    }
    return many;
};

register('pl', (number, index) => [
    ['przed chwilą', 'za chwilę'],
    [`${number} ${plPlural(number, 'sekundę', 'sekundy', 'sekund')} temu`, `za ${number} ${plPlural(number, 'sekundę', 'sekundy', 'sekund')}`],
    ['minutę temu', 'za minutę'],
    [`${number} ${plPlural(number, 'minutę', 'minuty', 'minut')} temu`, `za ${number} ${plPlural(number, 'minutę', 'minuty', 'minut')}`],
    ['godzinę temu', 'za godzinę'],
    [`${number} ${plPlural(number, 'godzinę', 'godziny', 'godzin')} temu`, `za ${number} ${plPlural(number, 'godzinę', 'godziny', 'godzin')}`],
    ['wczoraj', 'jutro'],
    [`${number} ${plPlural(number, 'dzień', 'dni', 'dni')} temu`, `za ${number} ${plPlural(number, 'dzień', 'dni', 'dni')}`],
    ['tydzień temu', 'za tydzień'],
    [`${number} ${plPlural(number, 'tydzień', 'tygodnie', 'tygodni')} temu`, `za ${number} ${plPlural(number, 'tydzień', 'tygodnie', 'tygodni')}`],
    ['miesiąc temu', 'za miesiąc'],
    [`${number} ${plPlural(number, 'miesiąc', 'miesiące', 'miesięcy')} temu`, `za ${number} ${plPlural(number, 'miesiąc', 'miesiące', 'miesięcy')}`],
    ['rok temu', 'za rok'],
    [`${number} ${plPlural(number, 'rok', 'lata', 'lat')} temu`, `za ${number} ${plPlural(number, 'rok', 'lata', 'lat')}`],
][index]);

export function timeago(value, locale) {
    if (!value) {
        return '';
    }
    return format(value, locale === 'pl' ? 'pl' : 'en_US');
}
