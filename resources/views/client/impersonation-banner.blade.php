{{-- Example impersonation banner for client panel --}}
@if (Session::has('is_impersonating'))
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                @svg('emoji-warning', ['class' => 'w-5 h-5 mr-2'])
                <span class="font-medium">
                    You are viewing this client's account as an administrator.
                </span>
            </div>
            <form action="{{ route('stop-impersonating') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                    class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-3 py-1 rounded text-sm font-medium transition-colors">
                    Stop Impersonating
                </button>
            </form>
        </div>
    </div>
@endif
