<?php
use App\Models\BlackList;
?>
@extends('layouts.app')
@section('page-css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
         .verified{
            color: green !important;
            font-size: 13px;
            margin-top: -6px;
            display: inline-block;
            margin-left: 5px;
        }
        .unverified{
            color: orange !important;
            font-size: 13px;
            margin-top: -6px;
            display: inline-block;
            margin-left: 5px;
        }

        .select2-container {
            width: 100% !important;
        }
    </style>
@endsection
@section('content')
    <!-- Content wrapper -->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper">
            <div class="content-header row">
                <div class="content-header-left col-12 mb-2 mt-1">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <div class="breadcrumb-wrapper col-12">
                                <ol class="breadcrumb p-0 mb-0">
                                    <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a>
                                    </li>
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a>
                                    </li>
                                    <li class="breadcrumb-item active">Customer Profile</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <section id="table-success">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Customer Profile</h5>
                        <div class="mt-3">
                            @include('layouts.alerts')
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-2">
                                <div class="d-flex justify-content-center flex-column align-items-center">
                                    @if ($user->avatar)
                                        <img src="{{ $user->avatar }}" width="180px" height="180px"
                                            alt="{{ $user->firstname }}" class="users-avatar-shadow rounded-circle" />
                                    @else
                                        <div class="users-avatar-shadow rounded-circle">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="180" viewBox="0 -960 960 960"
                                                width="180">
                                                <path fill="red"
                                                    d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q53 0 100-15.5t86-44.5q-39-29-86-44.5T480-280q-53 0-100 15.5T294-220q39 29 86 44.5T480-160Zm0-360q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0-60Zm0 360Z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <h5 class="card-title mt-1">
                                        {{ ucfirst($user->firstname) . ' ' . ucfirst($user->middlename) . ' ' . ucfirst($user->lastname) }}
                                        ({{ $user->customer->customer_level ?? 'Level 1' }})</h5>
                                        Email: <br><a href="mailto:{{ $user->email }}">{{ $user->email }}</a> <br>
                                        Phone: <br>{{ $user->phone }}
                                </div>
                            </div>
                            <div class="col-sm-10">
                                <div class="card-body">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="account-tab" data-toggle="tab" href="#account"
                                                aria-controls="home" role="tab" aria-selected="true">
                                                <i class="bx bxs-bar-chart-alt-2 align-middle"></i>
                                                <span class="align-middle">Account</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="transactions-tab" data-toggle="tab" href="#transactions"
                                                aria-controls="home" role="tab" aria-selected="true">
                                                <i class="bx bxs-bar-chart-alt-2 align-middle"></i>
                                                <span class="align-middle">Transactions</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="downlines-tab" data-toggle="tab" href="#downlines"
                                                aria-controls="profile" role="tab" aria-selected="false">
                                                <i class="bx bx-user-plus align-middle"></i>
                                                <span class="align-middle">Downlines</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="ky-tabc" data-toggle="tab" href="#kyc"
                                                aria-controls="about" role="tab" aria-selected="false">
                                                <i class="bx bx-user align-middle"></i>
                                                <span class="align-middle">KYC Data</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="reserved-account-tab" data-toggle="tab"
                                                href="#reserved-account" aria-controls="about" role="tab"
                                                aria-selected="false">
                                                <i class="bx bx-wallet align-middle"></i>
                                                <span class="align-middle">Reserved Accounts</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="actions-tab" data-toggle="tab" href="#actions"
                                                aria-controls="about" role="tab" aria-selected="false">
                                                <i class="bx bx-repost align-middle"></i>
                                                <span class="align-middle">Actions</span>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="account" aria-labelledby="account-tab"
                                            role="tabpanel">
                                            <div class="row">
                                                @foreach ($balances as $key => $bal)
                                                    <div class="col-sm-3 col-md-3">
                                                        <div
                                                            class="card bg-warning bg-lighten-2 p-0">
                                                            <div class="card-content">
                                                                <div class="card-body text-center" style="padding: 10px;padding-bottom: 0px;">
                                                                    <p class="card-text white">
                                                                        {{ $key }}</p>
                                                                    <h5 class="card-title white">
                                                                        {!! $bal !!}
                                                                    </h5>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <h5 class="card-title mt-3">
                                                Profile Info
                                            </h5>
                                            <form
                                                action="{{ request()->route()->getPrefix() }}/customer/update/{{ $user->id }}"
                                                method="POST">
                                                <fieldset class="form-group">
                                                    <label for="firstname">First Name</label>
                                                    <input type="text" class="form-control" id="firstname"
                                                        placeholder="First name" value="{{ $user->firstname }}"
                                                        name="firstname">
                                                </fieldset>
                                                <fieldset class="form-group">
                                                    <label for="lastname">Last Name</label>
                                                    <input type="text" class="form-control" id="lastname"
                                                        placeholder="Last name" value="{{ $user->lastname }}"
                                                        name="lastname">
                                                </fieldset>
                                                <fieldset class="form-group">
                                                    <label for="status">Status</label>
                                                    <select name="status" class="form-control" id="status">
                                                        <option value="">Select Status</option>
                                                        <option value="active" @selected($user->status == 'active')>Active</option>
                                                        <option value="suspended" @selected($user->status == 'suspended')>Suspended
                                                        </option>
                                                        <option value="delete" @selected($user->status == 'delete')>Delete</option>
                                                    </select>
                                                </fieldset>
                                        
                                                <fieldset class="form-group">
                                                    <label for="customerlevel">Customer Level</label>
                                                    <select name="customerlevel" class="form-control" id="customerlevel">
                                                        <option value="">Select Customerlevel</option>
                                                        @foreach ($customerLevels as $level)
                                                        <option value="{{ $level->id }}" {{$user->customer->customer_level == $level->id ? 'selected':''}}>{{ $level->name}} {{ $level->make_api_level == 'yes' ? '(API ACCESS)' : ''}}</option>
                                                        @endforeach
                                                    </select>
                                                </fieldset>
                                                <fieldset class="form-group">
                                                    <label for="email">Email</label>
                                                    <input type="email" disabled class="form-control" id="email" placeholder="Email" value="{{ $user->email }}">
                                                </fieldset>
                                                <fieldset class="form-group">
                                                    <label for="username">Username</label>
                                                    <input type="text" class="form-control" disabled placeholder="Username" value="{{ $user->username }}">
                                                </fieldset>
                                                <fieldset class="form-group">
                                                    <label for="phone">Phone</label>
                                                    <input type="phone" class="form-control" id="email"
                                                        placeholder="Phone number" value="{{ $user->phone }}"
                                                        name="phone">
                                                </fieldset>
                                                <fieldset class="form-group">
                                                    <button type="submit" class="btn btn-success">
                                                        Update
                                                    </button>
                                                </fieldset>
                                                @csrf
                                            </form>
                                        </div>
                                        <div class="tab-pane" id="transactions" aria-labelledby="transactions-tab"
                                            role="tabpanel">
                                            <div class="table-responsive">
                                                <table id="table-extended-success" class="table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Amount</th>
                                                            <th>Amount Paid</th>
                                                            <th>Biller</th>
                                                            <th>Status</th>
                                                            <th>Transaction ID</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $trans = $user->customer
                                                                ->transactions()
                                                                ->latest()
                                                                ->paginate(10);
                                                        @endphp
                                                        @foreach ($trans as $transaction)
                                                            <tr>
                                                                <td>
                                                                    {{ $transaction->product_name }} <br><br>
                                                                   <span><small>{{ $transaction->created_at->toDateString('en-GB') }}</small></span>
                                                                </td>
                                                                <td>{!! getSettings()->currency . number_format($transaction->amount) !!}</td>
                                                                <td>{!! getSettings()->currency . number_format($transaction->total_amount) !!}</td>
                                                                <td>{{ $transaction->unique_element }}</td>
                                                                <td>{{ $transaction->status }}</td>
                                                                <td><a class="mr-1 mb-1"  href="{{ route('admin.single.transaction.view', $transaction->id) }}">{{ $transaction->transaction_id }}</a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                {{ $trans->render() }}
                                            </div>
                                        </div>
                                        <div class="tab-pane" id="downlines" aria-labelledby="profile-tab"
                                            role="tabpanel">
                                            <table able class="table table-striped dataex-html5-selectors">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Phone</th>
                                                        <th>Total Earned</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($downlines as $ref)
                                                        <tr>
                                                            <td>{{ ucfirst($ref->referredCustomer->user->firstname) . ' ' . ucfirst($ref->referredCustomer->user->lastname) }}
                                                            </td>
                                                            <td>{{ $ref->referredCustomer->user->email }}</td>
                                                            <td>{{ $ref->referredCustomer->user->phone }}</td>
                                                            <td>{!! getSettings()->currency . number_format($ref->total) !!}</td>
                                                            <td>{{ $ref->created_at->toDateTimeString() }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>

                                            </table>
                                        </div>
                                        <div class="tab-pane" id="kyc" aria-labelledby="about-tab" role="tabpanel">
                                            <h1>KYC Data</h1>
                                            <form action="{{ route('admin.customer.update.kyc', $user->customer->id) }}" method="POST">
                                                @csrf
                                                <div class="card-content">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('FIRST_NAME', $user->customer->id)['status'] == 'verified')
                                                                <label for="FIRST_NAME">First Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" class="form-control" value="{{ kycStatus('FIRST_NAME', $user->customer->id)['value'] }}" disabled>
                                                                @else
                                                                <label for="FIRST_NAME">First Name</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <input type="text" name="FIRST_NAME" class="form-control" value="{{ $user->firstname }}" required>
                                                                @endif
                                                            </fieldset>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('MIDDLE_NAME', $user->customer->id)['status'] == 'verified')
                                                                <label for="MIDDLE_NAME">Middle Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" class="form-control" value="{{ kycStatus('MIDDLE_NAME', $user->customer->id)['value'] }}">
                                                                @else
                                                                <label for="MIDDLE_NAME">Middle Name</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <input type="text" name="MIDDLE_NAME" class="form-control" value="{{ $user->middlename }}">
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('LAST_NAME', $user->customer->id)['status'] == 'verified')
                                                                <label for="LAST_NAME">Last Name</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" class="form-control" value="{{ kycStatus('LAST_NAME', $user->customer->id)['value'] }}">
                                                                @else
                                                                <label for="lastname">Last Name</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <input type="text" name="LAST_NAME"  class="form-control" value="{{ $user->lastname }}">
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                <label for="email">Email Address</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input autocomplete="false" class="form-control" value="{{ $user->email }}">
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('PHONE_NUMBER', $user->customer->id)['status'] == 'verified')
                                                                <label for="PHONE_NUMBER">Phone Number</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" class="form-control" value="{{ kycStatus('PHONE_NUMBER', $user->customer->id)['value'] }}">
                                                                @else
                                                                <label for="lastname">Phone Number</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <input type="text" name="PHONE_NUMBER" class="form-control" value="{{ $user->phone }}">
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('COUNTRY', $user->customer->id)['status'] == 'verified')
                                                                <label for="COUNTRY">Country</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" name="COUNTRY" class="form-control" value="{{ kycStatus('COUNTRY', $user->customer->id)['value']}}">
                                                                @else
                                                                <label for="COUNTRY">Country</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <select name="COUNTRY" id="country" class="form-control">
                                                                    <option value="">Select...</option>
                                                                    <option value="Nigeria">Nigeria</option>
                                                                </select>
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('STATE', $user->customer->id)['status'] == 'verified')
                                                                <label for="STATE">State</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" class="form-control" value="{{ kycStatus('STATE', $user->customer->id)['value'] }}"/>
                                                                @else
                                                                <label for="STATE">State</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <select name="STATE" id="state" class="form-control">
                                                                    @foreach (getStates() as $state)
                                                                        <option value="{{$state}}"  {{ kycStatus('STATE', $user->customer->id)['value'] ? 'selected' : '' }}>{{$state}}</option>
                                                                    @endforeach
                                                                </select>
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('LGA', $user->customer->id)['status'] == 'verified')
                                                                <label for="LGA">Local Government Area</label>
                                                                <span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" class="form-control" value="{{ kycStatus('LGA', $user->customer->id)['value'] }}"/>
                                                                @else
                                                                <label for="LGA">Local Government Area</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <select id="lga" name="LGA" class="form-control" required>
                                                                    <option value="">Select</option>
                                                                    @if (!empty($lgas))
                                                                        @foreach ($lgas as $item)
                                                                            <option value="{{$item}}" {{ kycStatus('LGA', $user->customer->id)['value'] == $item ? 'selected' : '' }}>{{$item}}</option>
                                                                        @endforeach
                                                                    @endif
                                                                </select>
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('DOB', $user->customer->id)['status'] == 'verified')
                                                                <label for="DOB">Date of Birth</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="date" class="form-control" value="{{ kycStatus('DOB', $user->customer->id)['value'] }}">
                                                                @else
                                                                <label for="lastname">Date of Birth (As associated with BVN)</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <input type="date" name="DOB"  class="form-control" value="{{ kycStatus('DOB', $user->customer->id)['value'] }}" required>
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('IDCARDTYPE', $user->customer->id)['status'] == 'verified')
                                                                <label for="IDCARDTYPE">ID Card Type</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input type="text" class="form-control" value="{{ kycStatus('IDCARDTYPE', $user->customer->id)['value'] }}">
                                                                @else
                                                                <label for="IDCARDTYPE">ID Card Type</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <input type="date" name="IDCARDTYPE"  class="form-control" value="{{ kycStatus('IDCARDTYPE', $user->customer->id)['value'] }}" required>
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('IDCARD', $user->customer->id)['status'] == 'verified')
                                                                <label for="IDCARD">ID Card</label><span class="verified"><i class="fa fa-check"></i> Verifiedd</span> <br>
                                                                <img style="width: 60px;cursor:zoom-in;" src="{{asset(kycStatus('IDCARD', $user->customer->id)['value'])}}" onclick="zoomImg(this)">
                                                                @else
                                                                <label for="IDCARD">ID Card</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                
                                                                <input type="date" name="IDCARD"  class="form-control" value="{{ kycStatus('IDCARD', $user->customer->id)['value'] }}" required>
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <fieldset class="form-group">
                                                                @if(kycStatus('BVN', $user->customer->id)['status'] == 'verified')
                                                                <label for="bvn">BVN</label><span class="verified"><i class="fa fa-check"></i> Verified</span>
                                                                <input autocomplete="false" type="text" class="form-control" value="{{ kycStatus('BVN', $user->customer->id)['value'] }}">
                                                                @else
                                                                <label for="bvn">BVN</label><span class="unverified"><i class="fa fa-times"></i>Unverified</span>
                                                                <input type="text" name="BVN"  class="form-control" value="{{kycStatus('BVN', $user->customer->id)['value'] }}" required>
                                                                @endif
                                                            </fieldset>
                                                        </div>
                                                        <div class="col-md-12">
                                                            <button class="btn btn-primary">Update</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="tab-pane" id="reserved-account" aria-labelledby="about-tab"
                                            role="tabpanel">
                                            @empty($accounts)
                                                <p>No reserved account has been created by this customer</p>
                                            @else
                                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#reserved">Create Reserved Account
                                            </button>

                                                <table able class="table table-striped dataex-html5-selectors">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Bank</th>
                                                            <th>Account</th>
                                                            <th>Transactions</th>
                                                            <th>ACtion</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($accounts as $key => $account)
                                                            @if ($account->paymentgateway_id == 1)
                                                                <tr>
                                                                    <td>{{ ucfirst($account->account_name) }}</td>
                                                                    <td>{{ ucfirst($account->bank_name) }}</td>
                                                                    <td>{{ ucfirst($account->account_number) }} <br>
                                                                    <small style="color:black"><strong>Created on: {{$account->created_at}} </strong>
                                                                        @if(!empty($account->admin_id)) <br>
                                                                        By: <strong>{{ $account->admin->user->firstname . ' '. $account->admin->user->lastname}}</strong>
                                                                        @else   <br>
                                                                        By: <strong>SYSTEM</strong>
                                                                        @endif
                                                                    </small></td>
                                                                    <td>
                                                                        <a title ="View Transactions" href="{{route('account.transactions', $account->id)}}">
                                                                        {!! getSettings()->currency !!}{{ number_format($account->transactions->sum('total_amount'), 2) }} <small><strong>({{number_format($account->transactions->count())}})</strong></small></a>
                                                                    </td>
                                                                    <td>
                                                                        <div
                                                                            @if($account->transactions->count() < 1)
                                                                            <a>
                                                                            <a onclick="return confirm('You are about to delete a reserved account!')"class="btn btn-danger btn-sm mr-1 mb-1" href="{{ route('reserved_account.delete', $account->id) }}"><i class="bx bxs-trash"></i><span class="align-middle ml-25">Delete</span></button></a>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endempty
                                        </div>
                                        <div class="tab-pane" id="actions" aria-labelledby="about-tab"
                                            role="tabpanel">
                                            <div class="row">
                                                
                                                @php
                                                    // check blacklist status
                                                    $mail = BlackList::where('value', $user->email)->first();
                                                  
                                                @endphp
                                                <div class="col-md-6 col-sm-6">
                                                    <div class="card bg-lighten-2 p-0 bg-dark">
                                                        <div class="card-body">
                                                            <div class="card-content">
                                                                <h5 class="card-title white">
                                                                    Blacklist Email
                                                                </h5>
                                                                @if ($mail)
                                                                    <div
                                                                        class="custom-control custom-switch custom-switch-success custom-switch-glow custom-control-inline mb-1">
                                                                        <input type="checkbox"
                                                                            class="custom-control-input"
                                                                            id="customSwitchGlow2"
                                                                            @checked($mail->status == 'active')
                                                                            onchange="toggleStatus()"
                                                                            data-id="{{ $mail->id }}"
                                                                            data-value="{{ $mail->status }}">
                                                                        <label class="custom-control-label"
                                                                            for="customSwitchGlow2">
                                                                        </label>
                                                                    </div>
                                                                @else
                                                                    <form action="{{ route('customer-blacklist.store') }}"
                                                                        method="POST">
                                                                        @csrf
                                                                        <input type="hidden" name="type"
                                                                            value="email">
                                                                        <input type="hidden" name="value"
                                                                            value="{{ $user->email }}">
                                                                        <input type="hidden" name="status"
                                                                            value="active">
                                                                        <button class="btn btn-danger" type="submit">Add
                                                                            to blacklist</button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @php
                                                    // check blacklist statu
                                                    $phone = BlackList::where('value', $user->phone)->first();
                                                @endphp
                                                <div class="col-md-6 col-sm-6">
                                                    <div class="card bg-lighten-2 p-0 bg-danger">
                                                        <div class="card-body">
                                                            <div class="card-content">
                                                                <h5 class="card-title white">
                                                                    Blacklist Phone
                                                                </h5>
                                                                @if ($phone)
                                                                    <div
                                                                        class="custom-control custom-switch custom-switch-success custom-switch-glow custom-control-inline mb-1">
                                                                        <input type="checkbox"
                                                                            class="custom-control-input"
                                                                            id="customSwitchGlow2"
                                                                            @checked($phone->status == 'active')
                                                                            onchange="toggleStatus()"
                                                                            data-id="{{ $phone->id }}"
                                                                            data-value="{{ $phone->status }}">
                                                                        <label class="custom-control-label"
                                                                            for="customSwitchGlow2">
                                                                        </label>
                                                                    </div>
                                                                @else
                                                                    <form action="{{ route('customer-blacklist.store') }}"
                                                                        method="POST">
                                                                        
                                                                        @csrf
                                                                        <input type="hidden" name="type"
                                                                            value="biller">
                                                                        <input type="hidden" name="value"
                                                                            value="{{ $user->phone }}">
                                                                        <input type="hidden" name="status" value="active">
                                                                        <button class="btn btn-danger" type="submit">Add
                                                                            to blacklist</button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if(hasAccess('admin.password.reset'))
                                                <div class="col-md-6 col-sm-6">
                                                    <div class="card bg-lighten-2 p-0 bg-dark">
                                                        <div class="card-body">
                                                            <div class="card-content">
                                                                <h5 class="card-title white">
                                                                    Password Reset
                                                                </h5>
                                                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#reset-password">Click to reset password</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                                @if(hasAccess('admin.transaction.pin.reset'))
                                                <div class="col-md-6 col-sm-6">
                                                    <div class="card bg-lighten-2 p-0 bg-light">
                                                        <div class="card-body">
                                                            <div class="card-content">
                                                                <h5 class="card-title white">
                                                                    Reset Transaction PIN
                                                                </h5>
                                                                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#reset-transaction-pin">Click to reset transaction PIN</button>
                                                                
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <div class="modal fade text-left" id="reserved" tabindex="-1" role="dialog" aria-labelledby="myModalLabel160" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title white" id="myModalLabel160">Add Reserved account for {{ $user->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <form action="{{route('create.reserved.account', $user->customer->id)}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <fieldset class="form-group">
                                    <label for="bank" style="display: block">Bank(s)</label>
                                    
                                    <select class="form-control js-example-basic-single" name="bank[]" id="bank" required multiple>
                                        <option value="">Select</option>
                                        <option value="50515" {{ old('bank') == '50515' ? 'selected' : ''}}>Moniepoint</option>
                                        <option value="035" {{ old('bank') == '035' ? 'selected' : ''}}>Wema Bank</option>
                                        <option value="232" {{ old('bank') == '232' ? 'selected' : ''}}>Sterling Bank</option>
                                        <option value="058" {{ old('bank') == '058' ? 'selected' : ''}}>Guaranty Trust Bank</option> 
                                    </select>
                                </fieldset>
                            </div>
                        </div>
                        <input type="hidden" name="bvn" value="{{ kycStatus('BVN', $user->customer->id)['value']  }}">
                        <input type="hidden" name="customer_id" value="{{ $user->customer->id }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                            <i class="bx bx-x d-block d-sm-none"></i>
                            <span class="d-none d-sm-block">Close</span>
                        </button>
                        <button type="submit" class="btn btn-primary ml-1"><span class="d-none d-sm-block">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if(hasAccess('admin.transaction.pin.reset'))
    <div class="modal fade text-left" id="reset-transaction-pin" tabindex="-1" role="dialog" aria-labelledby="myModalLabel160" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title white" id="myModalLabel160">Reset Transaction PIN for: {{ $user->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <form action="{{route('admin.transaction.pin.reset', $user->id)}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <fieldset class="form-group">
                                    <label for="new_transaction_pin">New Trasaction PIN</label>
                                    <input type="text" class="form-control" name="new_transaction_pin" value="{{ old('new_transaction_pin') }}">
                                </fieldset>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                            <i class="bx bx-x d-block d-sm-none"></i>
                            <span class="d-none d-sm-block">Close</span>
                        </button>
                        <button type="submit" class="btn btn-primary ml-1"><span class="d-none d-sm-block">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    @if(hasAccess('admin.password.reset'))
    <div class="modal fade text-left" id="reset-password" tabindex="-1" role="dialog" aria-labelledby="myModalLabel160" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title white" id="myModalLabel160">Reset Password for: {{ $user->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
                <form action="{{route('admin.password.reset', $user->id)}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <fieldset class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="text" class="form-control" name="new_password" value="{{ old('new_password') }}">
                                </fieldset>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-dismiss="modal">
                            <i class="bx bx-x d-block d-sm-none"></i>
                            <span class="d-none d-sm-block">Close</span>
                        </button>
                        <button type="submit" class="btn btn-primary ml-1"><span class="d-none d-sm-block">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    <div id="myModal" class="modal modalPix">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl" role="document">
            <img class="modal-content" id="img01">
        </div>
    </div>
@endsection

@section('page-script')
    {{-- <script src="{{asset('asset/js/app-logistics-dashboard.js')}}"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();

            $('#state').on('change',function () {
                var state = $('#state').val();
                $('#lga option:not(:first)').remove();
                $.ajax({
                    type: "GET",
                    url: "{{url('/')}}/get-lga-by-statename/"+state,
                    beforeSend: function () {

                    },
                    success: function(data) {
                        $("#lga").append(data);
                    }
                });
            });

        });
        
    </script>
    <script>
        var modal = document.getElementById("myModal");
        var span = document.getElementsByClassName("close")[0];
        var modalImg = document.getElementById("img01");

        modalImg.onclick = function() {
            modal.style.display = "none";
        }

        function zoomImg(e){
            modal.style.display = "block";
            modalImg.src = e.src;
        }

        function toggleStatus() {
            let check = confirm('Are you sure you want to perform this action?');
            if (check) {
                let status = $('#customSwitchGlow2').attr('data-value');
                let id = $('#customSwitchGlow2').attr('data-id');

                $.ajax({
                    url: '/admin/black-list-status',
                    data: {
                        status,
                        id
                    },
                    success: e => {
                        alert(e.message)
                        if (e.code == 1) {
                            let status = $('#customSwitchGlow2').attr('data-value', e.status);
                        }
                    },
                    error: () => alert('Request could not be completed!'),
                });
            }
        }
    </script>

@endsection