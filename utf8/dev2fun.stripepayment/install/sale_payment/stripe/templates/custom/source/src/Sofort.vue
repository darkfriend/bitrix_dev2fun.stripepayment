<template>
    <div>
        <div class="stripe_payment__row inline">
            <div class="stripe_payment__col col">
                <label>{{locale.bank}}</label>
                <select name="sofort[country]" v-model="country" v-if="countryList.length" required>
                    <option :value="item.key" v-for="item of countryList">
                        {{item.label}}
                    </option>
                </select>
            </div>
        </div>

        <button
            type="button"
            class="stripe_submit"
            @click.prevent="clickBuy"
        >
            {{locale.submitButton}}
        </button>
    </div>
</template>

<script>
    export default {
        name: 'sofortStripe',
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
            // formAction : {
            //   type : String,
            //   required : true
            // },
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
                    email: 'Email Address',
                    bank: 'Bank Country',
                    submitButton: 'Submit'
                },
            }
        },
        data() {
            return {
                selected: '',
                type: 'sofort',
                country: 'DE',
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
        methods: {
            clickBuy() {
                const stripe = Stripe(this.stripeKey);
                stripe.createSource({
                    type: 'sofort',
                    amount: this.amount,
                    currency: 'eur',
                    redirect: {
                        // return_url: 'https://starsticket.de/order/?ORDER_ID=337',
                        return_url: this.redirectUrl,
                    },
                    sofort: {
                        country: 'DE',
                    },
                    metadata: {
                        orderId: this.orderId
                    },
                }).then((result) => {
                    if (typeof result.source === 'undefined') {
                        this.errors = result.error.message;
                    } else {
                        document.location.href = result.source.redirect.url;
                    }
                });
            },
        }
    }
</script>

<style lang="scss" scoped>

</style>