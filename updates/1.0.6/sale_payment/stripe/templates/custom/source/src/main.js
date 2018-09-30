import Vue from 'vue/dist/vue.js';
import Card from './Card';

Vue.config.productionTip = false;

new Vue({
  el: '#cardVue',
  components : {
    'card-stripe' : Card
  }
});
