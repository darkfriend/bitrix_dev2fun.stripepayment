import Vue from 'vue/dist/vue.js';
// import Card from './Card';
// import Card from './Card';
import FormStripe from './Stripe';

Vue.config.productionTip = false;

new Vue({
    el: '#cardVue',
    components: {
        // 'card-stripe' : Card,
        'form-stripe': FormStripe
    }
});
