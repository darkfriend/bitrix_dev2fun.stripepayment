<template>
    <div>
        <form
            method="POST"
            id="stripe-payment-form"
            class="stripe_payment__form"
            ref="formStripe"
            @submit.prevent="stripeSubmit"
        >
            <div id="error-message" role="alert" v-html="showErrors"></div>

            <div class="stripe_payment__row inline">
                <div class="stripe_payment__col col">
                    <label for="name">{{this.locale.name}}</label>
                    <input id="name" name="owner[name]" placeholder="Jenny Rosen" ref="sepaName" v-model="owner.name"
                           required>
                </div>
            </div>

            <button type="submit" class="stripe_submit">
                {{this.locale.submitButton}}
            </button>
        </form>
    </div>
</template>

<script>
    export default {
        name: 'giropay-stripe',
        props: {
            stripeKey: {
                type: String,
                required: true
            },
            orderId: {
                type: Number,
                required: true
            },
            redirectUrl: {
                type: String,
                required: true
            },
            amount: {
                type: Number,
                required: true
            },
            locale: {
                type: Object,
                default: {
                    name: 'Name',
                    submitButton: 'Submit'
                },
            }
        },
        data() {
            return {
                selected: '',
                stripeToken: '',
                type: 'giropay',
                owner: {
                    name: '',
                },
                submitEnabled: false,
                // numberInput: {
                // 	maxCount: null,
                // 	status: true
                // },
                errors: []
            };
        },
        computed: {
            showErrors() {
                let strError = '';
                // if (this.errors) {
                // 	strError = this.errors + "<br>";
                // }
                strError += this.errors.join("<br>");
                return strError;
            }
        },
        methods: {
            stripeSubmit() {
                if (!this.owner.name) {
                    this.errors.push('Name is empty!');
                }
                const stripe = Stripe(this.stripeKey);
                stripe.createSource({
                    type: 'giropay',
                    amount: this.amount,
                    currency: 'eur',
                    owner: {
                        name: this.owner.name,
                    },
                    redirect: {
                        return_url: this.redirectUrl,
                    },
                    metadata: {
                        orderId: this.orderId
                    },
                }).then(function (result) {
                    // handle result.error or result.source
                    if (typeof result.source === 'undefined') {
                        this.errors.push(result.error.message);
                    } else {
                        document.location.href = result.source.redirect.url;
                    }
                }).catch((error) => {
                    this.errors.push(error);
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