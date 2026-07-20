<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
                <!-- Deluge Settings Card -->
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Deluge Configuration</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('DELUGE_AUTH') is-invalid @enderror" id="DELUGE_AUTH" name="DELUGE_AUTH" placeholder="Deluge Auth Token" value="{{ old('DELUGE_AUTH', $settings['DELUGE_AUTH'] ?? '') }}">
                            <label for="DELUGE_AUTH">Deluge Auth Token (DELUGE_AUTH)</label>
                            @error('DELUGE_AUTH')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control @error('GOOGLE_REVIEW_LINK') is-invalid @enderror" id="GOOGLE_REVIEW_LINK" name="GOOGLE_REVIEW_LINK" placeholder="WhatsApp Number" value="{{ old('GOOGLE_REVIEW_LINK', $settings['GOOGLE_REVIEW_LINK'] ?? '') }}">
                            <label for="GOOGLE_REVIEW_LINK">Goolge Review link (e.g., https://google.come/review/1234)</label>
                            @error('GOOGLE_REVIEW_LINK') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>   

                        <label class="form-label d-block fw-bold mb-2">Select Environment</label>
                        <div class="btn-group mb-3" role="group" aria-label="Environment selection">
                            <input type="radio" class="btn-check" name="ENVIRONMENT" id="env_prod" autocomplete="off" checked value="production">
                            <label class="btn btn-outline-primary" for="env_prod">Production</label>

                            <input type="radio" class="btn-check" name="ENVIRONMENT" id="env_test" autocomplete="off" value="test">
                            <label class="btn btn-outline-primary" for="env_test">Test</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="text" class="form-control @error('TEST_WHATSAPP_NUMBER') is-invalid @enderror" id="TEST_WHATSAPP_NUMBER" name="TEST_WHATSAPP_NUMBER" placeholder="WhatsApp Number" value="{{ old('TEST_WHATSAPP_NUMBER', $settings['TEST_WHATSAPP_NUMBER'] ?? '') }}">
                            <label for="TEST_WHATSAPP_NUMBER">Test WhatsApp Recipient Number (e.g., +14155238886)</label>
                            @error('TEST_WHATSAPP_NUMBER') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>  

                        <div class="form-floating mb-4">
                            <input type="text" class="form-control @error('TEST_CALLBACK_URL') is-invalid @enderror" id="TEST_CALLBACK_URL" name="TEST_CALLBACK_URL" placeholder="WhatsApp Number" value="{{ old('TEST_CALLBACK_URL', $settings['TEST_CALLBACK_URL'] ?? '') }}">
                            <label for="TEST_CALLBACK_URL">Test Callback Url (e.g., http://localhost/app/callback)</label>
                            @error('TEST_CALLBACK_URL') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>  

                        <div class="form-floating mb-4">
                            <input type="text" class="form-control @error('PRODUCTION_CALLBACK_URL') is-invalid @enderror" id="PRODUCTION_CALLBACK_URL" name="PRODUCTION_CALLBACK_URL" placeholder="WhatsApp Number" value="{{ old('PRODUCTION_CALLBACK_URL', $settings['PRODUCTION_CALLBACK_URL'] ?? '') }}">
                            <label for="PRODUCTION_CALLBACK_URL">Production Callback Url (e.g., https://app/callback)</label>
                            @error('PRODUCTION_CALLBACK_URL') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>  

                                           
                    </div>
                </div>

                <!-- Twilio Settings Card -->
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="bi bi-chat-left-dots me-2"></i>Twilio Integration Settings</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('TWILIO_ACCOUNT_SID') is-invalid @enderror" id="TWILIO_ACCOUNT_SID" name="TWILIO_ACCOUNT_SID" placeholder="Account SID" value="{{ old('TWILIO_ACCOUNT_SID', $settings['TWILIO_ACCOUNT_SID'] ?? '') }}">
                                    <label for="TWILIO_ACCOUNT_SID">Twilio Account SID</label>
                                    @error('TWILIO_ACCOUNT_SID') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('TWILIO_COMPANY_NAME') is-invalid @enderror" id="TWILIO_COMPANY_NAME" name="TWILIO_COMPANY_NAME" placeholder="Company Name" value="{{ old('TWILIO_COMPANY_NAME', $settings['TWILIO_COMPANY_NAME'] ?? '') }}">
                                    <label for="TWILIO_COMPANY_NAME">Twilio Company Name</label>
                                    @error('TWILIO_COMPANY_NAME') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('TWILIO_API_KEY_SID') is-invalid @enderror" id="TWILIO_API_KEY_SID" name="TWILIO_API_KEY_SID" placeholder="API Key SID" value="{{ old('TWILIO_API_KEY_SID', $settings['TWILIO_API_KEY_SID'] ?? '') }}">
                                    <label for="TWILIO_API_KEY_SID">API Key SID</label>
                                    @error('TWILIO_API_KEY_SID') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control @error('TWILIO_API_KEY_SECRET') is-invalid @enderror" id="TWILIO_API_KEY_SECRET" name="TWILIO_API_KEY_SECRET" placeholder="API Key Secret" value="{{ old('TWILIO_API_KEY_SECRET', $settings['TWILIO_API_KEY_SECRET'] ?? '') }}">
                                    <label for="TWILIO_API_KEY_SECRET">API Key Secret</label>
                                    @error('TWILIO_API_KEY_SECRET') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="text" class="form-control @error('TWILIO_WHATSAPP_NUMBER') is-invalid @enderror" id="TWILIO_WHATSAPP_NUMBER" name="TWILIO_WHATSAPP_NUMBER" placeholder="WhatsApp Number" value="{{ old('TWILIO_WHATSAPP_NUMBER', $settings['TWILIO_WHATSAPP_NUMBER'] ?? '') }}">
                            <label for="TWILIO_WHATSAPP_NUMBER">Twilio WhatsApp Sender Number (e.g., +14155238886)</label>
                            @error('TWILIO_WHATSAPP_NUMBER') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <h6 class="text-muted mb-1 mt-5 fw-bold small text-uppercase">WhatsApp Templates (SIDs)</h6>
                        {{-- <hr class="mt-0 mb-3"> --}}

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('TWILIO_WHATSAPP_CUSTOMER_OPTBACK_TEMPLATE_SID') is-invalid @enderror" id="TWILIO_WHATSAPP_CUSTOMER_OPTBACK_TEMPLATE_SID" name="TWILIO_WHATSAPP_CUSTOMER_OPTBACK_TEMPLATE_SID" placeholder="Opt-Back Template" value="{{ old('TWILIO_WHATSAPP_CUSTOMER_OPTBACK_TEMPLATE_SID', $settings['TWILIO_WHATSAPP_CUSTOMER_OPTBACK_TEMPLATE_SID'] ?? '') }}">
                            <label for="TWILIO_WHATSAPP_CUSTOMER_OPTBACK_TEMPLATE_SID">Customer Opt-Back Template SID</label>
                            @error('TWILIO_WHATSAPP_CUSTOMER_OPTBACK_TEMPLATE_SID') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('TWILIO_WHATSAPP_CUSTOMER_OPTOUT_TEMPLATE_SID') is-invalid @enderror" id="TWILIO_WHATSAPP_CUSTOMER_OPTOUT_TEMPLATE_SID" name="TWILIO_WHATSAPP_CUSTOMER_OPTOUT_TEMPLATE_SID" placeholder="Opt-Out Template" value="{{ old('TWILIO_WHATSAPP_CUSTOMER_OPTOUT_TEMPLATE_SID', $settings['TWILIO_WHATSAPP_CUSTOMER_OPTOUT_TEMPLATE_SID'] ?? '') }}">
                            <label for="TWILIO_WHATSAPP_CUSTOMER_OPTOUT_TEMPLATE_SID">Customer Opt-Out Template SID</label>
                            @error('TWILIO_WHATSAPP_CUSTOMER_OPTOUT_TEMPLATE_SID') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('TWILIO_WHATSAPP_CUSTOMER_RATING_TEMPLATE_SID') is-invalid @enderror" id="TWILIO_WHATSAPP_CUSTOMER_RATING_TEMPLATE_SID" name="TWILIO_WHATSAPP_CUSTOMER_RATING_TEMPLATE_SID" placeholder="Rating Template" value="{{ old('TWILIO_WHATSAPP_CUSTOMER_RATING_TEMPLATE_SID', $settings['TWILIO_WHATSAPP_CUSTOMER_RATING_TEMPLATE_SID'] ?? '') }}">
                            <label for="TWILIO_WHATSAPP_CUSTOMER_RATING_TEMPLATE_SID">Customer Rating Template SID</label>
                            @error('TWILIO_WHATSAPP_CUSTOMER_RATING_TEMPLATE_SID') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                </div>

                <!-- Form Submission -->
                {{-- <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-light border">Reset Changes</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-bold">Save Configurations</button>
                </div> --}}
            {{-- </form> --}}

        </div>
    </div>
</div>

@section('script')
<script>
    const settings = @json($settings);
    if (settings['ENVIRONMENT'] === 'test') {
        $('#env_prod').prop('checked', false);
        $('#env_test').prop('checked', true);
    } 
</script>
@endsection
