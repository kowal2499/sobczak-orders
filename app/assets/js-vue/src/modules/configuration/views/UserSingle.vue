<template>
    <div>
        <waiting :description="$t('user.form.loading')" v-if="!dataFetched"></waiting>

        <template v-if="dataFetched">
            <SectionBlock class="section-gap user-section">
                <h6 class="section-title">{{ $t('user.form.sectionDetails') }}</h6>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label">{{ $t('user.form.firstName') }}</label>
                    <div class="col">
                        <input type="text" class="form-control" v-model="user.firstName">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label">{{ $t('user.form.lastName') }}</label>
                    <div class="col">
                        <input type="text" class="form-control" v-model="user.lastName">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label">{{ $t('user.form.email') }}</label>
                    <div class="col">
                        <input type="text" class="form-control" v-model="user.email">
                    </div>
                </div>

                <div class="form-group row mb-0" v-if="!isNew">
                    <label class="col-lg-3 col-form-label">{{ $t('user.form.active') }}</label>
                    <div class="col d-flex align-items-center">
                        <b-form-checkbox v-model="user.active" switch />
                    </div>
                </div>
            </SectionBlock>

            <SectionBlock class="user-section">
                <h6 class="section-title">{{ $t('user.form.sectionPassword') }}</h6>
                <p v-if="!isNew" class="section-hint">{{ $t('user.form.passwordKeepHint') }}</p>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label">
                        {{ isNew ? $t('user.form.password') : $t('user.form.newPassword') }}
                    </label>
                    <div class="col">
                        <input type="password" class="form-control" v-model="passwords.new">
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <label class="col-lg-3 col-form-label">
                        {{ isNew ? $t('user.form.passwordRepeat') : $t('user.form.newPasswordRepeat') }}
                    </label>
                    <div class="col">
                        <input
                            type="password"
                            class="form-control"
                            :class="{ 'is-invalid': !passwords.passwordsMatch }"
                            v-model="passwords.check"
                        >
                        <div class="invalid-feedback">{{ $t('user.form.passwordMismatch') }}</div>
                    </div>
                </div>
            </SectionBlock>

            <SectionBlock class="user-section">
                <h6 class="section-title">{{ $t('user.form.sectionRoles') }}</h6>

                <div class="form-group row">
                    <label class="col-lg-3 col-form-label">{{ $t('user.form.legacyRoles') }}</label>
                    <div class="col">
                        <role-picker v-model="user" />
                    </div>
                </div>

                <div class="form-group row mb-0">
                    <label class="col-lg-3 col-form-label">{{ $t('user.form.authRoles') }}</label>
                    <div class="col">
                        <role-select
                            :userId="userId"
                            v-model="newRoles"
                        />
                    </div>
                </div>
            </SectionBlock>

            <SectionBlock class="user-section">
                <h6 class="section-title">{{ $t('user.form.sectionGrants') }}</h6>
                <user-grants
                    :userId="userId"
                    :grants-per-role="grantsPerRole"
                    v-model="grants"
                />

                <div class="form-actions">
                    <router-link :to="{ name: 'users' }" class="btn btn-outline-secondary">
                        {{ $t('_cancel') }}
                    </router-link>
                    <button type="button" class="btn btn-primary" :disabled="!canSubmit" @click="save">
                        <i v-if="locked" class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                        {{ submitLabel }}
                    </button>
                </div>
            </SectionBlock>
        </template>
    </div>
</template>

<script>
    import users from "../repository/users";
    import UserGrants from "../components/UserGrants"
    import Waiting from "@/components/base/Waiting";
    import RolePicker from "@/components/users/RolePicker";
    import RoleSelect from '@/modules/configuration/components/roles/RoleSelect'
    import { assignRoles, mergeRoles } from '@/modules/configuration/repository/rolesRepository'
    import {
        fetchGrantUserValues,
        setGrantUserValues,
    } from '../repository/authorizatonRepository'
    export default {
        name: "UserSingle",

        components: {UserGrants, RoleSelect, Waiting, RolePicker },

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
            }
            this.dataFetched = true;
            this.locked = false;
        },

        computed: {
            isNew() {
                return this.userId === 0
            },

            canSubmit() {
                return this.dataFetched && !this.locked && this.passwords.passwordsMatch && this.user.roles.length > 0
            },

            submitLabel() {
                return this.isNew ? this.$t('user.form.add') : this.$t('user.form.saveChanges')
            },

            // the page header has its own save button, so it needs to know when saving is allowed
            formState() {
                return { canSubmit: this.canSubmit, busy: this.locked, label: this.submitLabel }
            }
        },

        watch: {
            formState: {
                handler(state) {
                    this.$emit('state', state)
                },
                immediate: true,
            },

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
                            this.$router.push({ name: 'users' });
                        } else {
                            EventBus.$emit('message', {
                                type: 'success',
                                content: this.$t('user.form.saved')
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
.user-section + .user-section {
    margin-top: 1rem;
}

.section-title {
    margin: 0 0 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--colorPrimary);
}

.section-hint {
    margin: -0.25rem 0 0.75rem;
    color: var(--colorInkMuted);
}
</style>