<?php

declare(strict_types=1);

namespace Pdv\Catalog;

final class CatalogValidator
{
    /** @param array<string, mixed> $data @return array<string, string> */
    public function product(array $data): array
    {
        $errors = [];
        $type = (string) ($data['type'] ?? 'product');
        $name = trim((string) ($data['name'] ?? ''));

        if (! in_array($type, ['product', 'service'], true)) {
            $errors['type'] = 'Tipo de cadastro inválido.';
        }

        if (strlen($name) < 2) {
            $errors['name'] = 'Informe um nome com pelo menos 2 caracteres.';
        }

        foreach (['cost_cents' => 'Custo', 'price_cents' => 'Preço'] as $field => $label) {
            if ((int) ($data[$field] ?? 0) < 0) {
                $errors[$field] = "{$label} não pode ser negativo.";
            }
        }

        if ((int) ($data['stock_min'] ?? 0) < 0) {
            $errors['stock_min'] = 'Estoque mínimo não pode ser negativo.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function variant(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));

        if (strlen($name) < 1) {
            $errors['name'] = 'Informe o nome da variante.';
        }

        foreach (['cost_cents' => 'Custo', 'price_cents' => 'Preço'] as $field => $label) {
            $value = $data[$field] ?? null;

            if ($value !== null && (int) $value < 0) {
                $errors[$field] = "{$label} não pode ser negativo.";
            }
        }

        if ((int) ($data['current_stock'] ?? 0) < 0) {
            $errors['current_stock'] = 'Estoque inicial não pode ser negativo.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function normalizeProduct(array $input): array
    {
        $type = (string) ($input['type'] ?? 'product');
        $trackStock = $type === 'service' ? false : $this->bool($input['track_stock'] ?? true);

        return [
            'type' => $type,
            'sku' => $this->nullableString($input['sku'] ?? null),
            'name' => trim((string) ($input['name'] ?? '')),
            'description' => $this->nullableString($input['description'] ?? null),
            'cost_cents' => $this->moneyToCents($input['cost_cents'] ?? $input['cost'] ?? 0),
            'price_cents' => $this->moneyToCents($input['price_cents'] ?? $input['price'] ?? 0),
            'track_stock' => $trackStock,
            'stock_min' => (int) ($input['stock_min'] ?? 0),
            'label_name' => $this->nullableString($input['label_name'] ?? null),
            'active' => $this->bool($input['active'] ?? true),
        ];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function normalizeVariant(array $input): array
    {
        $cost = $input['cost_cents'] ?? $input['cost'] ?? null;
        $price = $input['price_cents'] ?? $input['price'] ?? null;

        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'sku' => $this->nullableString($input['sku'] ?? null),
            'barcode' => $this->nullableString($input['barcode'] ?? null),
            'attributes_json' => $this->nullableJson($input['attributes'] ?? $input['attributes_json'] ?? null),
            'cost_cents' => $cost === null || $cost === '' ? null : $this->moneyToCents($cost),
            'price_cents' => $price === null || $price === '' ? null : $this->moneyToCents($price),
            'current_stock' => (int) ($input['current_stock'] ?? 0),
            'active' => $this->bool($input['active'] ?? true),
        ];
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

    private function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function nullableJson(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }

            return json_encode(['value' => $value], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
