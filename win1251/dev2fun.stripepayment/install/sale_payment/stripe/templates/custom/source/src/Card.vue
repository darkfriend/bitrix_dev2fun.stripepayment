<template>
    <div>
        <label>{{locale.cardLabel}}</label>
        <button type="button" @click.prevent="clickBuy">
            {{locale.submitButton}}
        </button>
        <p v-if="error.length" style="color: #920a0a">
            <br>
            Error: {{error}}
        </p>
    </div>
</template>

<script>
    export default {
        name: 'cardStripe',
        props: {
            // error: {
            //   type : String,
            //   default : ''
            // },
            sessionUrl: {
                type: String,
                required: true
            },
            orderId: {
                type: Number,
                required: true
            },
            stripeKey: {
                type: String,
                required: true
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
                // stripeToken : '',
                // submitEnabled : false,
                errors: [],
                error: '',
            };
        },
        computed: {
            showErrors() {
                let strError = '';
                if (this.errors) {
                    strError = this.errors + "<br>";
                }
                // strError += this.errors.join("<br>");
                return strError;
            }
        },
        methods: {
            clickBuy() {
                this.sendClickBuy();
            },
            async sendClickBuy() {
                let session = await this.getSessionId();
                if(typeof session === "object" && typeof session.error !== "undefined") {
                    console.warn(session);
                    this.error = session.error;
                    return;
                }
                if (!session) return;
                const stripe = Stripe(this.stripeKey);
                stripe.redirectToCheckout({
                    // Make the id field from the Checkout Session creation API response
                    // available to this file, so you can provide it as parameter here
                    // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
                    sessionId: session.id,
                }).then((result) => {
                    this.errors = result.error.message;
                });
            },
            async getSessionId() {
                let resp = await fetch(this.sessionUrl);
                let text = await resp.text();
                return JSON.parse(text.replace(/(\<.*\>)/g, ''));
            },
        },
    }
</script>

<style lang="scss" scoped>

</style>