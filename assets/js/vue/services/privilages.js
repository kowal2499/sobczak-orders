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

    constructor(roles) {

        this.init(roles);

        this.hierarchy = {
            ROLE_ADMIN: [Roles.CAN_PRODUCTION, Roles.CAN_CUSTOMERS, Roles.CAN_PRODUCTS, Roles.CAN_ORDERS_DELETE],
            ROLE_USER: [Roles.CAN_CUSTOMERS, Roles.CAN_PRODUCTS],
            ROLE_CUSTOMER: [Roles.CAN_CUSTOMERS_OWNED_ONLY]
        }

    }

    init(roles) {
        if (Array.isArray(roles)) {
            this.roles = roles;
        } else {
            this.roles = [];
        }
    }

    can(name) {
        if (this.roles.length === 0) {
            return false;
        }


        for (let role of this.roles) {

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