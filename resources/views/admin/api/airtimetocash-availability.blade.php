@extends('layouts.app')

@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-header row">
            <div class="content-header-left col-12 mb-2 mt-1">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb p-0 mb-0 bg-transparent">
                        <li class="breadcrumb-item"><a href="/"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('api.index') }}">API Providers</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('api.edit', $api) }}">{{ $api->name }}</a></li>
                        <li class="breadcrumb-item active">Recipient availability</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-body">
            <section class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="card-title mb-25">Check recipient availability</h4>
                                <p class="text-muted mb-0">{{ $api->name }}</p>
                            </div>
                            <a href="{{ route('api.edit', $api) }}" class="btn btn-sm btn-light-secondary">
                                <i class="bx bx-left-arrow-alt mr-25"></i> Back to provider
                            </a>
                        </div>

                        <div class="card-body">
                            <form id="availability-form" action="{{ route('api.airtimetocash.availability.check', $api) }}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label for="network">Network</label>
                                    <select class="form-control" id="network" name="network" required>
                                        <option value="MTN">MTN</option>
                                        <option value="AIRTEL">Airtel</option>
                                        <option value="GLO">Glo</option>
                                        <option value="9MOBILE">9mobile</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="amount">Amount</label>
                                    <input type="number" class="form-control" id="amount" name="amount" min="50" step="1" required placeholder="Enter amount">
                                    <small class="text-muted">Enter the airtime amount to check against the provider's recipient quota.</small>
                                </div>

                                <button type="submit" class="btn btn-primary" id="check-availability-button">
                                    <i class="bx bx-search-alt mr-25"></i>
                                    Check availability
                                </button>

                                <div id="availability-response" class="mt-2" style="display:none">
                                    <h6 class="mb-1">Provider JSON response</h6>
                                    <pre id="availability-response-json" class="bg-light border rounded p-2 mb-0" style="white-space:pre-wrap;word-break:break-word"></pre>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('availability-form');
    const button = document.getElementById('check-availability-button');
    const responseContainer = document.getElementById('availability-response');
    const responseJson = document.getElementById('availability-response-json');

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        button.disabled = true;
        button.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-25"></i> Checking...';
        responseContainer.style.display = 'none';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value
                },
                body: JSON.stringify({
                    network: document.getElementById('network').value,
                    amount: document.getElementById('amount').value
                })
            });

            const payload = await response.json().catch(function () {
                return { message: 'The server returned an invalid response.' };
            });

            responseJson.textContent = JSON.stringify(payload, null, 2);
            responseContainer.style.display = 'block';
            responseJson.classList.toggle('border-danger', !response.ok);
            responseJson.classList.toggle('border-success', response.ok);
        } catch (error) {
            responseJson.textContent = JSON.stringify({ message: error.message }, null, 2);
            responseContainer.style.display = 'block';
            responseJson.classList.add('border-danger');
            responseJson.classList.remove('border-success');
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="bx bx-search-alt mr-25"></i> Check availability';
        }
    });
});
</script>
@endsection
