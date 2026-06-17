<?php

declare(strict_types=1);

namespace Pdv\ServiceOrders;

final class ServiceOrderValidator
{
    /** @return list<string> */
    public function statuses(): array
    {
        return ['open', 'in_progress', 'ready', 'closed', 'cancelled'];
    }

    /** @return list<string> */
    public function manualStatuses(): array
    {
        return ['open', 'in_progress', 'ready', 'cancelled'];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function normalizeOrder(array $input): array
    {
        return [
            'opened_by_user_id' => $this->nullableInt($input['opened_by_user_id'] ?? null),
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'customer_phone' => $this->nullableString($input['customer_phone'] ?? null),
            'customer_document' => $this->nullableString($input['customer_document'] ?? null),
            'description' => $this->nullableString($input['description'] ?? null),
            'notes' => $this->nullableString($input['notes'] ?? null),
            'items' => $this->normalizeItems($input['items'] ?? []),
        ];
    }

    /** @param array<string, mixed> $data @return array<string, string> */
    public function order(array $data): array
    {
        $errors = [];
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        if ((string) ($data['customer_name'] ?? '') === '') {
            $errors['customer_name'] = 'Informe o nome do cliente.';
        }

        $this->validateLength($errors, 'customer_name', $data['customer_name'] ?? null, 190, 'Nome do cliente deve ter até 190 caracteres.');
        $this->validateLength($errors, 'customer_phone', $data['customer_phone'] ?? null, 60, 'Telefone deve ter até 60 caracteres.');
        $this->validateLength($errors, 'customer_document', $data['customer_document'] ?? null, 80, 'Documento deve ter até 80 caracteres.');
        $this->validateLength($errors, 'description', $data['description'] ?? null, 500, 'Descrição deve ter até 500 caracteres.');
        $this->validateLength($errors, 'notes', $data['notes'] ?? null, 1000, 'Notas devem ter até 1000 caracteres.');

        if ($items === []) {
            $errors['items'] = 'Adicione pelo menos um serviço ou produto à ordem.';
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

        return $errors;
    }

    /** @param array<string, mixed> $input @return array{status:string,actor_user_id:?int,notes:?string} */
    public function normalizeStatus(array $input): array
    {
        return [
            'status' => trim((string) ($input['status'] ?? '')),
            'actor_user_id' => $this->nullableInt($input['actor_user_id'] ?? null),
            'notes' => $this->nullableString($input['notes'] ?? null),
        ];
    }

    /** @param array{status:string,actor_user_id:?int,notes:?string} $data @return array<string, string> */
    public function status(array $data): array
    {
        $errors = [];

        if (! in_array($data['status'], $this->manualStatuses(), true)) {
            $errors['status'] = 'Status inválido para atualização manual.';
        }

        $this->validateLength($errors, 'notes', $data['notes'] ?? null, 190, 'Nota de status deve ter até 190 caracteres.');

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

            $variantId = (int) ($item['variant_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $discountRaw = $item['discount_cents'] ?? $item['discount'] ?? null;
            $discount = $this->moneyToCents($discountRaw ?? 0);

            if ($variantId <= 0 && $quantity <= 0 && ($discountRaw === null || trim((string) $discountRaw) === '')) {
                continue;
            }

            $normalized[] = [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'discount_cents' => $discount,
            ];
        }

        return $normalized;
    }

    private function validateLength(array &$errors, string $field, mixed $value, int $max, string $message): void
    {
        if ($value === null) {
            return;
        }

        if (strlen((string) $value) > $max) {
            $errors[$field] = $message;
        }
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
