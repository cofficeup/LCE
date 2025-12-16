@extends('layouts.app')

@section('title', 'Payment - LCE 2.0')

@section('content')
<div class="card" style="max-width: 600px; margin: 30px auto;">
    <h2>Payment Required</h2>

    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h3>Order Summary</h3>
        <p><strong>Order Type:</strong> {{ ucfirst($orderType) }}</p>
        <p><strong>Amount Due:</strong> ${{ number_format($amount, 2) }}</p>
        @if($creditsApplied > 0)
        <p style="color: #28a745;"><strong>Credits Applied:</strong> -${{ number_format($creditsApplied, 2) }}</p>
        @endif
        <hr>
        <p style="font-size: 1.2em;"><strong>Total to Charge:</strong> ${{ number_format($finalAmount, 2) }}</strong></p>
    </div>

    @if($finalAmount > 0)
    <form id="payment-form" method="POST" action="{{ route('payment.process') }}">
        @csrf
        <input type="hidden" name="invoice_id" value="{{ $invoiceId }}">

        <div class="form-group">
            <label>Card Information</label>
            <div id="card-element" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: white;">
                <!-- Stripe Card Element will be inserted here -->
            </div>
            <div id="card-errors" role="alert" style="color: #dc3545; margin-top: 10px;"></div>
        </div>

        <div class="form-group">
            <label for="cardholder-name">Cardholder Name</label>
            <input type="text" id="cardholder-name" name="cardholder_name" required>
        </div>

        <button type="submit" id="submit-button" class="btn" style="width: 100%;">
            <span id="button-text">Pay ${{ number_format($finalAmount, 2) }}</span>
            <span id="spinner" style="display: none;">Processing...</span>
        </button>

        <div style="text-align: center; margin-top: 15px;">
            <img src="https://img.shields.io/badge/Secured%20by-Stripe-blue" alt="Secured by Stripe">
            <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                Your payment is secure. We use Stripe for payment processing.
            </p>
        </div>
    </form>
    @else
    <div style="text-align: center; padding: 20px;">
        <p style="color: #28a745; font-size: 1.2em;">✓ Fully covered by credits!</p>
        <p>No payment required.</p>
        <form method="POST" action="{{ route('payment.process') }}">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoiceId }}">
            <button type="submit" class="btn" style="width: 100%;">Confirm Order</button>
        </form>
    </div>
    @endif

    <a href="/dashboard" class="btn btn-secondary" style="width: 100%; margin-top: 10px; text-align: center;">Cancel</a>
</div>

@if($finalAmount > 0)
<script src="https://js.stripe.com/v3/"></script>
<script>
    // Initialize Stripe
    const stripe = Stripe('{{ config("stripe.publishable_key") }}');
    const elements = stripe.elements();

    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        }
    });

    // Mount card element
    cardElement.mount('#card-element');

    // Handle card element changes
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Handle form submission
    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        // Disable button and show spinner
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        spinner.style.display = 'inline';

        // Create payment method
        const {
            error,
            paymentMethod
        } = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: document.getElementById('cardholder-name').value,
            }
        });

        if (error) {
            // Show error
            document.getElementById('card-errors').textContent = error.message;
            submitButton.disabled = false;
            buttonText.style.display = 'inline';
            spinner.style.display = 'none';
        } else {
            // Add payment method ID to form and submit
            const hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'payment_method_id');
            hiddenInput.setAttribute('value', paymentMethod.id);
            form.appendChild(hiddenInput);

            // Submit form
            form.submit();
        }
    });
</script>
@endif
@endsection