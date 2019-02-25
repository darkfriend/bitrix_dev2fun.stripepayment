<template>
  <div>
    <form
      :action="formAction"
      method="POST"
      id="stripe-payment-form"
      class="stripe_payment__form"
      ref="formStripe"
    >
      <input type="hidden" name="stripeToken" v-model="stripeToken">
      <input type="hidden" name="orderId" v-model="orderId">
      <input type="hidden" name="type" value="card">
      <!--<span v-if="errors.length>0" class="payment-errors text-error text-center">-->
        <!--<span v-html="showErrors"></span>-->
      <!--</span>-->

      <div class="form-row">
        <label for="card-element">
          {{locale.cardLabel}}
        </label>
        <div id="card-element" ref="cardElement">
          <!-- A Stripe Element will be inserted here. -->
        </div>

        <!-- Used to display form errors. -->
        <div id="card-errors" role="alert" v-html="showErrors"></div>
      </div>

      <button type="submit" class="stripe_submit">{{locale.submitButton}}</button>
    </form>
  </div>
</template>

<script>

export default {
  name: 'card-stripe',
  props : {
    error: {
      type : String,
      default : ''
    },
    orderId: {
      type : Number,
      required : true
    },
    stripeKey : {
      type : String,
      required : true
    },
    formAction : {
      type : String,
      required : true
    },
    locale: {
      type: Object,
      default: {
        cardLabel: 'Credit or debit card',
        submitButton: 'Submit'
      },
    }
  },
  components: {},
  data() {
    return {
      // stripeObj : {},
      // stripeCard : {},
      stripeToken : '',
      submitEnabled : false,
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
    stripeTokenHandler(token) {
      // this.$refs.formStripe.appendChild(hiddenInput);
      this.stripeToken = token.id;
      // Submit the form
      // this.$refs.formStripe.submit();
      let that = this;
      setTimeout(()=>{
        that.$refs.formStripe.submit();
      },1000);
    }
  },
  mounted() {
    // Create a Stripe client.
    let stripe = Stripe(this.stripeKey);

    // Create an instance of Elements.
    let elements = stripe.elements();
    // let elements = this.stripeObj.elements();

    // Custom styling can be passed to options when creating an Element.
    // (Note that this demo uses a wider set of styles than the guide below.)
    let style = {
      base: {
        color: '#32325d',
        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
        fontSmoothing: 'antialiased',
        fontSize: '16px',
        '::placeholder': {
          color: '#aab7c4'
        }
      },
      invalid: {
        color: '#fa755a',
        iconColor: '#fa755a'
      }
    };

    // Create an instance of the card Element.
    let card = elements.create('card', {style: style});
    // this.stripeCard = elements.create('card', {style: style});

    // Add an instance of the card Element into the `card-element` <div>.
    // card.mount('#card-element');
    card.mount(this.$refs.cardElement);

    // Handle real-time validation errors from the card Element.
    card.addEventListener('change', function(event) {
      let displayError = document.getElementById('card-errors');
      if (event.error) {
        displayError.textContent = event.error.message;
      } else {
        displayError.textContent = '';
      }
    });

    // Handle form submission.
    let form = document.getElementById('stripe-payment-form');
    // this.$refs.formStripe.addEventListener('submit', function(event) {
    let that = this;
    form.addEventListener('submit', function(event) {
      event.preventDefault();
      stripe.createToken(card).then(function(result) {
        if (result.error) {
          // Inform the user if there was an error.
          let errorElement = document.getElementById('card-errors');
          errorElement.textContent = result.error.message;
        } else {
          // Send the token to your server.
          that.stripeTokenHandler(result.token);
        }
      });
    });

  },
}
</script>

<style lang="scss" scoped>
  .stripe_submit{
    &:disabled {
      opacity: 0.8;
      cursor: no-drop;
    }
  }
  .StripeElement {
    box-sizing: border-box;
    height: 40px;
    padding: 10px 12px;
    border: 1px solid transparent;
    border-radius: 4px;
    background-color: white;
    box-shadow: 0 1px 3px 0 #e6ebf1;
    -webkit-transition: box-shadow 150ms ease;
    transition: box-shadow 150ms ease;
    min-width: 350px;
    &--focus {
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