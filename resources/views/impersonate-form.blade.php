<!DOCTYPE html>
<html>
<head>
    <title>Impersonating User...</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
        <h2>Impersonating {{ $user->name }}...</h2>
        <p>You will be redirected to the client panel momentarily.</p>

        <form id="impersonateForm" action="{{ route('impersonate', $user) }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>

    <script>
        // Auto-submit the form after a brief delay
        setTimeout(function() {
            document.getElementById('impersonateForm').submit();
        }, 1000);
    </script>
</body>
</html>
