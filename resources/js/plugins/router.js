import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter)
import Test from '../components/Test'

const routes = [

    {
        path: '/test',
        name: 'test',
        component: Test,
    },
]

export default new VueRouter({
    routes
})
