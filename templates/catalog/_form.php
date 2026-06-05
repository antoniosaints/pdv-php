<?php
$money = static function (array $product, string $field): string {
    if (isset($product[$field])) {
        return (string) $product[$field];
    }

    $centsField = $field . '_cents';
    $cents = (int) ($product[$centsField] ?? 0);

    return number_format($cents / 100, 2, ',', '.');
};
$error = static fn (string $field): string => isset($errors[$field]) ? '<small class="field-error">' . htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8') . '</small>' : '';
$type = (string) ($product['type'] ?? 'product');
?>
<form class="stack" method="post" action="<?= htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <?php if (isset($errors['_token'])): ?>
        <div class="alert alert--danger" role="alert"><?= htmlspecialchars($errors['_token'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="form-grid">
        <label class="field">
            <span>Tipo</span>
            <select name="type" required>
                <option value="product" <?= $type === 'product' ? 'selected' : '' ?>>Produto</option>
                <option value="service" <?= $type === 'service' ? 'selected' : '' ?>>Serviço</option>
            </select>
            <?= $error('type') ?>
        </label>

        <label class="field">
            <span>SKU</span>
            <input type="text" name="sku" value="<?= htmlspecialchars((string) ($product['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="CAM-BASICA">
            <?= $error('sku') ?>
        </label>
    </div>

    <label class="field">
        <span>Nome</span>
        <input type="text" name="name" required value="<?= htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Camiseta básica">
        <?= $error('name') ?>
    </label>

    <label class="field">
        <span>Descrição</span>
        <textarea name="description" rows="3" placeholder="Detalhes úteis para venda, etiqueta ou ordem de serviço."><?= htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    </label>

    <div class="form-grid">
        <label class="field">
            <span>Custo</span>
            <input type="text" inputmode="decimal" name="cost" value="<?= htmlspecialchars($money($product, 'cost'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $error('cost_cents') ?>
        </label>

        <label class="field">
            <span>Preço</span>
            <input type="text" inputmode="decimal" name="price" value="<?= htmlspecialchars($money($product, 'price'), ENT_QUOTES, 'UTF-8') ?>">
            <?= $error('price_cents') ?>
        </label>

        <label class="field">
            <span>Estoque mínimo</span>
            <input type="number" min="0" step="1" name="stock_min" value="<?= htmlspecialchars((string) ($product['stock_min'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
            <?= $error('stock_min') ?>
        </label>
    </div>

    <label class="field">
        <span>Nome para etiqueta</span>
        <input type="text" name="label_name" value="<?= htmlspecialchars((string) ($product['label_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome curto para etiqueta">
    </label>

    <div class="check-row">
        <input type="hidden" name="track_stock" value="0">
        <label><input type="checkbox" name="track_stock" value="1" <?= ((int) ($product['track_stock'] ?? 1)) === 1 ? 'checked' : '' ?>> Controla estoque físico</label>
    </div>

    <div class="check-row">
        <input type="hidden" name="active" value="0">
        <label><input type="checkbox" name="active" value="1" <?= ((int) ($product['active'] ?? 1)) === 1 ? 'checked' : '' ?>> Item ativo para venda</label>
    </div>

    <div class="actions">
        <button class="button" type="submit"><?= htmlspecialchars((string) $submitLabel, ENT_QUOTES, 'UTF-8') ?></button>
        <a class="button button--ghost" href="/catalog">Cancelar</a>
    </div>
</form>
