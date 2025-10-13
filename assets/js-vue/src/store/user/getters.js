export default {
    users: (state) => active => (state.users || []).filter(user => user.active === active),
}