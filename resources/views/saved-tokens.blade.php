<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Payment Methods</title>
    <style>
        .tokens-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .token-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .token-card.default {
            border-color: #4CAF50;
            background-color: #f0f8f0;
        }
        .token-info {
            flex: 1;
        }
        .card-number {
            font-weight: bold;
            font-size: 16px;
        }
        .card-details {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .default-badge {
            background-color: #4CAF50;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .token-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #2196F3;
            color: white;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="tokens-container">
        <h2>Saved Payment Methods</h2>
        
        @if(count($tokens) === 0)
            <p>You don't have any saved payment methods.</p>
        @else
            @foreach($tokens as $token)
                <div class="token-card {{ $token->is_default ? 'default' : '' }}">
                    <div class="token-info">
                        <div class="card-number">{{ $token->getMaskedCardNumber() }}</div>
                        <div class="card-details">
                            @if($token->card_type)
                                {{ $token->card_type }} •
                            @endif
                            Expires {{ $token->expiry_month }}/{{ $token->expiry_year }}
                            @if($token->is_default)
                                <span class="default-badge">Default</span>
                            @endif
                        </div>
                    </div>
                    <div class="token-actions">
                        @if(!$token->is_default)
                            <form method="POST" action="{{ route('sumit.tokens.default', $token->id) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-primary">Set as Default</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('sumit.tokens.destroy', $token->id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
