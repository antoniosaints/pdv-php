<main class="shell shell--narrow">
    <section class="panel form-panel">
        <p class="eyebrow">Catálogo</p>
        <h1>Editar item</h1>
        <p class="form-panel__copy">Alterações feitas aqui serão usadas pelo PDV, etiquetas, estoque e relatórios.</p>
        <?php
            $action = '/catalog/' . (int) $product['id'];
            $submitLabel = 'Atualizar item';
            require __DIR__ . '/_form.php';
        ?>
    </section>
</main>
