<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscribe') }}
        </h2>
    </x-slot>

    @php
    $defaultPlan = $planTypes->firstWhere('id', 1);
    $paymentMethods = collect($paymentMethods);
    $defaultMethod = $paymentMethods->first(fn($m) => strtolower($m) === 'stripe');
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-visible shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form method="post" action="{{ route('stripe.init') }}" class="mt-6 space-y-6">
                        @csrf
                        @method('post')
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <!-- NAME -->
                        <div>
                            <x-input-label value="Name" />
                            <x-text-input type="text" class="mt-1 block w-full" :value="$user->name" disabled />
                        </div>

                        <!-- EMAIL + PAYMENT METHOD -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <!-- Email -->
                            <div>
                                <x-input-label value="Email" />
                                <x-text-input type="email" class="mt-1 block w-full" :value="$user->email" disabled />
                            </div>

                            <!-- Payment Method -->
                            <div class="relative">
                                <x-input-label value="Payment Method" />

                                <button type="button" id="methodBtn" class="w-full flex justify-between items-center px-4 py-2 mt-1 border rounded-md bg-white">
                                    <span id="selectedMethod">{{ ucfirst($defaultMethod ?? ' Select Payment Method') }}</span>
                                </button>

                                <div id="methodDropdown" class="hidden absolute w-full mt-2 bg-white border rounded-md shadow-lg z-[9999]">

                                    @foreach($paymentMethods as $method)
                                    <div class="method-option px-4 py-2 hover:bg-gray-100 cursor-pointer" data-value="{{ strtolower($method) }}">
                                        {{ strtolower($method) }}
                                    </div>
                                    @endforeach
                                </div>

                                <input type="hidden" name="gateway" id="gateway" value="{{ strtolower($defaultMethod) }}">
                            </div>
                        </div>

                        <!-- PAYMENT TYPE + PRICE -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <!-- Payment Type -->
                            <div class="relative">
                                <x-input-label value="Plan Type" />

                                <button type="button" id="planBtn" class="w-full flex justify-between items-center px-4 py-2 mt-1 border rounded-md bg-white">
                                    <span id="selectedPlan">{{ $defaultPlan->name ?? 'Select Payment Type' }}</span>
                                </button>

                                <div id="planDropdown" class="hidden absolute w-full mt-2 bg-white border rounded-md shadow-lg z-[9999]">

                                    @foreach($planTypes as $plan)
                                    <div class="plan-option px-4 py-2 hover:bg-gray-100 cursor-pointer" data-id="{{ $plan->id }}" data-name="{{ $plan->name }}" data-price="{{ $plan->price }}">
                                        {{ $plan->name }} - ₹{{ $plan->price }}
                                    </div>
                                    @endforeach
                                </div>

                                <input type="hidden" name="plan_id" id="plan_id" value="{{ $defaultPlan->id ?? '' }}">
                            </div>

                            <!-- Price -->
                            <div>
                                <x-input-label value="Amount" />
                                <div class="relative mt-1">
                                    <span class="absolute left-3 top-2.5 text-gray-500">₹</span>
                                    <input type="text" name="amount" id="amount" value="{{ $defaultPlan->price ?? '' }}" readonly class="pl-7 block w-full border-gray-300 rounded-md bg-gray-100">
                                </div>
                            </div>

                        </div>

                        <!-- Submit -->
                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- ✅ jQuery CDN (PLACE BEFORE SCRIPT) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- ✅ jQuery Logic -->
    <script>
        $(function () {

            // Toggle dropdowns
            $('#planBtn').on('click', function () {
                $('#planDropdown').toggle();
            });

            $('#methodBtn').on('click', function () {
                $('#methodDropdown').toggle();
            });

            // Select Plan
            $('.plan-option').on('click', function () {
                let name = $(this).data('name');
                let id = $(this).data('id');
                let price = $(this).data('price');

                $('#selectedPlan').text(name);
                $('#plan_id').val(id);
                $('#amount').val(price);

                $('#planDropdown').hide();
            });

            // Select Payment Method
            $('.method-option').on('click', function () {
                let value = $(this).data('value');

                $('#selectedMethod').text(value.charAt(0).toUpperCase() + value.slice(1));
                $('#gateway').val(value);

                $('#methodDropdown').hide();
            });

            // Close dropdown on outside click
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#planBtn, #planDropdown').length) {
                    $('#planDropdown').hide();
                }
                if (!$(e.target).closest('#methodBtn, #methodDropdown').length) {
                    $('#methodDropdown').hide();
                }
            });

        });
    </script>

</x-app-layout>