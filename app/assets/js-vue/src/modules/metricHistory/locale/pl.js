export default {
    title: 'Dynamika mierników',
    breadcrumb: {
        home: 'Pulpit',
        reports: 'Raporty',
        current: 'Dynamika mierników',
    },
    filters: {
        all_departments: 'Wszystkie działy',
        cumulative: 'Narastająco od 1. dnia',
        daily: 'Przyrost dzienny',
        factors: 'Współczynnik',
        count: 'Liczba',
        compare: 'Porównaj z poprzednim miesiącem',
    },
    computed_at: 'Ostatnie przeliczenie',
    never_computed: 'jeszcze nie przeliczono',
    previous_month: 'poprzedni miesiąc',
    week: 'Tydz. {number}',
    backfill_note: 'Uzupełnione wstecz - policzone na późniejszych danych',
    no_data: 'Brak zapisów dla wybranego miesiąca. Pierwsze wartości pojawią się po przebiegu crona albo po ręcznym przeliczeniu.',
    no_data_chart: 'Brak danych do pokazania. Przyrost dzienny wymaga zapisów z dwóch kolejnych dni.',
    capture_done: 'Przeliczono dzisiejsze wartości mierników.',
    action: {
        capture: 'Przelicz teraz',
    },
    metric: {
        orders_finished: 'Zamówienia zrealizowane',
        orders_pending: 'Zamówienia w realizacji',
    },
    chart: {
        orders: {
            title: 'Zamówienia',
            description: 'Zrealizowane liczone od 1. dnia miesiąca, w realizacji - stan na dany dzień.',
        },
        bonus: {
            title: 'Ukończone zadania produkcyjne',
            description: 'Suma współczynników zadań ukończonych od 1. dnia miesiąca, per dział.',
        },
        on_time: {
            title: 'Terminowość działów',
            description: 'Udział współczynnika ukończonego w terminie (z widełkami obowiązującymi dla rozliczeń) w całości ukończonej od 1. dnia miesiąca.',
        },
        capacity: {
            title: 'Obłożenie działów produkcji',
            description: 'Zaplanowany współczynnik na cały wybrany miesiąc według stanu z danego dnia.',
        },
        weekly: {
            title: 'Obłożenie tygodniowe',
            description: 'Wykorzystanie zdolności kolejnych tygodni (współczynnik zamówień / limit tygodnia) według stanu z danego dnia.',
        },
        attention: {
            title: 'Wymaga uwagi',
            description: 'Liczba pozycji na listach uwagi - stan na dany dzień. Listy zamówień liczą zamówienia, listy produkcji - zadania w działach.',
        },
    },
    error: {
        load: 'Nie udało się pobrać historii mierników.',
        capture: 'Nie udało się przeliczyć mierników.',
    },
}
