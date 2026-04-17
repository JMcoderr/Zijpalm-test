@php
    $prefix = $prefix ?? 'manual-finance';
    $manualBudgetValue = old('manual_budget', $manualBudget ?? '');

    $incomeRows = old('manual_income_entries', $manualIncomeEntries ?? []);
    $expenseRows = old('manual_expense_entries', $manualExpenseEntries ?? []);

    if (blank($incomeRows) || !is_array($incomeRows)) {
        $incomeRows = [['description' => '', 'quantity' => 1, 'unit_price' => '']];
    }

    if (blank($expenseRows) || !is_array($expenseRows)) {
        $expenseRows = [['description' => '', 'quantity' => 1, 'unit_price' => '']];
    }
@endphp

<x-input-group id="{{ $prefix }}" title="Handmatig kostenoverzicht" grid="grid grid-cols-1" class="mt-1">
    <p class="text-sm text-zinc-600">
        Vul hier handmatig inkomsten en uitgaven in voor deze activiteit. Het overzicht op de activiteitspagina gebruikt deze regels en rekent totalen automatisch uit.
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
            <div class="flex justify-between"><span>Totaal inkomsten:</span><strong id="{{ $prefix }}-income-total">€ 0,00</strong></div>
            <div class="flex justify-between"><span>Totaal uitgaven:</span><strong id="{{ $prefix }}-expense-total">€ 0,00</strong></div>
            <div class="flex justify-between"><span>Saldo:</span><strong id="{{ $prefix }}-balance-total">€ 0,00</strong></div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 grid-cols-1 gap-3 mt-2">
        <div class="rounded-md border border-zinc-300 overflow-hidden">
            <div class="bg-zijpalm-100 px-3 py-2 flex items-center justify-between">
                <h4 class="font-semibold text-sm">Inkomsten</h4>
                <button type="button" class="text-sm font-semibold text-zijpalm-700" onclick="manualFinanceAddRow('{{ $prefix }}', 'income')">+ Regel</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100">
                    <tr>
                        <th class="text-left p-2">Omschrijving</th>
                        <th class="text-right p-2">Aantal</th>
                        <th class="text-right p-2">Per stuk</th>
                        <th class="text-right p-2">Totaal</th>
                        <th class="p-2"></th>
                    </tr>
                    </thead>
                    <tbody id="{{ $prefix }}-income-body">
                    @foreach($incomeRows as $row)
                        <tr>
                            <td class="p-1"><input type="text" name="manual_income_entries[][description]" value="{{ $row['description'] ?? '' }}" class="w-full rounded border border-zinc-300 px-2 py-1"/></td>
                            <td class="p-1"><input type="number" name="manual_income_entries[][quantity]" step="0.01" min="0" value="{{ $row['quantity'] ?? '' }}" class="w-24 rounded border border-zinc-300 px-2 py-1 text-right"/></td>
                            <td class="p-1"><input type="text" name="manual_income_entries[][unit_price]" inputmode="decimal" value="{{ $row['unit_price'] ?? '' }}" class="w-24 rounded border border-zinc-300 px-2 py-1 text-right"/></td>
                            <td class="p-1 text-right font-semibold" data-total-cell>€ 0,00</td>
                            <td class="p-1 text-right"><button type="button" class="text-red-600" onclick="manualFinanceRemoveRow(this, '{{ $prefix }}', 'income')">x</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-md border border-zinc-300 overflow-hidden">
            <div class="bg-zijpalm-100 px-3 py-2 flex items-center justify-between">
                <h4 class="font-semibold text-sm">Uitgaven</h4>
                <button type="button" class="text-sm font-semibold text-zijpalm-700" onclick="manualFinanceAddRow('{{ $prefix }}', 'expense')">+ Regel</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100">
                    <tr>
                        <th class="text-left p-2">Omschrijving</th>
                        <th class="text-right p-2">Aantal</th>
                        <th class="text-right p-2">Per stuk</th>
                        <th class="text-right p-2">Totaal</th>
                        <th class="p-2"></th>
                    </tr>
                    </thead>
                    <tbody id="{{ $prefix }}-expense-body">
                    @foreach($expenseRows as $row)
                        <tr>
                            <td class="p-1"><input type="text" name="manual_expense_entries[][description]" value="{{ $row['description'] ?? '' }}" class="w-full rounded border border-zinc-300 px-2 py-1"/></td>
                            <td class="p-1"><input type="number" name="manual_expense_entries[][quantity]" step="0.01" min="0" value="{{ $row['quantity'] ?? '' }}" class="w-24 rounded border border-zinc-300 px-2 py-1 text-right"/></td>
                            <td class="p-1"><input type="text" name="manual_expense_entries[][unit_price]" inputmode="decimal" value="{{ $row['unit_price'] ?? '' }}" class="w-24 rounded border border-zinc-300 px-2 py-1 text-right"/></td>
                            <td class="p-1 text-right font-semibold" data-total-cell>€ 0,00</td>
                            <td class="p-1 text-right"><button type="button" class="text-red-600" onclick="manualFinanceRemoveRow(this, '{{ $prefix }}', 'expense')">x</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
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

    function manualFinanceRowTemplate(prefix, type) {
        const rowName = type === 'income' ? 'manual_income_entries' : 'manual_expense_entries';

        return `
            <tr>
                <td class="p-1"><input type="text" name="${rowName}[][description]" class="w-full rounded border border-zinc-300 px-2 py-1"/></td>
                <td class="p-1"><input type="number" name="${rowName}[][quantity]" step="0.01" min="0" value="1" class="w-24 rounded border border-zinc-300 px-2 py-1 text-right"/></td>
                <td class="p-1"><input type="text" name="${rowName}[][unit_price]" inputmode="decimal" class="w-24 rounded border border-zinc-300 px-2 py-1 text-right"/></td>
                <td class="p-1 text-right font-semibold" data-total-cell>€ 0,00</td>
                <td class="p-1 text-right"><button type="button" class="text-red-600" onclick="manualFinanceRemoveRow(this, '${prefix}', '${type}')">x</button></td>
            </tr>
        `;
    }

    function manualFinanceRecalculate(prefix) {
        const incomeRows = document.querySelectorAll(`#${prefix}-income-body tr`);
        const expenseRows = document.querySelectorAll(`#${prefix}-expense-body tr`);

        let incomeTotal = 0;
        let expenseTotal = 0;

        incomeRows.forEach(function (row) {
            const quantity = parseMoneyInput(row.querySelector('input[name*="[quantity]"]')?.value);
            const unitPrice = parseMoneyInput(row.querySelector('input[name*="[unit_price]"]')?.value);
            const rowTotal = quantity * unitPrice;
            incomeTotal += rowTotal;

            const totalCell = row.querySelector('[data-total-cell]');
            if (totalCell) {
                totalCell.textContent = formatMoney(rowTotal);
            }
        });

        expenseRows.forEach(function (row) {
            const quantity = parseMoneyInput(row.querySelector('input[name*="[quantity]"]')?.value);
            const unitPrice = parseMoneyInput(row.querySelector('input[name*="[unit_price]"]')?.value);
            const rowTotal = quantity * unitPrice;
            expenseTotal += rowTotal;

            const totalCell = row.querySelector('[data-total-cell]');
            if (totalCell) {
                totalCell.textContent = formatMoney(rowTotal);
            }
        });

        const balance = incomeTotal - expenseTotal;

        const incomeTotalElement = document.getElementById(`${prefix}-income-total`);
        const expenseTotalElement = document.getElementById(`${prefix}-expense-total`);
        const balanceTotalElement = document.getElementById(`${prefix}-balance-total`);

        if (incomeTotalElement) {
            incomeTotalElement.textContent = formatMoney(incomeTotal);
        }

        if (expenseTotalElement) {
            expenseTotalElement.textContent = formatMoney(expenseTotal);
        }

        if (balanceTotalElement) {
            balanceTotalElement.textContent = formatMoney(balance);
        }
    }

    function manualFinanceAddRow(prefix, type) {
        const targetBody = document.getElementById(`${prefix}-${type}-body`);

        if (!targetBody) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = manualFinanceRowTemplate(prefix, type).trim();
        const row = template.content.firstChild;
        targetBody.appendChild(row);
        manualFinanceRecalculate(prefix);
    }

    function manualFinanceRemoveRow(button, prefix, type) {
        const targetBody = document.getElementById(`${prefix}-${type}-body`);
        if (!targetBody) {
            return;
        }

        const row = button.closest('tr');
        if (row) {
            row.remove();
        }

        if (!targetBody.querySelector('tr')) {
            manualFinanceAddRow(prefix, type);
            return;
        }

        manualFinanceRecalculate(prefix);
    }

    document.addEventListener('input', function (event) {
        const target = event.target;

        if (!target || !target.name) {
            return;
        }

        if (target.name.includes('manual_income_entries') || target.name.includes('manual_expense_entries')) {
            manualFinanceRecalculate('{{ $prefix }}');
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        manualFinanceRecalculate('{{ $prefix }}');
    });
</script>
