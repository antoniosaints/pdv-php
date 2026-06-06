<?php

declare(strict_types=1);

namespace Pdv\Stock;

final class StockValidator
{
    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function normalizeReplenishment(array $input): array
    {
        return [
            'variant_id' => (int) ($input['variant_id'] ?? 0),
            'quantity' => (int) ($input['quantity'] ?? 0),
            'reason' => $this->nullableString($input['reason'] ?? null) ?? 'Entrada de estoque',
        ];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function normalizeAdjustment(array $input): array
    {
        return [
            'variant_id' => (int) ($input['variant_id'] ?? 0),
            'delta' => (int) ($input['delta'] ?? 0),
            'reason' => $this->nullableString($input['reason'] ?? null),
        ];
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function replenishment(array $data): array
    {
        $errors = [];

        if ((int) ($data['variant_id'] ?? 0) <= 0) {
            $errors['variant_id'] = 'Selecione uma variante válida.';
        }

        if ((int) ($data['quantity'] ?? 0) <= 0) {
            $errors['quantity'] = 'Quantidade de entrada deve ser maior que zero.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function adjustment(array $data): array
    {
        $errors = [];

        if ((int) ($data['variant_id'] ?? 0) <= 0) {
            $errors['variant_id'] = 'Selecione uma variante válida.';
        }

        if ((int) ($data['delta'] ?? 0) === 0) {
            $errors['delta'] = 'Ajuste deve ser diferente de zero.';
        }

        if (($data['reason'] ?? null) === null) {
            $errors['reason'] = 'Informe o motivo do ajuste.';
        }

        return $errors;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
