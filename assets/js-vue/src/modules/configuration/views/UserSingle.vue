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
                                <p class="text-muted" v-if="!isNew">Pozostaw te pola puste aby nie zmieniać hasła.</p>

                                <div class="card-text">
                                    <div class="form-group row">
                                        <label class="col-3 col-form-label">
                                            <span v-if="isNew">Hasło</span><span v-else>Nowe hasło</span>
                                        </label>
                                        <div class="col">
                                            <input type="password" class="form-control" v-model="passwords.new">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-3 col-form-label">
                                            <span v-if="isNew">Powtórz hasło</span><span v-else>Powtórz nowe hasło</span>
                                        </label>
                                        <div class="col">
                                            <input type="password" class="form-control" v-model="passwords.check">
                                        </div>
                                    </div>

                                    <div class="row" v-if="!passwords.passwordsMatch">
                                        <div class="col">
                                            <div class="alert alert-info" v-if="!isNew">
                                                Wartości w polach 'Nowe hasło' i 'Powtórz nowe hasło' muszą być takie same.
                                            </div>
                                            <div class="alert alert-info" v-if="isNew">
                                                Wartości w polach 'Hasło' i 'Powtórz hasło' muszą być takie same.
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group row" v-if="!isNew">
                    <label class="col-3 col-form-label">
                        Aktywny
                    </label>
                    <div class="col">
                        <b-form-checkbox v-model="user.active" switch />
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

                <div class="form-group row">
                    <label class="col-3 col-form-label">
                        Uprawnienia
                    </label>
                    <div class="col">
                        <user-grants
                            :userId="userId"
                            :grants-per-role="grantsPerRole"
                            v-model="grants"
                        />
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <button-plus
                            :button-class="'btn-primary float-right'"
                            :icon-class="'fa fa-floppy-o'"
                            :inner-text="isNew ? 'Dodaj użytkownika' : 'Zapisz zmiany'"
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
    import CollapsibleCard from "@/components/base/CollapsibleCard";
    import users from "../repository/users";
    import UserGrants from "../components/UserGrants"
    import helpers from "@/helpers";
    import Waiting from "@/components/base/Waiting";
    import Routing from "@/api/routing";
    import ButtonPlus from "@/components/base/ButtonPlus";
    import RolePicker from "@/components/users/RolePicker";
    import RoleSelect from '@/modules/configuration/components/roles/RoleSelect'
    import { assignRoles, mergeRoles } from '@/modules/configuration/repository/rolesRepository'
    import {
        fetchGrantUserValues,
        setGrantUserValues,
    } from '../repository/authorizatonRepository'
    export default {
        name: "UserSingle",

        components: {UserGrants, RoleSelect, CollapsibleCard, Waiting, ButtonPlus, RolePicker },

        props: {
            userId: {
                type: Number,
                default: 0
            }
        },

        async mounted() {
            this.dataFetched = false;
            this.locked = true;

            // pobranie danych użytkownika
            if (!this.isNew) {
                await Promise.all([
                    this.fetchUserData(),
                    this.fetchUserGrants()
                ])
            } else {
                 this.title = "Nowy użytkownik";
            }
            this.dataFetched = true;
            this.locked = false;
        },

        computed: {
            isNew() {
                return this.userId === 0
            }
        },

        watch: {
            passwords: {
                handler() {
                    this.passwords.passwordsMatch = this.passwords.new === this.passwords.check;
                },
                deep: true
            },

            newRoles: {
                handler() {
                    return mergeRoles(this.newRoles).then(({data}) => {
                        this.grantsPerRole = data
                        console.log(this.grantsPerRole)
                    })
                },
                deep: true,
            }
        },

        methods: {
            fetchUserData() {
                return users.fetchUser(this.userId)
                    .then(({data}) => {
                        data.customers2Users = (data.customers2Users || []).map(item => ({
                            id: item.id,
                            customer: item.customer.id
                        }))
                        this.user = data;
                        this.title = helpers.userName(this.user);
                    })
            },

            fetchUserGrants() {
                return fetchGrantUserValues(this.userId).then(({data}) => {
                    this.grants = data
                })
            },

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
                }

                let fn = this.isNew ? users.addUser : users.storeUser;

                return fn(userData)
                    .then(({data}) => {
                        const userId = this.isNew ? data.id : this.userId;
                        if (userId) {
                            return Promise.all([
                                assignRoles(userId, this.newRoles),
                                setGrantUserValues(userId, this.grants)
                            ])
                        }
                    })
                    .then(() => {
                        // gdy dodano nowego użytkownika to przekieruj do listy
                        if (this.isNew) {
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
        },

        data() {
            return {
                user: {
                    id: null,
                    customers2users: [],
                    email: '',
                    firstName: '',
                    lastName: '',
                    roles: [],
                    userFullName: null,
                    active: true,
                },
                locked: false,
                dataFetched: false,
                title: '',
                passwords: {
                    new: '',
                    check: '',
                    passwordsMatch: true
                },
                newRoles: [],
                grants: [],
                grantsPerRole: [],
                canSave: true,
            }
        },
    }
</script>

<style scoped>

</style>