@php
    $prefix = $prefix ?? 'manual-finance';
    $manualBudgetValue = old('manual_budget', $manualBudget ?? '');
    $financeRows = old('manual_finance_entries', $manualFinanceEntries ?? []);

    if (blank($financeRows) || !is_array($financeRows)) {
        $financeRows = [['description' => '', 'quantity' => 1, 'unit_price' => '']];
    }
@endphp

<x-input-group id="{{ $prefix }}" title="Handmatig kostenoverzicht" grid="grid grid-cols-1" class="mt-1">
    <p class="text-sm text-zinc-600">
        Voeg regels toe met omschrijving, aantal en bijdrage per deelnemer. Totalen worden automatisch berekend.
    </p>

    <div class="grid md:grid-cols-2 grid-cols-1 gap-3">
        <div>
            <label for="manual_budget" class="block text-sm font-semibold mb-1">Begroot bedrag (optioneel)</label>
            <input
                id="manual_budget"
                name="manual_budget"
                type="text"
                inputmode="decimal"
                placeholder="0.00"
                value="{{ $manualBudgetValue }}"
                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-zijpalm-300"
            />
        </div>
        <div class="rounded-md bg-[rgba(0,0,0,0.06)] px-3 py-2 text-sm space-y-1">
            <div class="flex justify-between"><span>Totaal:</span><strong id="{{ $prefix }}-total">€ 0,00</strong></div>
        </div>
    </div>

    <div class="rounded-md border border-zinc-300 overflow-hidden mt-2">
        <div class="bg-zijpalm-100 px-3 py-2 flex items-center justify-between">
            <h4 class="font-semibold text-sm">Tabelregels</h4>
            <button type="button" class="text-sm font-semibold text-zijpalm-700" onclick="manualFinanceAddRow('{{ $prefix }}')">+ Regel</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100">
                <tr>
                    <th class="text-left p-2">Omschrijving</th>
                    <th class="text-right p-2">Aantal</th>
                    <th class="text-right p-2">Bijdrage per deelnemer</th>
                    <th class="text-right p-2">Totaal</th>
                    <th class="p-2"></th>
                </tr>
                </thead>
                <tbody id="{{ $prefix }}-body">
                @foreach($financeRows as $row)
                    <tr>
                        <td class="p-1"><input type="text" name="manual_finance_entries[][description]" value="{{ $row['description'] ?? '' }}" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                        <td class="p-1"><input type="number" name="manual_finance_entries[][quantity]" step="0.01" min="0" value="{{ $row['quantity'] ?? '' }}" class="w-24 rounded border border-zinc-300 bg-white px-2 py-1 text-right"/></td>
                        <td class="p-1"><input type="text" name="manual_finance_entries[][unit_price]" inputmode="decimal" value="{{ $row['unit_price'] ?? '' }}" class="w-28 rounded border border-zinc-300 bg-white px-2 py-1 text-right"/></td>
                        <td class="p-1 text-right font-semibold" data-total-cell>€ 0,00</td>
                        <td class="p-1 text-right"><button type="button" class="text-red-600" onclick="manualFinanceRemoveRow(this, '{{ $prefix }}')">x</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-input-group>

<script>
    function parseMoneyInput(value) {
        if (value === undefined || value === null) {
            return 0;
        }

        const normalized = String(value).replace(',', '.').trim();
        const parsed = parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('nl-NL', {
            style: 'currency',
            currency: 'EUR'
        }).format(value || 0);
    }

    function manualFinanceRowTemplate(prefix) {
        return `
            <tr>
                <td class="p-1"><input type="text" name="manual_finance_entries[][description]" class="w-full rounded border border-zinc-300 bg-white px-2 py-1"/></td>
                <td class="p-1"><input type="number" name="manual_finance_entries[][quantity]" step="0.01" min="0" value="1" class="w-24 rounded border border-zinc-300 bg-white px-2 py-1 text-right"/></td>
                <td class="p-1"><input type="text" name="manual_finance_entries[][unit_price]" inputmode="decimal" class="w-28 rounded border border-zinc-300 bg-white px-2 py-1 text-right"/></td>
                <td class="p-1 text-right font-semibold" data-total-cell>€ 0,00</td>
                <td class="p-1 text-right"><button type="button" class="text-red-600" onclick="manualFinanceRemoveRow(this, '${prefix}')">x</button></td>
            </tr>
        `;
    }

    function manualFinanceRecalculate(prefix) {
        const rows = document.querySelectorAll(`#${prefix}-body tr`);
        let total = 0;

        rows.forEach(function (row) {
            const quantity = parseMoneyInput(row.querySelector('input[name*="[quantity]"]')?.value);
            const unitPrice = parseMoneyInput(row.querySelector('input[name*="[unit_price]"]')?.value);
            const rowTotal = quantity * unitPrice;
            total += rowTotal;

            const totalCell = row.querySelector('[data-total-cell]');
            if (totalCell) {
                totalCell.textContent = formatMoney(rowTotal);
            }
        });

        const totalElement = document.getElementById(`${prefix}-total`);
        if (totalElement) {
            totalElement.textContent = formatMoney(total);
        }
    }

    function manualFinanceAddRow(prefix) {
        const targetBody = document.getElementById(`${prefix}-body`);
        if (!targetBody) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = manualFinanceRowTemplate(prefix).trim();
        const row = template.content.firstChild;
        targetBody.appendChild(row);
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

        if (!targetBody.querySelector('tr')) {
            manualFinanceAddRow(prefix);
            return;
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
