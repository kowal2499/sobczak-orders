import Tasks from "../definitions/userTasks";

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
    }
}



export default {
    privileges: new Privilages([]),
    Tasks
};