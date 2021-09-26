import Tasks from "../definitions/userTasks";
import Roles from "../definitions/userRoles";

class Privilages {

    constructor(roles) {

        this.init(roles);

    }

    init(roles) {
        if (Array.isArray(roles)) {
            this.roles = roles;
        } else {
            this.roles = [];
        }
    }

    can(task) {

        if (this.roles.length === 0) {
            return false;
        }

        switch (task) {
            case Tasks.CUSTOMER_ADD:
            case Tasks.PRODUCTION_CREATE:
            case Tasks.ORDER_DELETE:
                return this.roles.indexOf('ROLE_CUSTOMER') === -1;

        }

        return true;
    }
}

class User {

    constructor(user) {

        if (user) {
            this.user = user;
        } else {
            this.user = {};
        }

        if (!this.user.roles) {
            this.user.roles = [];
        }

        this.hierarchy = {
            ROLE_ADMIN: [Roles.CAN_PRODUCTION, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_CUSTOMERS, Roles.CAN_PRODUCTS, Roles.CAN_ORDERS_ADD, Roles.CAN_ORDERS_DELETE],
            ROLE_USER: [Roles.CAN_CUSTOMERS, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_ORDERS_ADD, Roles.CAN_PRODUCTS],
            ROLE_CUSTOMER: [Roles.CAN_CUSTOMERS_OWNED_ONLY, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_ORDERS_ADD],
            ROLE_PRODUCTION: [Roles.CAN_PRODUCTION, Roles.CAN_PRODUCTION_VIEW, Roles.CAN_PRODUCTS]
        }

    }

    getName() {
        if (this.user) {
            return this.user.firstName + ' ' + this.user.lastName;
        } else {
            return '';
        }
    }

    getId() {
        if (this.user) {
            return this.user.id;
        } else {
            return null;
        }
    }

    can(name) {
        if (this.user.roles.length === 0) {
            return false;
        }


        for (let role of this.user.roles) {

            let possibilities = this.hierarchy[role];

            if (!possibilities) {
                continue;
            }

            if (possibilities.indexOf(name) !== -1) {
                return true;
            }

        }

        return false;
    }
}


export default {
    privileges: new Privilages([]),
    Tasks,
    Roles,
    User
};