<div class="modal fade" id="sneatSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">Edit Profile</a>
                    <a href="{{ route('update.kyc.details') }}" class="btn btn-outline-primary">Update KYC</a>
                    <a href="{{ route('customer.load.wallet') }}" class="btn btn-outline-primary">Fund Wallet</a>
                    <a href="{{ route('customer.transaction.history') }}" class="btn btn-outline-primary">Transactions</a>
                </div>
            </div>
        </div>
    </div>
</div>
