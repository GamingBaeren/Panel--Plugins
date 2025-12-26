@php
    $enabledMethods = \Boy132\Billing\Enums\PaymentMethod::getEnabledMethods();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
<div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <a href="{{ route('filament.app.pages.dashboard') }}" class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ config('app.name') }}
                </a>
                <a href="{{ route('filament.app.resources.orders.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    Back to Orders
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl mx-auto w-full py-12 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-12">
        <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">{{ __('Checkout') }}</h1>
        <p class="text-lg text-gray-500 dark:text-gray-400">{{ __('Complete your order') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Order Summary -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Order Summary') }}</h2>
                
                <div class="space-y-4 border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Order ID') }}</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">#{{ $order->id }}</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Product') }}</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $order->productPrice->product->name }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Plan') }}</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $order->productPrice->name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Price -->
                <div class="flex justify-between items-center">
                    <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Total') }}:</span>
                    <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $order->productPrice->formatCost() }}</span>
                </div>
            </div>

            <!-- Payment Method Selection -->
            @if(count($enabledMethods) > 0)
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Select Payment Method') }}</h2>
                    
                    <div class="space-y-4">
                        @foreach($enabledMethods as $method)
                            <a href="{{ route('billing.checkout.page', $order->id) }}?method={{ $method->value }}" class="block">
                                <div class="relative border-2 rounded-lg p-6 cursor-pointer transition-all hover:shadow-lg
                                    @if($method->value === $selectedMethod)
                                        border-blue-500 bg-blue-50 dark:bg-blue-900/20
                                    @else
                                        border-gray-200 dark:border-gray-700 hover:border-blue-400
                                    @endif
                                ">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $method->getLabel() }}</h3>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                @switch($method->value)
                                                    @case('stripe')
                                                        {{ __('Secure payment with Stripe') }}
                                                        @break
                                                    @case('paypal')
                                                        {{ __('Pay safely with your PayPal account') }}
                                                        @break
                                                @endswitch
                                            </p>
                                        </div>
                                        @if($method->value === $selectedMethod)
                                            <div class="flex-shrink-0">
                                                <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-8">
                    <p class="text-red-800 dark:text-red-200 font-medium">{{ __('No payment methods available. Please contact support.') }}</p>
                </div>
            @endif
        </div>

        <!-- Action Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-8 sticky top-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">{{ __('Payment') }}</h3>

                <div class="space-y-3 mb-8">
                    @if($selectedMethod === 'stripe')
                        <form method="POST" action="{{ route('billing.checkout.stripe') }}">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="w-full px-4 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M1.5 8.67v8.66c0 .84.64 1.56 1.42 1.56h19.16c.78 0 1.42-.72 1.42-1.56V8.67c0-.84-.64-1.56-1.42-1.56H2.92c-.78 0-1.42.72-1.42 1.56z"/>
                                </svg>
                                <span>{{ __('Pay with Stripe') }}</span>
                            </button>
                        </form>
                    @elseif($selectedMethod === 'paypal')
                        <form method="POST" action="{{ route('billing.checkout.paypal') }}">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="w-full px-4 py-3 bg-yellow-400 text-gray-900 font-semibold rounded-lg hover:bg-yellow-500 transition-colors flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9.012 0C4.965.006.96 2.15.96 6.03v11.94C.96 21.851 4.965 24 9.012 24h5.976c4.047 0 8.052-2.149 8.052-6.03V6.03C23.04 2.15 19.035 0 14.988 0H9.012z"/>
                                </svg>
                                <span>{{ __('Pay with PayPal') }}</span>
                            </button>
                        </form>
                    @else
                        <button disabled class="w-full px-4 py-3 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg cursor-not-allowed opacity-50">
                            {{ __('Select Payment Method') }}
                        </button>
                    @endif
                </div>

                <a href="{{ route('filament.app.resources.orders.index') }}" class="w-full block px-4 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition text-center">
                    {{ __('Cancel') }}
                </a>

                <!-- Price Summary -->
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Subtotal') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $order->productPrice->formatCost() }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-gray-600 dark:text-gray-400">{{ __('Fees') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Free') }}</span>
                    </div>
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Total') }}</span>
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $order->productPrice->formatCost() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <p class="text-center text-gray-600 dark:text-gray-400">
                &copy; {{ now()->year }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
            </p>
        </div>
    </footer>
</div>
</body>
</html>
