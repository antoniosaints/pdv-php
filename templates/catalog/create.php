<main class="shell shell--narrow">
    <section class="panel form-panel">
        <p class="eyebrow">Catálogo</p>
        <h1>Novo produto ou serviço</h1>
        <p class="form-panel__copy">Use produto para itens com estoque físico e serviço para mão de obra, conserto ou venda sem baixa de estoque.</p>
        <?php
            $action = '/catalog';
            $submitLabel = 'Salvar item';
            require __DIR__ . '/_form.php';
        ?>
    </section>
</main>
