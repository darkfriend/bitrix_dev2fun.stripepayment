<template>
    <div class="stripe-root-form">
        <p v-if="error" class="error">{{error}}</p>
        <select v-model="mode" v-if="mode">
            <option :value="item.key" v-for="item in modeList">
                {{item.value}}
            </option>
        </select>
        <card-stripe
            :session-url="sessionUrl"
            :stripe-key="stripeKey"
            :locale="locale.card"
            :orderId="orderId"
            v-if="mode=='card'"
        ></card-stripe>
<!--        <sepa-stripe-->
<!--            :stripe-key="stripeKey"-->
<!--            :error="error"-->
<!--            :form-action="formAction"-->
<!--            :currency="currencyEur"-->
<!--            :locale="locale.sepa"-->
<!--            :orderId="orderId"-->
<!--            v-if="mode=='sepa'"-->
<!--        ></sepa-stripe>-->
        <sofort-stripe
            :stripe-key="stripeKey"
            :amount="amountEur"
            :redirectUrl="params.sofort.redirectUrl"
            :locale="locale.sofort"
            :orderId="orderId"
            v-if="mode=='sofort'"
        ></sofort-stripe>
        <giropay-stripe
            :stripe-key="stripeKey"
            :currency="currencyEur"
            :amount="amountEur"
            :redirectUrl="params.giropay.redirectUrl"
            :locale="locale.giropay"
            :orderId="orderId"
            v-if="mode=='giropay'"
        ></giropay-stripe>
    </div>
</template>

<script>
    import 'babel-polyfill';
    import Card from './Card';
    // import Sepa from './Sepa';
    import Sofort from './Sofort';
    import Giropay from './Giropay';

    export default {
        name: "formStripe",
        components: {
            'card-stripe': Card,
            // 'sepa-stripe': Sepa,
            'sofort-stripe': Sofort,
            'giropay-stripe': Giropay,
        },
        props: {
            sessionUrl: {
                type: String,
                required: true
            },
            stripeKey: {
                type: String,
                required: true
            },
            currency: {
                type: String,
                required: true
            },
            currencyEur: {
                type: String,
                required: true
            },
            amount: {
                type: Number,
                required: true
            },
            amountEur: {
                type: Number,
                required: true
            },
            orderId: {
                type: Number,
                required: true
            },
            stripeClientSecret: String,
            stripeSource: String,
            params: {
                type: Object,
                default: {
                    sofort: {
                        redirectUrl: ''
                    },
                }
            },
            modeList: {
                type: Array,
                default: [
                    {
                        key: 'card',
                        value: 'Card'
                    },
                    {
                        key: 'sepa',
                        value: 'Sepa Debit'
                    },
                    {
                        key: 'sofort',
                        value: 'Sofort'
                    },
                    {
                        key: 'giropay',
                        value: 'Giropay'
                    }
                ]
            },
            locale: {
                type: Object,
                default: {
                    card: {
                        cardLabel: 'Credit or debit card',
                        submitButton: 'Submit'
                    },
                    sepa: {
                        name: 'Name',
                        email: 'Email Address',
                        submitButton: 'Submit'
                    },
                    sofort: {
                        name: 'Name',
                        email: 'Email Address',
                        bank: 'Bank Country',
                        submitButton: 'Submit'
                    },
                    giropay: {
                        name: 'Name',
                        submitButton: 'Submit'
                    },
                }
            }
        },
        data() {
            return {
                error: '',
                mode: 'card',
            };
        },
        methods: {
            clickBuy() {
                this.sendClickBuy();
            },
            async sendClickBuy() {
                let session = await this.getSessionId();
                if (!session) return;
                const stripe = Stripe(this.stripeKey);
                stripe.redirectToCheckout({
                    sessionId: session.id
                }).then((result) => {
                    this.error = result.error.message;
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

<style lang="scss">
    .error {
        color: darkred;
        margin: 20px 0;
    }

    .stripe-root-form {
        padding: 0 25px;
    }
</style>