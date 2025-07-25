@if(session('is_impersonating'))
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        You are currently impersonating a user
                    </h3>
                    <div class="mt-1 text-sm text-yellow-700">
                        <p>You are viewing this account as an administrator. All actions will be performed as this user.</p>
                    </div>
                </div>
            </div>
            <div class="flex-shrink-0">
                <form action="{{ route('stop-impersonating') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded text-sm font-medium transition-colors">
                        Stop Impersonating
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
