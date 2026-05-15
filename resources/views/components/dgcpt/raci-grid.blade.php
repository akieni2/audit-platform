@props([
    'rows' => [],
    'headers' => [],
])

<div {{ $attributes->class('overflow-x-auto rounded-[2rem] border border-[rgba(0,209,255,0.10)] bg-[rgba(5,8,22,0.72)] p-4') }}>
    <table class="dgcpt-table min-w-full text-sm">
        <thead>
            <tr>
                <th class="text-left">Processus</th>
                @foreach ($headers as $header)
                    <th class="text-left">{{ is_object($header) ? $header->name : data_get($header, 'name') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="font-semibold text-[#E6EEF8]">{{ $row['process_label'] ?? 'Processus' }}</td>
                    @foreach (($row['cells'] ?? []) as $cell)
                        <td>{{ data_get($cell, 'assignment.role_type') ? strtoupper(substr((string) data_get($cell, 'assignment.role_type'), 0, 1)) : '-' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
