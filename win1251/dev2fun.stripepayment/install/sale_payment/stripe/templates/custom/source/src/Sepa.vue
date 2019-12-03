<template>
    <div>
        <form
            :action="formAction"
            method="POST"
            id="stripe-payment-form"
            class="stripe_payment__form"
            ref="formStripe"
        >
            <input type="hidden" name="stripeToken" :value="stripeToken">
            <input type="hidden" name="type" v-model="type">
            <input type="hidden" name="currency" v-model="currency">
            <input type="hidden" name="orderId" v-model="orderId">
            <!--<input type="hidden" name="owner[name]" v-model="owner.name">-->
            <!--<input type="hidden" name="owner[email]" v-model="owner.email">-->

            <div class="stripe_payment__row inline">
                <div class="stripe_payment__col col">
                    <label for="name">
                        {{locale.name}}
                    </label>
                    <input id="name" name="owner[name]" placeholder="Jenny Rosen" ref="sepaName" v-model="owner.name"
                           required>
                </div>
                <div class="stripe_payment__col col">
                    <label for="email">
                        {{locale.email}}
                    </label>
                    <input id="email" name="owner[email]" type="email" ref="sepaEmail"
                           placeholder="jenny.rosen@example.com" v-model="owner.email" required>
                </div>
            </div>

            <div class="stripe_payment__row form-row">
                <label for="iban-element">
                    IBAN
                </label>
                <div id="iban-element" ref="cardElement">
                    <!-- A Stripe Element will be inserted here. -->
                </div>
            </div>
            <div id="bank-name"></div>

            <button type="submit" class="stripe_submit" :disabled="!submitEnabled">{{locale.submitButton}}</button>

            <!-- Used to display form errors. -->
            <div id="error-message" role="alert" v-html="showErrors"></div>
        </form>
    </div>
</template>

<script>
    import Inputmask from 'inputmask'

    export default {
        name: 'sepa-stripe',
        props: {
            error: {
                type: String,
                default: ''
            },
            stripeKey: {
                type: String,
                required: true
            },
            orderId: {
                type: Number,
                required: true
            },
            currency: {
                type: String,
                default: 'eur'
            },
            formAction: {
                type: String,
                required: true
            },
            locale: {
                type: Object,
                default: {
                    name: 'Name',
                    email: 'Email Address',
                    submitButton: 'Submit'
                },
            }
        },
        components: {},
        data() {
            return {
                selected: '',
                stripeToken: '',
                type: 'sepa',
                owner: {
                    name: '',
                    email: ''
                },
                card: {
                    number: null,
                    month: null,
                    year: null,
                    cvc: null,
                },
                submitEnabled: false,
                numberInput: {
                    maxCount: null,
                    status: true
                },
                errors: []
            };
        },
        computed: {
            showErrors() {
                let strError = '';
                if (this.error) {
                    strError = this.error + "<br>";
                }
                strError += this.errors.join("<br>");
                return strError;
            }
        },
        directives: {
            'input-mask': {
                bind: function (el, binding) {
                    Inputmask(binding.value).mask(el);
                }
            }
        },
        methods: {
            stripeSourceHandler(token) {
                // Insert the token ID into the form so it gets submitted to the server
                // let form = document.getElementById('payment-form');
                // let hiddenInput = document.createElement('input');
                // hiddenInput.setAttribute('type', 'hidden');
                // hiddenInput.setAttribute('name', 'stripeToken');
                // hiddenInput.setAttribute('value', token.id);
                // this.$refs.formStripe.appendChild(hiddenInput);
                // console.log(token.id);
                // console.log(token);
                this.stripeToken = token.id;
                // this.owner.name = token.owner.name;
                // this.owner.email = token.owner.email;
                this.currency = token.currency;
                // $(this.$refs.formStripe).serialize();
                // console.log(token);
                // Submit the form
                let that = this;
                setTimeout(() => {
                    that.$refs.formStripe.submit();
                }, 1000);
                // this.$refs.formStripe.submit();
            }
        },
        mounted() {
            // Create a Stripe client.
            let stripe = Stripe(this.stripeKey);
            // this.stripeObj = Stripe(this.stripeKey);
            // let stripe = Stripe('pk_test_TYooMQauvdEDq54NiTphI7jx');

            // Create an instance of Elements.
            let elements = stripe.elements();
            // let elements = this.stripeObj.elements();

            // Custom styling can be passed to options when creating an Element.
            // (Note that this demo uses a wider set of styles than the guide below.)
            let style = {
                base: {
                    color: '#32325d',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    },
                    ':-webkit-autofill': {
                        color: '#32325d',
                    },
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a',
                    ':-webkit-autofill': {
                        color: '#fa755a',
                    },
                }
            };

            // Create an instance of the iban Element.
            let iban = elements.create('iban', {
                style: style,
                supportedCountries: ['SEPA']
            });
            // this.stripeCard = elements.create('card', {style: style});

            // Add an instance of the iban Element into the `iban-element` <div>.
            // card.mount('#card-element');
            iban.mount(this.$refs.cardElement);

            // let errorMessage = document.getElementById('error-message');
            let bankName = document.getElementById('bank-name');

            iban.on('change', function (event) {
                // Handle real-time validation errors from the iban Element.
                if (event.error) {
                    that.errors.push(event.error.message);
                    // errorMessage.textContent = event.error.message;
                    // errorMessage.classList.add('visible');
                } else {
                    that.errors = [];
                    that.submitEnabled = true;
                    // errorMessage.classList.remove('visible');
                }

                // Display bank name corresponding to IBAN, if available.
                if (event.bankName) {
                    bankName.textContent = event.bankName;
                    bankName.classList.add('visible');
                } else {
                    bankName.classList.remove('visible');
                }
            });

            let that = this;
            let form = document.getElementById('stripe-payment-form');
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                // showLoading();
                BX.showWait();

                let sourceData = {
                    type: 'sepa_debit',
                    currency: that.currency,
                    metadata: {
                        orderId: that.orderId
                    },
                    owner: {
                        // name: document.querySelector('input[name="name"]').value,
                        // email: document.querySelector('input[name="email"]').value,
                        // name: that.$refs.sepaName.value,
                        // email: that.$refs.sepaEmail.value,
                        name: that.owner.name,
                        email: that.owner.email,
                    },
                    mandate: {
                        // Automatically send a mandate notification email to your customer
                        // once the source is charged.
                        notification_method: 'email',
                    }
                };

                // Call `stripe.createSource` with the iban Element and additional options.
                stripe.createSource(iban, sourceData).then(function (result) {
                    // console.log(result);
                    BX.closeWait();
                    if (result.error) {
                        // Inform the customer that there was an error.
                        that.errors.push(event.error.message);
                        // errorMessage.textContent = result.error.message;
                        // errorMessage.classList.add('visible');
                        // stopLoading();
                    } else {
                        // Send the Source to your server to create a charge.
                        // errorMessage.classList.remove('visible');
                        that.errors = [];
                        that.stripeSourceHandler(result.source);
                    }
                });
            });

        },
    }
</script>

<style lang="scss" scoped>
    .stripe_submit {
        &:disabled {
            opacity: 0.8;
            cursor: no-drop;
        }
    }

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