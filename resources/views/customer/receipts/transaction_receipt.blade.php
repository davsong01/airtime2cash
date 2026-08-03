<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>{{ $transaction['product_name']}}</title>

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
										$image = !empty($transaction['product']) ? $transaction['product']['image'] : 'site/upgrade.jpg';
									?>
									<img src="{{ url('/').'/'.getSettings()['logo']}}" style="width: 20%; max-width: 500px"/>
									@if(in_array($transaction['reason'], ['LEVEL-UPGRADE','WALLET-FUNDING']))
								
									@else
									<img src="{{ url('/').'/'.$image  }}" style="width: 20%; max-width: 500px"/>
									@endif
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
									Payment for: <br />
									@if(in_array($transaction['reason'], ['LEVEL-UPGRADE','WALLET-FUNDING']))
										{{ ucfirst(str_replace("-"," ",$transaction['reason']))}}
									@else
									 <strong>{{ $transaction['product']['name']}}</strong>@if(!empty($transaction['variation']['system_name'])) {{ " | ". $transaction['variation']['system_name'] }}@endif
									@endif
								</td>

								{{-- <td>
									Acme Corp.<br />
									John Doe<br />
									john@example.com --}}
								{{-- </td> --}}
							</tr>
						</table>
					</td>
				</tr>
				<tr class="heading">
					<td>Transaction Details</td>

					<td></td>
				</tr>
				@if(!empty($transaction['descr']))
				<tr class="item">
					<td>Description</td>
					<td>{{ $transaction['descr'] }}</td>
				</tr>
				@endif
				@if(!empty($transaction['extras']))
				<tr class="item">
					<td>Extras</td>
					<td>{{ ucfirst($transaction['extras']) }}</td>
				</tr>                                                        
				@endif
				@if(!empty($transaction['extra_info']))
					@foreach ( json_decode($transaction['extra_info']) as $key=>$value )
					<tr class="item">
						<td>{{ $key }}</td>
						<td>{{ ucfirst($value) }}</td>
					</tr>
					@endforeach
				@endif
				<tr class="item">
					<td>Payment Method</td>
					<td>{{ ucfirst($transaction['payment_method']) }}</td>
				</tr>
				<tr class="item">
					<td>Service</td>
					<td>
						@if(in_array($transaction['reason'], ['LEVEL-UPGRADE','WALLET-FUNDING']))
							{{ ucfirst(str_replace("-"," ",$transaction['reason']))}}
						@else
						{{$transaction['product']['display_name']}} @if(!empty($transaction['variation']['system_name'])) ({{$transaction['variation']['system_name']}})@endif
						@endif
					</td>
				</tr>
				<tr class="item">
					<td>Phone</td>
					<td>{{$transaction['customer_phone']}}</td>
				</tr>
				<tr class="item">
					<td>Biller</td>
					<td>{{$transaction['unique_element']}}</td>
				</tr>
				<tr class="item">
					<td>Email</td>
					<td>{{$transaction['customer_email']}}</td>
				</tr>
				<tr class="item">
					<td>Unit Price</td>
					<td>#{{ number_format($transaction['unit_price'], 2) }}</td>
				</tr>
				
				<tr class="item">
					<td>Quantity</td>
					<td>{{ number_format($transaction['quantity']) }}</td>
				</tr>
				<tr class="item">
					<td>Amount</td>
					<td>#{{ number_format($transaction['amount'], 2) }}</td>
				</tr>
				<tr class="item">
					<td>Discount Applied</td>
					<td>#{{ number_format($transaction['discount'], 2) }}</td>
				</tr>
				@if(!empty($transaction->provider_charge))
                <tr class="item">
					<td>Convenience Fee</td>
					<td>#{{ number_format($transaction['provider_charge'], 2) }}</td>
				</tr>                                                           
                @endif
				<tr class="item">
					<td>Total Amount Paid</td>
					<td>#{{ number_format($transaction['total_amount'], 2) }}</td>
				</tr>
				<tr class="item">
					<td>Initial Balance</td>
					<td>#{{ number_format($transaction['balance_before'], 2) }}</td>
				</tr>
				<tr class="item">
					<td>Final Balance</td>
					<td>#{{ number_format($transaction['balance_after'], 2) }}</td>
				</tr>


			</table>
		</div>
	</body>
</html>