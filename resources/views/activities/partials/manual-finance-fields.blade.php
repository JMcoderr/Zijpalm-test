@php
    $prefix = $prefix ?? 'manual-finance';
    $financeRows = old('manual_finance_entries', $manualFinanceEntries ?? []);

    if (blank($financeRows) || !is_array($financeRows)) {
        $financeRows = [['description' => '', 'quantity' => 1, 'unit_price' => '']];
    }
@endphp

<x-input-group id="{{ $prefix }}" title="Kostenoverzicht" grid="grid grid-cols-1" class="mt-3 md:max-w-xl">
    <div class="rounded-md border border-zinc-300 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100">
                <tr>
                    <th class="text-left p-2">Omschrijving</th>
                    <th class="text-left p-2">Aantal</th>
                    <th class="text-left p-2">Bijdrage per deelnemer</th>
                    <th class="text-left p-2">Totaal</th>
                    <th class="p-2"></th>
                </tr>
                </thead>
                <tbody id="{{ $prefix }}-body">
                @foreach($financeRows as $row)
                    <tr>
                        <td class="p-1 align-top"><input type="text" name="manual_finance_entries[][description]" value="{{ $row['description'] ?? '' }}" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                        <td class="p-1 align-top"><input type="number" name="manual_finance_entries[][quantity]" step="0.01" min="0" value="{{ $row['quantity'] ?? '' }}" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                        <td class="p-1 align-top"><input type="text" name="manual_finance_entries[][unit_price]" inputmode="decimal" value="{{ $row['unit_price'] ?? '' }}" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                        <td class="p-1 align-top"><input type="text" value="{{ isset($row['total']) ? number_format((float) $row['total'], 2, ',', '.') : '' }}" readonly class="w-full rounded border border-zinc-300 bg-zinc-50 px-2 py-1"/></td>
                        <td class="p-1 text-right align-top"><button type="button" class="text-red-600" onclick="manualFinanceRemoveRow(this, '{{ $prefix }}')">x</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-2 py-2 border-t border-zinc-300 bg-zinc-50">
            <button type="button" class="text-sm font-semibold text-zijpalm-700" onclick="manualFinanceAddRow('{{ $prefix }}')">+ Lijn toevoegen</button>
        </div>
    </div>
</x-input-group>

<script>
    function manualFinanceRowTemplate(prefix) {
        return `
            <tr>
                <td class="p-1 align-top"><input type="text" name="manual_finance_entries[][description]" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                <td class="p-1 align-top"><input type="number" name="manual_finance_entries[][quantity]" step="0.01" min="0" value="1" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                <td class="p-1 align-top"><input type="text" name="manual_finance_entries[][unit_price]" inputmode="decimal" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                <td class="p-1 align-top"><input type="text" readonly class="w-full rounded border border-zinc-300 bg-zinc-50 px-2 py-1"/></td>
                <td class="p-1 text-right align-top"><button type="button" class="text-red-600" onclick="manualFinanceRemoveRow(this, '${prefix}')">x</button></td>
            </tr>
        `;
    }

    function manualFinanceAddRow(prefix) {
        const targetBody = document.getElementById(`${prefix}-body`);
        if (!targetBody) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = manualFinanceRowTemplate(prefix).trim();
        targetBody.appendChild(template.content.firstChild);
    }

    function manualFinanceRemoveRow(button, prefix) {
        const targetBody = document.getElementById(`${prefix}-body`);
        if (!targetBody) {
            return;
        }

        const row = button.closest('tr');
        if (row) {
            row.remove();
        }

        if (!targetBody.querySelector('tr')) {
            manualFinanceAddRow(prefix);
        }
    }
</script>
