@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
                <span style="font-size: 24px; font-weight: bold; color: #2563eb;">Elivate CRM</span>
            @else
                {!! $slot !!}
            @endif
        </a>
    </td>
</tr>
