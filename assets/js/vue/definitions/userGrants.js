
export const GRANTS = {
    'products.add': 10,
    'products.edit': 11,
    'products.delete': 12,
    'products.read': 13,

    'production.add': 20,
    'production.edit': 21,
    'production.delete': 22,
    'production.read': 23,

    'customers.add': 30,
    'customers.edit': 31,
    'customers.delete': 32,
    'customers.read': 33,

    'orders.add': 40,
    'orders.edit': 41,
    'orders.delete': 42,
    'orders.read': 43,

    'reports.factorAnalysis': 50,
    'reports.defaultAnalysis': 51,
};

export const ROLES_GRANTS = {
    ROLE_ADMIN: [
        GRANTS['customers.add'], GRANTS['customers.edit'], GRANTS['customers.delete'], GRANTS['customers.read'],
        GRANTS['products.add'], GRANTS['products.edit'], GRANTS['products.delete'], GRANTS['products.read'],
        GRANTS['production.add'], GRANTS['production.edit'], GRANTS['production.delete'], GRANTS['production.read'],
        GRANTS['orders.add'], GRANTS['orders.edit'], GRANTS['orders.delete'], GRANTS['orders.read'],
        GRANTS['reports.factorAnalysis'], GRANTS['reports.defaultAnalysis'],
    ],
    ROLE_USER: [
        GRANTS['customers.add'], GRANTS['customers.edit'], GRANTS['customers.delete'], GRANTS['customers.read'],
        GRANTS['orders.add'], GRANTS['orders.edit'], GRANTS['orders.delete'], GRANTS['orders.read'],
        GRANTS['products.add'], GRANTS['products.edit'], GRANTS['products.delete'], GRANTS['products.read'],
        GRANTS['production.read'],
        GRANTS['reports.factorAnalysis'], GRANTS['reports.defaultAnalysis'],
    ],
    ROLE_PRODUCTION: [
        GRANTS['production.add'], GRANTS['production.edit'], GRANTS['production.delete'], GRANTS['production.read'],
        GRANTS['products.add'], GRANTS['products.edit'], GRANTS['products.delete'], GRANTS['products.read'],
        GRANTS['reports.factorAnalysis'], GRANTS['reports.defaultAnalysis'],
    ],
    ROLE_CUSTOMER: [
        GRANTS['orders.add'], GRANTS['orders.edit'], GRANTS['orders.read'],
        GRANTS['customers.edit'], GRANTS['customers.read'],
        GRANTS['production.read'],
        GRANTS['reports.defaultAnalysis']
    ]
};

// ROLE_ADMIN: [Roles.CAN_PRODUCTION, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_CUSTOMERS, Roles.CAN_PRODUCTS, Roles.CAN_ORDERS_ADD, Roles.CAN_ORDERS_DELETE],
// ROLE_USER: [Roles.CAN_CUSTOMERS, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_ORDERS_ADD, Roles.CAN_PRODUCTS],
// ROLE_CUSTOMER: [Roles.CAN_CUSTOMERS_OWNED_ONLY, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_ORDERS_ADD],
// ROLE_PRODUCTION: [Roles.CAN_PRODUCTION, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_PRODUCTS]
