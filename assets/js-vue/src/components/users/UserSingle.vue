<template>
    <div class="row">
        <div class="col-sm-12">
            <waiting :description="'Trwa pobieranie danych'" v-if="!dataFetched"></waiting>

            <collapsible-card
                v-if="dataFetched"
                :title="title"
                :locked="locked"
            >

                <div class="form-group row">
                    <label class="col-3 col-form-label">
                        Imię
                    </label>
                    <div class="col">
                        <input type="text" class="form-control" v-model="user.firstName">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-3 col-form-label">
                        Nazwisko
                    </label>
                    <div class="col">
                        <input type="text" class="form-control" v-model="user.lastName">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-3 col-form-label">
                        Email
                    </label>
                    <div class="col">
                        <input type="text" class="form-control" v-model="user.email">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-3 col-form-label">
                        Uwierzytelnienie
                    </label>
                    <div class="col">
                        <div class="card">
                            <div class="card-body">
                                <p class="text-muted" v-if="!isNew()">Pozostaw te pola puste aby nie zmieniać hasła.</p>

                                <div class="card-text">

                                    <div class="form-group row" v-if="!isNew()">
                                        <label class="col-3 col-form-label">
                                            Stare hasło
                                        </label>
                                        <div class="col">
                                            <input type="password" class="form-control" v-model="passwords.old">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-3 col-form-label">
                                            <span v-if="isNew()">Hasło</span><span v-else>Nowe hasło</span>
                                        </label>
                                        <div class="col">
                                            <input type="password" class="form-control" v-model="passwords.new">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-3 col-form-label">
                                            <span v-if="isNew()">Powtórz hasło</span><span v-else>Powtórz nowe hasło</span>
                                        </label>
                                        <div class="col">
                                            <input type="password" class="form-control" v-model="passwords.check">
                                        </div>
                                    </div>

                                    <div class="row" v-if="!passwords.passwordsMatch">
                                        <div class="col">
                                            <div class="alert alert-info" v-if="!isNew()">
                                                Wartości w polach 'Nowe hasło' i 'Powtórz nowe hasło' muszą być takie same.
                                            </div>
                                            <div class="alert alert-info" v-if="isNew()">
                                                Wartości w polach 'Hasło' i 'Powtórz hasło' muszą być takie same.
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-3 col-form-label">
                        Role (stary system)
                    </label>
                    <div class="col">
                        <role-picker v-model="user" />
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-3 col-form-label">
                        Role (nowy system)
                    </label>
                    <div class="col">
                        <role-select
                            :userId="userId"
                            v-model="newRoles"
                        />
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <button-plus
                            :button-class="'btn-primary float-right'"
                            :icon-class="'fa fa-floppy-o'"
                            :inner-text="isNew() ? 'Dodaj użytkownika' : 'Zapisz zmiany'"
                            :is-disabled="!passwords.passwordsMatch || user.roles.length===0"
                            :is-busy="locked"
                            @clicked="save"
                        >
                        </button-plus>
                    </div>
                </div>

            </collapsible-card>
        </div>
    </div>

</template>

<script>

    import CollapsibleCard from "../base/CollapsibleCard";
    import users from "../../api/users";
    import helpers from "../../helpers";
    import Waiting from "../base/Waiting";
    import Routing from "../../api/routing";
    import ButtonPlus from "../base/ButtonPlus";
    import RolePicker from "./RolePicker";
    import RoleSelect from '@/modules/authorization/components/RoleSelect'
    import { assignRoles } from '@/modules/authorization/repository/rolesRepository'
    export default {
        name: "UserSingle",

        components: {RoleSelect, CollapsibleCard, Waiting, ButtonPlus, RolePicker },

        props: {
            userId: {
                type: Number,
                default: 0
            }
        },

        mounted() {
            // pobranie danych użytkownika
            if (this.userId > 0) {
                this.dataFetched = false;
                this.locked = true;

                users.fetchUser(this.userId)
                    .then(({data}) => {
                        data.customers2Users = (data.customers2Users || []).map(item => ({
                            id: item.id,
                            customer: item.customer.id
                        }))
                        this.user = data;
                        // this.roles.customers = data.customers;
                        this.title = helpers.userName(this.user);
                        this.repeatedPassword = this.user.password;
                        this.dataFetched = true;
                    })
                    .catch(() => {})
                    .finally(() => {
                        this.locked = false;
                    })
            } else {
                this.dataFetched = true;
                this.title = "Nowy użytkownik";
            }

        },

        methods: {
            save() {
                this.locked = true;

                const userData = {...this.user}
                userData.customers2Users = (userData.customers2Users || []).map(data => {
                    const item = {
                        customer: data.customer,
                    }
                    if (this.userId) {
                        item.user = this.userId
                    }
                    return item
                })

                if (this.passwords.new) {
                    userData.passwordPlain = this.passwords.new;
                    userData.passwordOld = this.passwords.old;
                }

                let fn = this.userId ? users.storeUser : users.addUser;

                Promise.all([
                    fn(userData),
                    assignRoles(this.userId, this.newRoles)
                ])
                    .then(() => {
                        // gdy dodano nowego użytkownika to przekieruj do listy
                        if (this.isNew()) {
                            window.location.replace(Routing.get('security_users'));
                        } else {
                            EventBus.$emit('message', {
                                type: 'success',
                                content: 'Zapisano zmiany.'
                            });
                            this.locked = false;
                        }
                    })
                    .catch((data) => {
                        if (data.response.data) {
                            EventBus.$emit('message', {
                                type: 'error',
                                content: data.response.data.errors.title
                            });
                        }
                        this.locked = false;
                    })
                ;
            },

            isNew() {
                return this.userId === 0;
            }
        },

        watch: {
            passwords: {
                handler() {
                    if (this.passwords.new === this.passwords.check) {
                        this.passwords.passwordsMatch = true;
                    } else {
                        this.passwords.passwordsMatch = false;
                    }
                },
                deep: true
            }
        },

        data() {
            return {
                user: {
                    roles: []
                },
                locked: false,
                dataFetched: false,
                title: '',

                passwords: {
                    new: '',
                    old: '',
                    check: '',
                    passwordsMatch: true
                },

                newRoles: [],

                canSave: true,
            }
        },


    }
</script>

<style scoped>

</style>