<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>{{ $transaction['product']['name']}}</title>

		<style>
			.invoice-box {
				max-width: 800px;
				margin: auto;
				padding: 30px;
				border: 1px solid #eee;
				box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
				font-size: 16px;
				line-height: 24px;
				font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
				color: #555;
			}

			.invoice-box table {
				width: 100%;
				line-height: inherit;
				text-align: left;
			}

			.invoice-box table td {
				padding: 5px;
				vertical-align: top;
			}

			.invoice-box table tr td:nth-child(2) {
				text-align: right;
			}

			.invoice-box table tr.top table td {
				padding-bottom: 20px;
			}

			.invoice-box table tr.top table td.title {
				font-size: 45px;
				line-height: 45px;
				color: #333;
			}

			.invoice-box table tr.information table td {
				padding-bottom: 40px;
			}

			.invoice-box table tr.heading td {
				background: #1a233a;;
				border-bottom: 1px solid #ddd;
				font-weight: bold;
				color:white
			}

			.invoice-box table tr.details td {
				padding-bottom: 20px;
			}

			.invoice-box table tr.item td {
				border-bottom: 1px solid #eee;
			}

			.invoice-box table tr.item.last td {
				border-bottom: none;
			}

			.invoice-box table tr.total td:nth-child(2) {
				border-top: 2px solid #eee;
				font-weight: bold;
			}

			@media only screen and (max-width: 600px) {
				.invoice-box table tr.top table td {
					width: 100%;
					display: block;
					text-align: center;
				}

				.invoice-box table tr.information table td {
					width: 100%;
					display: block;
					text-align: center;
				}
			}

			/** RTL **/
			.invoice-box.rtl {
				direction: rtl;
				font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
			}

			.invoice-box.rtl table {
				text-align: right;
			}

			.invoice-box.rtl table tr td:nth-child(2) {
				text-align: left;
			}

			.red{
				color:red
			}

			.green{
				color:green
			}
		</style>
	</head>

	<body>
		<div class="invoice-box">
			<table cellpadding="0" cellspacing="0">
				<tr class="top">
					<td colspan="2">
						<table>
							<tr>
								<td class="title">
									<?php 
										$image = $transaction['product']['image'];
									?>
									<img src="{{ url('/').'/'.getSettings()['logo']}}" style="width: 20%; max-width: 500px"/>
									<img src="{{ url('/').'/'.$image  }}" style="width: 20%; max-width: 500px"/>
								</td>
								<td style="width: 50%;">
									<strong>{{ date("M jS, Y g:iA", strtotime($transaction['created_at'])) }} </strong><br />
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr class="information">
					<td colspan="2">
						<table>
							<tr>
								<td style="padding-left: 0px;">
									Airtime To Cash Receipt: <br />
									@if(!empty($transaction->decline_reason))
										{!! $transactiondecline_reason !!}
									@else
									<strong>{{ $transaction['product']['name']}}</strong>
									@endif
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr class="heading">
					<td>Transaction Details</td>

					<td></td>
				</tr>
				<tr class="item">
					<td>Status</td>
					<td>{{ ucfirst($transaction['status']) }}</td>
				</tr>
				@if(!empty($transaction['decline_reason']) && $transaction->status == 'declined')
				<tr class="item">
					<td>Decline Reason</td>
					<td>{{ $transaction['decline_reason'] }}</td>
				</tr>                                                        
				@endif
				<tr class="item">
					<td>Where to receive payment</td>
					<td>{{ ucfirst($transaction['payment_method']) }}</td>
				</tr>
				<tr class="item">
					<td>Phone Numbers</td>
					<td>{{$transaction['phone_numbers']}}</td>
				</tr>
				<tr class="item">
					<td>Amount to Transfer</td>
					<td>#{{ number_format($transaction['total_amount'], 2) }}</td>
				</tr>
				<tr class="item">
					<td>Amount charged</td>
					<td>#{{ number_format($transaction['amount_charged']) }}</td>
				</tr>
				<tr class="item">
					<td>Charge Rate</td>
					<td>{{ $transaction['charge_rate'] }}%</td>
				</tr>
				<tr class="item">
					<td>Amount to Receive</td>
					<td>#{{ number_format($transaction['amount_paid'], 2) }}</td>
				</tr>
				{{-- <tr class="item">
					<td>Initial Balance</td>
					<td>#{{ number_format($transaction['balance_before'], 2) }}</td>
				</tr>
				<tr class="item">
					<td>Final Balance</td>
					<td>#{{ number_format($transaction['balance_after'], 2) }}</td>
				</tr> --}}

			</table>
		</div>
	</body>
</html>