require('./bootstrap');

window.Vue = require('vue').default;
import ClickOutside from 'vue-click-outside';

//Vue.component('example-component', require('./components/ExampleComponent.vue').default);
//import { Service } from './services/service';
//import Auth from './services/auth';
//import Api from './services/api';

const app = new Vue({
    el: '#app',
    components: {},
    data: () => ({
        errors: [],
        saving: false,
        loading: false,
    }),
    filters: {},
    directives: {
        ClickOutside,
    },
    computed: {},
    methods: {},
    created() {},
    mounted() {},
});
