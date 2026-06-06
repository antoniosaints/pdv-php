<?php

declare(strict_types=1);

namespace Pdv\Sales;

final class SalesValidator
{
    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function normalizeSale(array $input): array
    {
        return [
            'cashier_user_id' => $this->nullableInt($input['cashier_user_id'] ?? null),
            'customer_name' => $this->nullableString($input['customer_name'] ?? null),
            'notes' => $this->nullableString($input['notes'] ?? null),
            'items' => $this->normalizeItems($input['items'] ?? []),
            'payments' => $this->normalizePayments($input['payments'] ?? []),
        ];
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function sale(array $data): array
    {
        $errors = [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        $payments = is_array($data['payments'] ?? null) ? $data['payments'] : [];

        if ($items === []) {
            $errors['items'] = 'Adicione pelo menos um item à venda.';
        }

        foreach ($items as $index => $item) {
            $prefix = 'items.' . $index;

            if ((int) ($item['variant_id'] ?? 0) <= 0) {
                $errors[$prefix . '.variant_id'] = 'Selecione um item válido.';
            }

            if ((int) ($item['quantity'] ?? 0) <= 0) {
                $errors[$prefix . '.quantity'] = 'Quantidade deve ser maior que zero.';
            }

            if ((int) ($item['discount_cents'] ?? 0) < 0) {
                $errors[$prefix . '.discount'] = 'Desconto não pode ser negativo.';
            }
        }

        if ($payments === []) {
            $errors['payments'] = 'Informe pelo menos uma forma de pagamento.';
        }

        foreach ($payments as $index => $payment) {
            $prefix = 'payments.' . $index;

            if (! in_array((string) ($payment['method'] ?? ''), ['cash', 'credit_card', 'debit_card', 'pix', 'other'], true)) {
                $errors[$prefix . '.method'] = 'Forma de pagamento inválida.';
            }

            if ((int) ($payment['amount_cents'] ?? 0) <= 0) {
                $errors[$prefix . '.amount'] = 'Valor do pagamento deve ser maior que zero.';
            }
        }

        return $errors;
    }

    public function moneyToCents(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value * 100);
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return 0;
        }

        $raw = str_replace(['R$', ' '], '', $raw);

        if (str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        }

        if (! is_numeric($raw)) {
            return -1;
        }

        return (int) round(((float) $raw) * 100);
    }

    /** @param mixed $items @return list<array<string, int>> */
    private function normalizeItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized[] = [
                'variant_id' => (int) ($item['variant_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'discount_cents' => $this->moneyToCents($item['discount_cents'] ?? $item['discount'] ?? 0),
            ];
        }

        return $normalized;
    }

    /** @param mixed $payments @return list<array<string, mixed>> */
    private function normalizePayments(mixed $payments): array
    {
        if (! is_array($payments)) {
            return [];
        }

        $normalized = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                continue;
            }

            $normalized[] = [
                'method' => trim((string) ($payment['method'] ?? '')),
                'amount_cents' => $this->moneyToCents($payment['amount_cents'] ?? $payment['amount'] ?? 0),
                'reference' => $this->nullableString($payment['reference'] ?? null),
            ];
        }

        return $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
