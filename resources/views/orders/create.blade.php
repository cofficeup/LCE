@extends('layouts.app')

@section('title', 'Create PPO Order - LCE 2.0')

@section('content')
<div class="card" style="max-width: 600px; margin: 30px auto;">
    <h2>Create Pay-Per-Order</h2>

    <form method="POST" action="/orders">
        @csrf

        <div class="form-group">
            <label for="weight_lbs">Weight (lbs)</label>
            <input type="number" step="0.1" id="weight_lbs" name="weight_lbs" value="{{ old('weight_lbs') }}" required>
            <small style="color: #666;">Minimum charge: $30</small>
        </div>

        <div class="form-group">
            <label for="pickup_type">Pickup Type</label>
            <select id="pickup_type" name="pickup_type" required onchange="toggleDateField()">
                <option value="asap">ASAP (Next Available)</option>
                <option value="future">Schedule for Later</option>
            </select>
        </div>

        <div class="form-group" id="pickup_date_group" style="display: none;">
            <label for="pickup_date">Pickup Date</label>
            <input type="date" id="pickup_date" name="pickup_date" value="{{ old('pickup_date') }}">
        </div>

        <div class="form-group">
            <label for="pickup_zone">Pickup Zone</label>
            <input type="text" id="pickup_zone" name="pickup_zone" value="{{ old('pickup_zone', 'ZONE-1') }}" required>
        </div>

        <div class="form-group">
            <label for="notes">Notes (Optional)</label>
            <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
        </div>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <h4>Pricing Summary</h4>
            <p>Rate: $2.99/lb</p>
            <p>Pickup & Delivery Fee: $9.99</p>
            <p>Service Fee: $5.00</p>
        </div>

        <button type="submit" class="btn" style="width: 100%;">Create Order</button>
        <a href="/dashboard" class="btn btn-secondary" style="width: 100%; margin-top: 10px; text-align: center;">Cancel</a>
    </form>
</div>

<script>
    function toggleDateField() {
        const pickupType = document.getElementById('pickup_type').value;
        const dateGroup = document.getElementById('pickup_date_group');
        dateGroup.style.display = pickupType === 'future' ? 'block' : 'none';
    }
</script>
@endsection