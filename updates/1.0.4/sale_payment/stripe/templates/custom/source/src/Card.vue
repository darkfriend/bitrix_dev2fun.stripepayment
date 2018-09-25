<script>
import Vue from 'vue';
import axios from 'axios';
import Inputmask from 'inputmask'
import cardValid from 'card-validator'

// Vue.use(VueInputMask);

// Vue.directive('input-mask', {
//   bind: function(el,binding) {
//     console.log(binding);
//     // Inputmask()
//     Inputmask(binding.value).mask(el);
//   },
// });

export default {
  name: 'card-stripe',
  props : {
    stripeKey : {
      type : String,
      required : true
    },
    formAction : {
      type : String,
      //default : '/',
      required : true
    },
  },
  components: {},
  data() {
    return {
      selected : '',
      stripeToken : '',
      card : {
        number : null,
        month : null,
        year : null,
        cvc : null,
      },
      errors : []
    };
  },
  computed: {
    showErrors() {
      return this.errors.join("<br>");
    }
  },
  directives: {
    'input-mask': {
      bind: function(el,binding) {
        Inputmask(binding.value).mask(el);
      },
    }
  },
  methods : {
    cardValid(){
      console.log(this.formAction);
      let cardInfo = cardValid.number(this.card.number);
      console.log(cardInfo);
      if(cardInfo && cardInfo.card) {
        Inputmask('9',{
          repeat: cardInfo.card.code.size
        }).mask(this.$refs.cardCvc);
      }
    },
    stripeSubmit(e) {
      // let queryString = {
      //   CARD: this.card
      // };
      this.errors = [];
      if(!this.card.number)
        this.errors.push('Укажите номер');
      if(!this.card.month)
        this.errors.push('Укажите месяц');
      if(!this.card.year)
        this.errors.push('Укажите год');
      if(!this.card.cvc)
        this.errors.push('Укажите cvc');

      Stripe.card.createToken(this.$refs.formStripe, this.stripeResponseHandler);

      if(this.errors.length>0) {
        e.preventDefault();
      } else {
        return true;
      }
      // this.query(queryString);
    },
    query(queryString) {
      let that = this;
      axios.post(this.formAction,queryString)
        .then( responce => {
          console.log(responce);
        })
        .catch(error=>{
          that.errors.push(error);
          console.log(error);
        });
    },
    stripeResponseHandler(status, response) {
      // Grab the form:
      // var $form = $('#stripe-payment-form');
      if (response.error) { // Problem!
        // Show the errors on the form:
        this.errors.push(response.error.message);
        return false;
        // $form.find('.payment-errors').text(response.error.message);
        // $form.find('.stripe-submit').prop('disabled', false); // Re-enable submission
      } else {
        // Get the token ID:
        // var token = response.id;
        this.stripeToken = response.id;
        // Insert the token ID into the form so it gets submitted to the server:
        // $form.append($('<input type="hidden" name="stripeToken">').val(token));
        // Submit the form:
        // $form.get(0).submit();
        this.$refs.formStripe.submit();
        // return true;
      }
    },
    created() {
      Stripe.setPublishableKey(this.stripeKey);
    }
  }
}
</script>

<template>
  <div>
    <form :action="formAction" method="POST" id="stripe-payment-form" class="stripe_payment__form" ref="formStripe">
      <input type="hidden" name="stripeToken" v-model="stripeToken">
      <span v-if="errors.length>0" class="payment-errors text-error text-center">
        <span v-html="showErrors"></span>
      </span>
      <div class="form-row">
        <label>
          <span class="stripe_payment__label">Card Number</span>
          <input type="text" size="20" name="CARD[number]" v-model="card.number" data-stripe="number" class="stripe_payment__input stripe_payment__input_card" v-input-mask="'9999 9999 9999 9999'" @change="cardValid">
        </label>
      </div>

      <div class="form-row">
        <div class="form-left">
          <span class="stripe_payment__label">Date on which the credit card</span>
          <div class="form-row">
            <input type="text" size="2" name="CARD[month]" v-model="card.month" data-stripe="exp_month" class="stripe_payment__input stripe_payment__input_date" v-input-mask="{mask: '99', alias: 'mm', placeholder:'mm'}">
            <span class="stripe_payment__slash"> / </span>
            <input type="text" size="2" name="CARD[year]" v-model="card.year" data-stripe="exp_year" class="stripe_payment__input stripe_payment__input_date" v-input-mask="{mask: '9999', alias: 'yyyy', placeholder:'yyyy'}">
          </div>
        </div>
        <div class="form-right">
          <label>
            <span class="stripe_payment__label">CVC</span>
            <input type="text" size="4" name="CARD[cvc]" v-model="card.cvc" data-stripe="cvc" class="stripe_payment__input stripe_payment__input_cvc" v-input-mask="'999'" ref="cardCvc">
          </label>
        </div>
      </div>
      <input type="submit" class="stripe-submit" value="PROCEED" @click="stripeSubmit">
    </form>
  </div>
</template>

<style lang="scss"></style>