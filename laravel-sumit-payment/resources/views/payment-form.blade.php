<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Form</title>
    <style>
        .payment-form {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-row {
            display: flex;
            gap: 10px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="payment-form">
        <h2>Payment Information</h2>
        <form method="POST" action="{{ route('sumit.payment.process') }}">
            @csrf
            
            <div class="form-group">
                <label for="amount">Amount (ILS)</label>
                <input type="number" id="amount" name="amount" step="0.01" required>
                @error('amount')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="customer_name">Full Name</label>
                <input type="text" id="customer_name" name="customer_name" required>
                @error('customer_name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="customer_email">Email</label>
                <input type="email" id="customer_email" name="customer_email" required>
                @error('customer_email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" maxlength="16" required>
                @error('card_number')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="expiry_month">Expiry Month</label>
                    <input type="text" id="expiry_month" name="expiry_month" placeholder="MM" maxlength="2" required>
                    @error('expiry_month')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="expiry_year">Expiry Year</label>
                    <input type="text" id="expiry_year" name="expiry_year" placeholder="YY" maxlength="2" required>
                    @error('expiry_year')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" maxlength="3" required>
                    @error('cvv')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="save_card" value="1">
                    Save card for future purchases
                </label>
            </div>

            <button type="submit" class="btn">Process Payment</button>
        </form>
    </div>
</body>
</html>
