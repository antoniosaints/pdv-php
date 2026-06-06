(() => {
    const panels = Array.from(document.querySelectorAll('[data-print-status-panel]'));
    const buttons = Array.from(document.querySelectorAll('[data-print-action]'));

    if (panels.length === 0 && buttons.length === 0) {
        return;
    }

    const setStatus = (state, detail) => {
        panels.forEach((panel) => {
            panel.dataset.printState = state;
            const status = panel.querySelector('[data-print-status]');
            const description = panel.querySelector('[data-print-detail]');

            if (status) {
                status.textContent = state;
            }

            if (description) {
                description.textContent = detail;
            }
        });
    };

    const targetFor = (button) => {
        const targetId = button.getAttribute('data-print-target');

        if (!targetId) {
            return null;
        }

        return document.getElementById(targetId);
    };

    const nativePrint = (target) => {
        if (target) {
            document.body.dataset.printTarget = target.id;
        }

        setStatus('Impressão nativa', 'QZ Tray não foi usado. O navegador abriu o fluxo de impressão local.');
        window.print();
    };

    const printWithQz = async (target) => {
        if (!target) {
            setStatus('Erro', 'Área de impressão não encontrada na página.');
            return;
        }

        if (!window.qz) {
            setStatus('QZ indisponível', 'QZ Tray não foi detectado. Usando impressão nativa como fallback.');
            nativePrint(target);
            return;
        }

        try {
            setStatus('Conectando', 'Tentando conectar ao QZ Tray no navegador.');

            if (!window.qz.websocket.isActive()) {
                await window.qz.websocket.connect();
            }

            setStatus('Conectado', 'QZ Tray conectado. Buscando impressora padrão.');
            const printer = await window.qz.printers.getDefault();
            const config = window.qz.configs.create(printer);
            const payload = [{
                type: 'html',
                format: 'plain',
                data: target.outerHTML,
            }];

            await window.qz.print(config, payload);
            setStatus('Enviado', `Trabalho enviado para ${printer}.`);
        } catch (error) {
            const message = error instanceof Error ? error.message : String(error);
            setStatus('Erro', `Falha ao imprimir via QZ Tray: ${message}`);
        }
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = targetFor(button);
            const action = button.getAttribute('data-print-action');

            if (action === 'qz') {
                void printWithQz(target);
                return;
            }

            nativePrint(target);
        });
    });

    if (window.qz) {
        setStatus('QZ detectado', 'QZ Tray está disponível no navegador. Clique em enviar para imprimir.');
    } else {
        setStatus('QZ indisponível', 'QZ Tray não foi detectado. Use o botão de impressão nativa ou instale o QZ Tray.');
    }
})();
