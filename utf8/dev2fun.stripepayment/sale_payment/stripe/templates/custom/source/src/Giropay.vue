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

      <div class="stripe_payment__row inline">
        <div class="stripe_payment__col col">
          <label for="name">{{this.locale.name}}</label>
          <input id="name" name="owner[name]" placeholder="Jenny Rosen" ref="sepaName" v-model="owner.name" required>
        </div>
      </div>

      <button type="submit" class="stripe_submit">{{this.locale.submitButton}}</button>

      <!-- Used to display form errors. -->
      <div id="error-message" role="alert" v-html="showErrors"></div>
    </form>
  </div>
</template>

<script>

  export default {
    name: 'giropay-stripe',
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
          submitButton: 'Submit'
        },
      }
    },
    components: {},
    data() {
      return {
        selected : '',
        stripeToken : '',
        type : 'giropay',
        owner: {
          name: '',
        },
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
    methods : {
      stripeSubmit() {
        this.errors = [];
        let stripe = Stripe(this.stripeKey);
        let that = this;
        stripe.createSource({
          type: that.type,
          amount: this.amount,
          currency: 'eur',
          owner: {
            name: this.owner.name,
          },
          redirect: {
            return_url: this.redirectUrl,
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