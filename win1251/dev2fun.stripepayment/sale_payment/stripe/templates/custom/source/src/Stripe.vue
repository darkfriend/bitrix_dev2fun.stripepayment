<template>
	<div class="stripe-root-form">
		<select v-model="mode" v-if="mode">
			<option :value="item.key" v-for="item of modeList">
				{{item.value}}
			</option>
		</select>
		<card-stripe
			:stripe-key="stripeKey"
			:error="error"
			:form-action="formAction"
			:locale="locale.card"
			:orderId="orderId"
			v-if="mode=='card'"
		></card-stripe>
		<sepa-stripe
		:stripe-key="stripeKey"
		:error="error"
		:form-action="formAction"
		:currency="currency"
		:locale="locale.sepa"
		:orderId="orderId"
		v-if="mode=='sepa'"
		></sepa-stripe>
		<sofort-stripe
			:stripe-key="stripeKey"
			:error="error"
			:form-action="formAction"
			:currency="currency"
			:amount="amount"
			:redirectUrl="params.sofort.redirectUrl"
			:locale="locale.sofort"
			:orderId="orderId"
			v-if="mode=='sofort'"
		></sofort-stripe>
		<giropay-stripe
			:stripe-key="stripeKey"
			:error="error"
			:form-action="formAction"
			:currency="currency"
			:amount="amount"
			:redirectUrl="params.giropay.redirectUrl"
			:locale="locale.giropay"
			:orderId="orderId"
			v-if="mode=='giropay'"
		></giropay-stripe>
	</div>
</template>

<script>
	import Card from './Card';
	import Sepa from './Sepa';
	import Sofort from './Sofort';
	import Giropay from './Giropay';

	export default {
		name: "form-stripe",
		components: {
			'card-stripe': Card,
			'sepa-stripe': Sepa,
			'sofort-stripe': Sofort,
			'giropay-stripe': Giropay,
		},
		props: {
			error: {
				type : String,
				default : ''
			},
			stripeKey: {
				type : String,
				required : true
			},
			currency: {
				type : String,
				required : true
			},
			amount: {
				type : Number,
				required : true
			},
			orderId: {
				type : Number,
				required : true
			},
			formAction: {
				type : String,
				required : true
			},
			params: {
				type : Object,
				default: {
					sofort: {
						redirectUrl: ''
					},
				}
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
				// mode: {
				// 	key: 'card',
				// 	value: 'Карта'
				// },
				mode: 'card',
				modeList: [
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
			};
		}
	}
</script>

<style lang="scss">
	.stripe-root-form{
		padding: 0 25px;
		select {
			box-sizing: border-box;
			height: 40px;
			padding: 10px 12px;
			border: 1px solid transparent;
			border-radius: 4px;
			background-color: white;
			box-shadow: 0 1px 3px 0 #e6ebf1;
			-webkit-transition: box-shadow 150ms ease;
			transition: box-shadow 150ms ease;
		}
	}
</style>