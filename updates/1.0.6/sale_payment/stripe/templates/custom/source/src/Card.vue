<script>
import Inputmask from 'inputmask'
import cardValid from 'card-validator'

export default {
  name: 'card-stripe',
  props : {
    stripeKey : {
      type : String,
      required : true
    },
    formAction : {
      type : String,
      required : true
    },
    labels: {
      inputs: {
        number:'Card Number',
        date:'Date on which the credit card',
        cvc:'CVC',
        button:'PROCEED'
      },
      emptyErrors: {
        number: 'Select number card!',
        month: 'Select month!',
        year: 'Select year!',
        cvc: 'Select CVC!'
      }
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
    cardValidOnKey (e) {
      // console.log(e.key);
      if(!/(\d+)/.test(e.key)) return;
      return this.cardValid(e);
    },
    cardValid(e){
      // return;
      if(!this.card.number) {
        this.numberInput.status = true;
        this.numberInput.maxCount = null;
        return;
      }
      let prepareNumber = this.card.number.replace(/ /g,'');
      let cardNumberLength = prepareNumber.length;
      let maxNumberCount = null;
      // console.log(cardNumberLength);
      if(cardNumberLength<4) return;
      // console.log(this.formAction);
      let cardInfo = cardValid.number(this.card.number);
      // console.log(cardInfo);
      // console.log(cardInfo);
      if(cardInfo) {
        this.submitEnabled = cardInfo.isValid;
        if(!cardInfo.isPotentiallyValid) {
          e.preventDefault();
          return false;
        }
        // 378282246310005
        if(cardInfo.card) {
          // console.log(cardNumberLength);
          maxNumberCount = Math.max.apply(null,cardInfo.card.lengths);
          // console.log(maxNumberCount);
          if(maxNumberCount>16) maxNumberCount = 16;
          this.numberInput.maxCount = maxNumberCount;
          // console.log(maxNumberCount);
          let resultMask = '9999 '.repeat(Math.ceil(maxNumberCount/4)).trim();
          Inputmask('9',{
            repeat: cardInfo.card.code.size
          }).mask(this.$refs.cardCvc);
          Inputmask({
            // mask:'9{1,4} ',
            mask:resultMask,
            // repeat:2,
            placeholder:'',
            showMaskOnHover: false,
            showMaskOnFocus: false
          }).mask(this.$refs.cardNumber);
          if(cardNumberLength>=maxNumberCount) {
            this.numberInput.status = false;
            e.preventDefault();
            return false;
          }
        }
      }
    },
    stripeSubmit(e) {
      this.errors = [];
      if(!this.card.number)
        this.errors.push(this.emptyErrors.number);
      if(!this.card.month)
        this.errors.push(this.emptyErrors.month);
      if(!this.card.year)
        this.errors.push(this.emptyErrors.year);
      if(!this.card.cvc)
        this.errors.push(this.emptyErrors.cvc);

      Stripe.card.createToken(this.$refs.formStripe, this.stripeResponseHandler);

      if(this.errors.length>0) {
        this.errors.push(this.errors.join('\n'));
        console.error(this.errors.join('\n'));
        e.preventDefault();
      } else {
        return true;
      }
    },
    stripeResponseHandler(status, response) {
      if (response.error) {
        this.errors.push(response.error.message);
        return false;
      } else {
        this.stripeToken = response.id;
        this.$refs.formStripe.submit();
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
          <span class="stripe_payment__label">{{this.labels.inputs.number}}</span>
          <input type="text" size="20" name="CARD[number]" v-model="card.number" data-stripe="number" class="stripe_payment__input stripe_payment__input_card" v-input-mask="{mask:'9{1,4} ',repeat:'*',placeholder:''}" @change="cardValid" @keydown="cardValidOnKey" ref="cardNumber">
        </label>
      </div>

      <div class="form-row">
        <div class="form-left">
          <span class="stripe_payment__label">{{this.labels.inputs.date}}</span>
          <div class="form-row">
            <input type="text" size="2" name="CARD[month]" v-model="card.month" data-stripe="exp_month" class="stripe_payment__input stripe_payment__input_date" v-input-mask="{mask: '99', alias: 'mm', placeholder:'mm'}">
            <span class="stripe_payment__slash"> / </span>
            <input type="text" size="2" name="CARD[year]" v-model="card.year" data-stripe="exp_year" class="stripe_payment__input stripe_payment__input_date" v-input-mask="{mask: '9999', alias: 'yyyy', placeholder:'yyyy'}">
          </div>
        </div>
        <div class="form-right">
          <label>
            <span class="stripe_payment__label">{{this.labels.inputs.cvc}}</span>
            <input type="text" size="4" name="CARD[cvc]" v-model="card.cvc" data-stripe="cvc" class="stripe_payment__input stripe_payment__input_cvc" v-input-mask="'999'" ref="cardCvc">
          </label>
        </div>
      </div>
      <input type="submit" class="stripe-submit" :value="this.labels.inputs.button" @click="stripeSubmit" :disabled="!submitEnabled">
    </form>
  </div>
</template>

<style lang="scss"></style>