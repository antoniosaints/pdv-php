<?php
$variantValue = static function (array $variant, string $key, string $moneyKey = ''): string {
    if ($moneyKey !== '' && isset($variant[$moneyKey]) && $variant[$moneyKey] !== null) {
        return number_format(((int) $variant[$moneyKey]) / 100, 2, ',', '.');
    }

    return (string) ($variant[$key] ?? '');
};
$variantError = static fn (string $field): string => isset($variantErrors[$field]) ? '<small class="field-error">' . htmlspecialchars($variantErrors[$field], ENT_QUOTES, 'UTF-8') . '</small>' : '';
$formVariant = $formVariant ?? $variantInput;
$formAction = $formAction ?? ('/catalog/' . (int) $product['id'] . '/variants');
$formTitle = $formTitle ?? 'Adicionar variante';
$formSubmit = $formSubmit ?? 'Salvar variante';
?>
<form class="variant-form stack" method="post" action="<?= htmlspecialchars((string) $formAction, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="section-heading section-heading--compact">
        <div>
            <p class="eyebrow">Variante</p>
            <h2><?= htmlspecialchars((string) $formTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
    </div>

    <?php if (isset($variantErrors['_token'])): ?>
        <div class="alert alert--danger" role="alert"><?= htmlspecialchars($variantErrors['_token'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="form-grid">
        <label class="field">
            <span>Nome da variante</span>
            <input type="text" name="variant_name" required value="<?= htmlspecialchars($variantValue($formVariant, 'name'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Preta / M">
            <?= $variantError('name') ?>
        </label>
        <label class="field">
            <span>SKU da variante</span>
            <input type="text" name="variant_sku" value="<?= htmlspecialchars($variantValue($formVariant, 'sku'), ENT_QUOTES, 'UTF-8') ?>" placeholder="CAM-PRETA-M">
            <?= $variantError('sku') ?>
        </label>
        <label class="field">
            <span>Código de barras</span>
            <input type="text" name="barcode" value="<?= htmlspecialchars($variantValue($formVariant, 'barcode'), ENT_QUOTES, 'UTF-8') ?>" placeholder="7890000000011">
            <?= $variantError('barcode') ?>
        </label>
        <label class="field">
            <span>Estoque inicial</span>
            <input type="number" min="0" step="1" name="current_stock" value="<?= htmlspecialchars($variantValue($formVariant, 'current_stock'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $variantError('current_stock') ?>
        </label>
        <label class="field">
            <span>Custo específico</span>
            <input type="text" inputmode="decimal" name="variant_cost" value="<?= htmlspecialchars($variantValue($formVariant, 'cost', 'cost_cents'), ENT_QUOTES, 'UTF-8') ?>" placeholder="opcional">
            <?= $variantError('cost_cents') ?>
        </label>
        <label class="field">
            <span>Preço específico</span>
            <input type="text" inputmode="decimal" name="variant_price" value="<?= htmlspecialchars($variantValue($formVariant, 'price', 'price_cents'), ENT_QUOTES, 'UTF-8') ?>" placeholder="opcional">
            <?= $variantError('price_cents') ?>
        </label>
    </div>

    <label class="field">
        <span>Atributos</span>
        <input type="text" name="attributes" value="<?= htmlspecialchars($variantValue($formVariant, 'attributes', 'attributes_json'), ENT_QUOTES, 'UTF-8') ?>" placeholder="cor=preta; tamanho=M">
    </label>

    <div class="check-row">
        <input type="hidden" name="variant_active" value="0">
        <label><input type="checkbox" name="variant_active" value="1" <?= ((int) ($formVariant['active'] ?? 1)) === 1 ? 'checked' : '' ?>> Variante ativa para venda</label>
    </div>

    <div class="actions">
        <button class="button" type="submit"><?= htmlspecialchars((string) $formSubmit, ENT_QUOTES, 'UTF-8') ?></button>
    </div>
</form>
