@php
    $prefix = $prefix ?? 'manual-finance';
    $oldFinanceRows = old('manual_finance_entries');
    $financeRows = $oldFinanceRows !== null ? $oldFinanceRows : ($manualFinanceEntries ?? []);

    if (!is_array($financeRows)) {
        $financeRows = [];
    }
@endphp

<x-input-group id="{{ $prefix }}" title="Kostenoverzicht" grid="grid grid-cols-1" class="mt-3 md:max-w-xl">
    <p class="text-sm text-zinc-600 mb-1">
        Vul per regel omschrijving, aantal en bijdrage in. Op de activiteitenpagina worden alle regels automatisch opgeteld.
    </p>

    <div class="flex justify-center mb-2">
        <x-zijpalm-button type="action" variant="add" size="size-5" onclick="manualFinanceAddRow('{{ $prefix }}')"/>
    </div>

    <div class="rounded-xl border border-[rgba(0,0,0,0.15)] bg-[rgba(255,255,255,0.92)] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[rgba(0,0,0,0.06)] border-b border-[rgba(0,0,0,0.15)]">
                    <tr>
                        <th class="text-left p-2 font-bold text-zijpalm-700">Omschrijving</th>
                        <th class="text-left p-2 font-bold text-zijpalm-700">Aantal</th>
                        <th class="text-left p-2 font-bold text-zijpalm-700">Bijdrage per deelnemer</th>
                        <th class="text-left p-2 font-bold text-zijpalm-700">Totaal</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody id="{{ $prefix }}-body" class="divide-y divide-[rgba(0,0,0,0.1)]">
                    @foreach($financeRows as $row)
                        <tr class="bg-[rgba(255,255,255,0.75)]">
                            <td class="p-1.5 align-top"><input type="text" name="manual_finance_entries[][description]" value="{{ $row['description'] ?? '' }}" required class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-zinc-900"/></td>
                            <td class="p-1.5 align-top"><input type="number" name="manual_finance_entries[][quantity]" step="0.01" min="0" value="{{ $row['quantity'] ?? '' }}" required class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-zinc-900"/></td>
                            <td class="p-1.5 align-top"><input type="text" name="manual_finance_entries[][unit_price]" inputmode="decimal" value="{{ $row['unit_price'] ?? '' }}" required class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-zinc-900"/></td>
                            <td class="p-2 align-top font-semibold text-zijpalm-700" data-total-cell>{{ isset($row['total']) ? number_format((float) $row['total'], 2, ',', '.') : '0,00' }}</td>
                            <td class="p-1.5 text-right align-top">
                                <x-zijpalm-button type="action" variant="remove" size="size-4" onclick="manualFinanceRemoveRow(this, '{{ $prefix }}')"/>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-input-group>

<script>
    function parseManualFinanceNumber(value) {
        if (value === undefined || value === null || value === '') {
            return 0;
        }

        const normalized = String(value).replace(',', '.').trim();
        const parsed = parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatManualFinanceNumber(value) {
        return Number(value || 0).toLocaleString('nl-NL', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function manualFinanceRecalculate(prefix) {
        const rows = document.querySelectorAll(`#${prefix}-body tr`);

        rows.forEach(function (row) {
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            const unitPriceInput = row.querySelector('input[name*="[unit_price]"]');
            const totalCell = row.querySelector('[data-total-cell]');

            const quantity = parseManualFinanceNumber(quantityInput?.value);
            const unitPrice = parseManualFinanceNumber(unitPriceInput?.value);
            const rowTotal = quantity * unitPrice;

            if (totalCell) {
                totalCell.textContent = formatManualFinanceNumber(rowTotal);
            }
        });
    }

    function manualFinanceRowTemplate(prefix) {
        return `
            <tr class="bg-[rgba(255,255,255,0.75)]">
                <td class="p-1.5 align-top"><input type="text" name="manual_finance_entries[][description]" required class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-zinc-900"/></td>
                <td class="p-1.5 align-top"><input type="number" name="manual_finance_entries[][quantity]" step="0.01" min="0" value="" required class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-zinc-900"/></td>
                <td class="p-1.5 align-top"><input type="text" name="manual_finance_entries[][unit_price]" inputmode="decimal" required class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-zinc-900"/></td>
                <td class="p-2 align-top font-semibold text-zijpalm-700" data-total-cell>0,00</td>
                <td class="p-1.5 text-right align-top">
                    <button type="button" class="bg-linear-to-t from-zinc-300 to-zinc-200 inset-shadow-zinc-100 text-red-500 rounded-full p-1 hover:scale-105 duration-300" onclick="manualFinanceRemoveRow(this, '${prefix}')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </td>
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
        manualFinanceRecalculate(prefix);
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

        manualFinanceRecalculate(prefix);
    }

    document.addEventListener('input', function (event) {
        const target = event.target;

        if (!target || !target.name) {
            return;
        }

        if (target.name.includes('manual_finance_entries')) {
            manualFinanceRecalculate('{{ $prefix }}');
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        manualFinanceRecalculate('{{ $prefix }}');
    });
</script>
