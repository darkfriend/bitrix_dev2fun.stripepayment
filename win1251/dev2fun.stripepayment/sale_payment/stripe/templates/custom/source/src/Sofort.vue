<template>
  <div>
    <form
      :action="formAction"
      method="POST"
      id="stripe-payment-form"
      class="stripe_payment__form"
      ref="formStripe"
      @submit.prevent="stripeSubmit"
    >
      <input type="hidden" name="stripeToken" v-model="stripeToken">
      <input type="hidden" name="type" v-model="type">
      <input type="hidden" name="currency" value="eur">
      <input type="hidden" name="amount" v-model="amount">
      <input type="hidden" name="orderId" v-model="orderId">
      <!--<input type="hidden" name="owner[name]" v-model="owner.name">-->
      <!--<input type="hidden" name="owner[email]" v-model="owner.email">-->
      <!--<input type="hidden" name="sofort[country]" v-model="owner.email">-->

      <div class="stripe_payment__row inline">
        <div class="stripe_payment__col col">
          <label for="name">{{locale.name}}</label>
          <input id="name" name="owner[name]" placeholder="Jenny Rosen" ref="sepaName" v-model="owner.name" required>
        </div>
        <div class="stripe_payment__col col">
          <label for="email">{{locale.email}}</label>
          <input id="email" name="owner[email]" type="email" ref="sepaEmail" placeholder="jenny.rosen@example.com" v-model="owner.email" required>
        </div>
      </div>

      <div class="stripe_payment__row inline">
        <div class="stripe_payment__col col">
          <label>{{locale.bank}}</label>
          <select name="sofort[country]" v-model="country" v-if="countryList.length" required>
            <option :value="item.key" v-for="item of countryList">
              {{item.label}}
            </option>
          </select>
          <!--<input id="sofort[country]" name="name" placeholder="Jenny Rosen" ref="sepaName" required>-->
        </div>
      </div>

      <button type="submit" class="stripe_submit">{{locale.submitButton}}</button>

      <!-- Used to display form errors. -->
      <div id="error-message" role="alert" v-html="showErrors"></div>
    </form>
  </div>
</template>

<script>
  // import Inputmask from 'inputmask'

  export default {
    name: 'sofort-stripe',
    props : {
      error: {
        type : String,
        default : ''
      },
      stripeKey : {
        type : String,
        required : true
      },
      orderId: {
        type : Number,
        required : true
      },
      formAction : {
        type : String,
        required : true
      },
      redirectUrl : {
        type : String,
        required : true
      },
      amount : {
        type : Number,
        required : true
      },
      locale: {
        type: Object,
        default: {
          name: 'Name',
          email: 'Email Address',
          bank: 'Bank Country',
          submitButton: 'Submit'
        },
      }
    },
    components: {},
    data() {
      return {
        selected : '',
        stripeToken : '',
        type : 'sofort',
        owner: {
          name: '',
          email: ''
        },
        country: '',
        countryList: [
          {
            key: 'AU',
            label: 'Austria'
          },
          {
            key: 'BE',
            label: 'Belgium'
          },
          {
            key: 'DE',
            label: 'Germany'
          },
          {
            key: 'IT',
            label: 'Italy'
          },
          {
            key: 'NL',
            label: 'Netherlands'
          },
          {
            key: 'ES',
            label: 'Spain'
          }
        ],
        submitEnabled : false,
        numberInput : {
          maxCount : null,
          status : true
        },
        errors : []
      };
    },
    computed: {
      showErrors() {
        let strError = '';
        if(this.error) {
          strError = this.error+"<br>";
        }
        strError += this.errors.join("<br>");
        return strError;
      }
    },
    directives: {},
    methods : {
      stripeSubmit() {
        this.errors = [];
        let stripe = Stripe(this.stripeKey);
        let that = this;
        stripe.createSource({
          type: 'sofort',
          amount: this.amount,
          currency: 'eur',
          owner: {
            name: this.owner.name,
            email: this.owner.email
          },
          redirect: {
            return_url: this.redirectUrl,
          },
          sofort: {
            country: this.country,
          },
          metadata: {
            orderId: that.orderId
          },
        }).then((result) => {
          // handle result.error or result.source
          // console.log('handle');
          // console.log(result);
          if(typeof result.error != 'undefined' && result.error.length>0) {
            that.errors.push(result.error.message);
          } else {
            // console.log(result);
            location.href=result.source.redirect.url;
          }
        });
      },
    }
  }
</script>

<style lang="scss" scoped>
  input,
  .StripeElement {
    height: 40px;
    padding: 10px 12px;

    color: #32325d;
    background-color: white;
    border: 1px solid transparent;
    border-radius: 4px;

    box-shadow: 0 1px 3px 0 #e6ebf1;
    transition: box-shadow 150ms ease;

    &:focus, &--focus {
      box-shadow: 0 1px 3px 0 #cfd7df;
    }
    &--invalid {
      border-color: #fa755a;
    }
    &--webkit-autofill {
      background-color: #fefde5 !important;
    }
  }
</style>